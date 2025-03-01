<?php

namespace Mateffy\Magic\Models\Providers;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mateffy\Magic\Chat\MessageCollection;
use Mateffy\Magic\Chat\Messages\FunctionInvocationMessage;
use Mateffy\Magic\Chat\Messages\FunctionOutputMessage;
use Mateffy\Magic\Chat\Messages\JsonMessage;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\Chat\Messages\MultimodalMessage;
use Mateffy\Magic\Chat\Messages\MultimodalMessage\Base64Image;
use Mateffy\Magic\Chat\Messages\MultimodalMessage\ContentInterface;
use Mateffy\Magic\Chat\Messages\MultimodalMessage\Text;
use Mateffy\Magic\Chat\Messages\MultimodalMessage\ToolResult;
use Mateffy\Magic\Chat\Messages\MultimodalMessage\ToolUse;
use Mateffy\Magic\Chat\Messages\TextMessage;
use Mateffy\Magic\Chat\TokenStats;
use Mateffy\Magic\Chat\ToolChoice;
use Mateffy\Magic\Exceptions\UnknownInferenceException;
use Mateffy\Magic\Chat\Prompt;
use Mateffy\Magic\Models\Decoders\OpenAiResponseDecoder;
use Mateffy\Magic\Tools\InvokableTool;
use OpenAI\Client;

trait UsesOpenAiApi
{
    protected function getOpenAiApiKey(): string
    {
        return config('llm-magic.apis.openai.token');
    }

    protected function getOpenAiOrganization(): ?string
    {
        return config('llm-magic.apis.openai.organization_id');
    }

    protected function getOpenAiBaseUri(): ?string
    {
        return 'api.openai.com/v1';
    }

	protected function getSystemMessageRoleName(): string
	{
		if (Str::startsWith($this->model, 'o1')) {
			return 'user';
		}

		if ($this->organization->id === 'openai') {
			return 'developer';
		}

		return 'system';
	}

	protected function createClient(): Client
	{
		return \OpenAI::factory()
			->withApiKey($this->getOpenAiApiKey())
			->withOrganization($this->getOpenAiOrganization())
			->withBaseUri($this->getOpenAiBaseUri())
			->make();
	}

	protected function prepareMessages(array $messages): Collection
	{
		return collect($messages)
            ->flatMap(callback: fn (Message $message) => match ($message::class) {
                TextMessage::class => [
                    [
                        'role' => $message->role,
                        'content' => $message->content,
                    ],
                ],
                JsonMessage::class => [
                    [
                        'role' => $message->role,
                        'content' => json_encode($message->data),
                    ],
                ],
                FunctionInvocationMessage::class => [
                    [
                        'role' => $message->role,
                        'tool_calls' => [
                            [
                                'id' => $message->call->id,
                                'type' => 'function',
                                'function' => [
                                    'name' => $message->call->name,
                                    'arguments' => json_encode($message->call->arguments, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                ]
                            ]
                        ],
                        'content' => json_encode($message->call->arguments),
                        'tool_call_id' => $message->call->id,
                    ],
                ],
                FunctionOutputMessage::class => [
                    [
                        'role' => 'tool',
                        'content' => $message->text(),
                        'tool_call_id' => $message->call->id,
                    ],
                ],
                MultimodalMessage::class => (function () use ($message) {
                    $tools = collect($message->content)
                        ->filter(fn (ContentInterface $message) => $message instanceof ToolUse || $message instanceof ToolResult)
                        ->map(fn (ContentInterface $message) => match ($message::class) {
                            ToolUse::class => [
                                'role' => 'assistant',
                                'tool_calls' => [
                                    [
                                        'id' => $message->call->id,
                                        'type' => 'function',
                                        'function' => [
                                            'name' => $message->call->name,
                                            'arguments' => json_encode($message->call->arguments, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                        ]
                                    ]
                                ],
                                'content' => json_encode($message->call->arguments),
                                'tool_call_id' => $message->call->id,
                            ],
                            ToolResult::class => [
                                'role' => 'tool',
                                'content' => json_encode($message->output, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                                'tool_call_id' => $message->call->id,
                            ],
                        })
                        ->values();

                    $content = collect($message->content)
                        ->filter(fn (ContentInterface $message) => !($message instanceof ToolUse || $message instanceof ToolResult))
                        ->values();

					dump('MultimodalMessage', [
						'content' => $content
							->map(fn (ContentInterface $message) => match ($message::class) {
								Text::class => null,
								Base64Image::class => $message->mime
							})
							->toArray(),
					]);

                    return array_filter([
                        ...$tools,
                        $content->isNotEmpty()
                            ? [
                                'role' => $message->role,
                                'content' => $content
                                    ->map(fn (ContentInterface $message) => match ($message::class) {
                                        Text::class => [
                                            'type' => 'text',
                                            'text' => $message->text,
                                        ],
                                        Base64Image::class => [
                                            'type' => 'image_url',
                                            'image_url' => [
                                                'url' => "data:{$message->mime};base64,{$message->imageBase64}"
                                            ]
                                        ],
                                    })
									->values()
                                    ->toArray(),
                            ]
                            : null,
                    ]);
                })(),

                default => null,
            })
            ->filter()
            ->values();
	}

	protected function prepareTools(array $tools): Collection
	{
		return collect($tools)
            ->map(fn (InvokableTool $function) => [
                'type' => 'function',
                'function' => [
                    'name' => $function->name(),
                    'description' => method_exists($function, 'description') ? $function->description() : "The {$function->name()} function",
                    'parameters' => $function->schema(),
                ]
            ])
			->values();
	}

	/**
     * @throws UnknownInferenceException
     */
    public function stream(Prompt $prompt, ?Closure $onMessageProgress = null, ?Closure $onMessage = null, ?Closure $onTokenStats = null, ?Closure $onDataPacket = null): MessageCollection
    {
		$messages = $this->prepareMessages(messages: $prompt->messages());
        $tools = $this->prepareTools($prompt->tools());

		if ($system = $prompt->system()) {
			$messages->unshift([
				'role' => $this->getSystemMessageRoleName(),
				'content' => [
					[
						'type' => 'text',
						'text' => $system,
					]
				]
			]);
		}

		$rawToolChoice = $prompt->toolChoice();
		$toolChoice = match ($rawToolChoice) {
			ToolChoice::Auto => null,
			ToolChoice::Required => 'required',
			default => ['type' => 'function', 'function' => ['name' => $rawToolChoice]]
		};

		$data = array_filter([
			'model' => str($this->model)
				->after('openai/'),
			'messages' => $messages,
			'tools' => count($tools) > 0 ? $tools : null,
			'tool_choice' => count($tools) > 0 ? $toolChoice : null,
		]);

        try {
            $stream = $this->createClient()
                ->chat()
                ->createStreamed($data);

            $cost = $this->getModelCost();

            $decoder = new OpenAiResponseDecoder(
                $stream,
                $onMessageProgress,
                $onMessage,
				$onDataPacket,
                onTokenStats: fn (TokenStats $stats) => $onTokenStats
                    ? $onTokenStats($cost
                        ? $stats->withCost($cost)
                        : $stats
                    )
                    : $stats,
                json: $prompt->shouldParseJson()
            );

            return MessageCollection::make($decoder->process());
        } catch (\Throwable $e) {
            throw new UnknownInferenceException($e->getMessage(), previous: $e);
        }
    }

    public function send(Prompt $prompt): MessageCollection
    {
        // TODO: Implement send() method.
    }
}
