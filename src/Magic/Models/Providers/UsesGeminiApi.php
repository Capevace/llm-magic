<?php

namespace Mateffy\Magic\Models\Providers;

use Closure;
use Gemini;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Arr;
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
use Mateffy\Magic\Exceptions\InvalidRequest;
use Mateffy\Magic\Exceptions\TooManyTokensForModelRequested;
use Mateffy\Magic\Chat\Prompt;
use Mateffy\Magic\Models\Decoders\ClaudeResponseDecoder;
use Mateffy\Magic\Tools\InvokableTool;
use OpenAI\Responses\Chat\CreateResponse;
use Psr\Http\Message\StreamInterface;

trait UsesGeminiApi
{
    protected Gemini\Enums\ModelType $modelType = Gemini\Enums\ModelType::GEMINI_FLASH;

    protected function getApiToken(): string
    {
        return config('llm-magic.apis.gemini.token');
    }

    protected function client(): Gemini\Resources\GenerativeModel
    {
        return Gemini::client($this->getApiToken())
            ->generativeModel($this->modelType);
    }

    /**
     * @throws InvalidRequest
     * @throws TooManyTokensForModelRequested
     */
    protected function createStreamedHttpRequest(Prompt $prompt): \Psr\Http\Message\StreamInterface
    {
        $gemini = $this->client();

        $messages = collect($prompt->messages())
            ->map(fn (Message $message) => match ($message::class) {
                TextMessage::class => [
                    'role' => $message->role,
                    'content' => $message->content,
                ],
                JsonMessage::class => [
                    'role' => $message->role,
                    'content' => json_encode($message->data),
                ],
                FunctionInvocationMessage::class => [
                    'role' => $message->role,
                    'content' => [
                        [
                            'type' => 'tool_use',
                            'id' => $message->call->id ?? $message->call->name,
                            'name' => $message->call->name,
                            'input' => $message->call->arguments,
                        ],
                    ],
                ],
                FunctionOutputMessage::class => [
                    'role' => $message->role,
                    'content' => [
                        [
                            'type' => 'tool_result',
                            'tool_use_id' => $message->call->id ?? $message->call->name,
                            'content' => [
                                ['type' => 'text', 'text' => $message->text()],
                            ]
                        ],
                    ],
                ],
                MultimodalMessage::class => [
                    'role' => $message->role,
                    'content' => collect($message->content)
                        ->map(fn (ContentInterface $message) => match ($message::class) {
                            Text::class => [
                                'type' => 'text',
                                'text' => $message->text,
                            ],
                            Base64Image::class => [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $message->mime,
                                    'data' => $message->imageBase64,
                                ]
                            ],
                            ToolUse::class => [
                                'type' => 'tool_use',
                                'id' => $message->call->id,
                                'name' => $message->call->name,
                                'input' => $message->call->arguments,
                            ],
                            ToolResult::class => [
                                'type' => 'tool_result',
                                'tool_use_id' => $message->call->id,
                                'content' => json_encode($message->output),
                            ],
                        })
                        ->toArray(),
                ],

                default => null,
            })
            ->filter()
            ->values()
            ->toArray();

        $options = $this->getOptions();
        $data = [
            'model' => $this->getModelName(),
            'max_tokens' => $options->maxTokens,
            'system' => $prompt->system(),
            'stream' => true,
            'tools' => collect($prompt->tools())
                ->map(fn (InvokableTool $function) => ($name = $function->name()) && $name === 'computer'
                    ? [
                        'type' => 'computer_20241022',
                        'name' => 'computer',
                        'display_width_px' => 1024,
                        'display_height_px' => 768,
                        'display_number' => 1,
                    ]
                    : [
                        'name' => $function->name(),
                        'description' => method_exists($function, 'description') ? $function->description() : '',
                        'input_schema' => $function->schema(),
                    ]
                )
        ];

        if (method_exists($prompt, 'forceFunction') && ($fn = $prompt->forceFunction())) {
            $data['tool_choice'] = ['type' => 'tool', 'name' => $fn->name()];
        }

        try {
            $request = new Request('POST', '/v1/messages', [], json_encode($data));
            $response = $client->send($request, ['stream' => true]);

            return $response->getBody();
        } catch (ClientException $e) {
            $json = $e->getResponse()->getBody();
            $data = json_decode($json, true);

            if (Arr::has($data, 'error')) {
                $type = Arr::get($data, 'error.type');

                if ($type === 'invalid_request_error' && Str::startsWith(Arr::get($data, 'error.message'), 'max_tokens')) {
                    throw new TooManyTokensForModelRequested('Too many tokens: '.Arr::get($data, 'error.message'), previous: $e);
                }

                if ($message = Arr::get($data, 'error.message')) {
                    throw new InvalidRequest('API error', $message, previous: $e);
                }
            }

            throw new InvalidRequest('API error', $e->getMessage(), previous: $e);
        } catch (GuzzleException $e) {
            throw new InvalidRequest('HTTP error', $e->getMessage(), previous: $e);
        }
    }

    public function stream(Prompt $prompt, ?Closure $onMessageProgress = null, ?Closure $onMessage = null, ?Closure $onTokenStats = null, ?Closure $onDataPacket = null): MessageCollection
    {
        $stream = $this->createStreamedHttpRequest($prompt);

        $cost = $this->getModelCost();

        $decoder = new ClaudeResponseDecoder(
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
    }

    protected function createHttpRequest(Prompt $prompt): StreamInterface|CreateResponse|null
    {
        throw new \Exception('Not implemented');
    }

    public function send(Prompt $prompt): MessageCollection
    {
        throw new \Exception('Not implemented');
    }
}
