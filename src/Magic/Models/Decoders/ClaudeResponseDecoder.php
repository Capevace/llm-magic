<?php

namespace Mateffy\Magic\Models\Decoders;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mateffy\Magic\Chat\Messages\ToolCall;
use Mateffy\Magic\Chat\Messages\ToolCallMessage;
use Mateffy\Magic\Chat\Messages\JsonMessage;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\Chat\Messages\PartialMessage;
use Mateffy\Magic\Chat\Messages\TextMessage;
use Mateffy\Magic\Chat\Prompt\Role;
use Mateffy\Magic\Chat\TokenStats;
use Psr\Http\Message\StreamInterface;

class ClaudeResponseDecoder implements Decoder
{
    public const CHUNK_SIZE = 512;

    /**
     * @var Message[]
     */
    protected array $messages = [];

    public ?int $inputTokens = null;

    public ?int $outputTokens = null;

    public function __construct(
        protected StreamInterface $response,
        /**
         * @var \Closure(PartialMessage): void
         */
        protected ?\Closure $onMessageProgress = null,

        /**
         * @var \Closure(Message): void
         */
        protected ?\Closure $onMessage = null,

		/**
		 * @var \Closure(array): void
		 */
		protected ?\Closure $onDataPacket = null,

        /**
         * @var \Closure(TokenStats): void
         */
        protected ?\Closure $onTokenStats = null,

        /**
         * @var \Closure(TokenStats): void
         */
        protected ?\Closure $onEnd = null,

        /**
         * @var bool
         */
        protected bool $json = true,
    ) {}

    public function process(): array
    {
        $unfinished = null;

        /** @var ?PartialMessage $message */
        $message = null;

        while (! $this->response->eof()) {
            $output = $this->response->read(self::CHUNK_SIZE);

			if ($this->onDataPacket) {
				($this->onDataPacket)(['data' => $output]);
			}

            if ($unfinished !== null) {
                $output = $unfinished.$output;
                $unfinished = null;
            }

            $newEvents = str($output)
                ->explode("\n\n");

            $lastEvent = $newEvents->pop();

            if (! Str::endsWith($lastEvent, "\n\n")) {
                $unfinished = $lastEvent;
            } else {
                $newEvents->push($lastEvent);
            }

            $newParsedEvents = collect();

            foreach ($newEvents as $event) {
                [$event, $data] = str($event)
                    ->explode("\n");

                $event = Str::after($event, 'event: ');
                $data = json_decode(Str::after($data, 'data: '), true);

                $newParsedEvents->push([$event, $data]);

                // End the previous message, if one exists
                if ($event === 'message_start' && $message !== null) {
                    $this->messages[] = $message;

                    if ($this->onMessage) {
                        ($this->onMessage)($message);
                    }

                    $message = null;
                }

                if ($event === 'message_start') {
                    $message = $this->json
                        ? JsonMessage::fromChunk('')
                        : TextMessage::fromChunk('');

                    $this->inputTokens = intval(Arr::get($data, 'message.usage.input_tokens') ?? 0);
                    $this->outputTokens = intval(Arr::get($data, 'message.usage.output_tokens') ?? 0);

                    if ($this->onTokenStats) {
                        ($this->onTokenStats)(TokenStats::withInputAndOutput($this->inputTokens, $this->outputTokens));
                    }
                } elseif ($event === 'content_block_start') {
                    $part = match ($data['content_block']['type']) {
                        default => $data['content_block']['text'] ?? null,
                        'tool_use' => null,
                    };

                    if ($data['content_block']['type'] === 'tool_use') {
                        if ($message instanceof TextMessage && !empty($message->text())) {
                            $this->messages[] = $message;

                            if ($this->onMessage) {
                                ($this->onMessage)($message);
                            }
                        }

                        if ($message instanceof ToolCallMessage && $message->data() !== null && count($message->data()) > 0) {
                            $this->messages[] = $message;

                            if ($this->onMessage) {
                                ($this->onMessage)($message);
                            }
                        }

                        $message = new ToolCallMessage(
                            role: Role::Assistant,
                            call: ($name = $data['content_block']['name'])
                                ? new ToolCall(
                                    name: $name,
                                    arguments: $data['content_block']['input'] ?? [],
                                    id: $data['content_block']['id'] ?? null,
                                )
                                : null,
                            partial: $part,
                        );
                    } else if (($part = $data['content_block']['text'] ?? null) && $message instanceof PartialMessage) {
                        $message->append($part);
                    } else {
                        Log::warning('Received content_block_start without having started a partial message', ['data' => $data]);
                    }

                    if ($this->onMessageProgress) {
                        ($this->onMessageProgress)($message);
                    }
                } elseif ($event === 'content_block_delta') {
                    $part = match ($data['delta']['type']) {
                        default => $data['delta']['text'],
                        'input_json_delta' => $data['delta']['partial_json'],
                    };

                    if ($part && $message instanceof PartialMessage) {
                        $message->append($part);
                    } else {
                        Log::warning('Received content_block_delta without having started a partial message', ['data' => $data, 'message' => $message]);
                    }

                    if ($this->onMessageProgress) {
                        ($this->onMessageProgress)($message);
                    }
                } elseif ($event === 'message_delta') {
                    // Anthropic seems to be using both 'delta' and 'usage' keys for the same thing
                    $this->outputTokens = intval(Arr::get($data, 'delta.usage.output_tokens') ?? Arr::get($data, 'usage.output_tokens'));

                    if ($this->onTokenStats) {
                        ($this->onTokenStats)(TokenStats::withInputAndOutput($this->inputTokens, $this->outputTokens));
                    }

                    if ($message !== null) {
                        $this->messages[] = $message;

                        if ($this->onMessage) {
                            ($this->onMessage)($message);
                        }

                        $message = null;
                    }
                }
            }
        }

        if ($message !== null) {
            $this->messages[] = $message;

            if ($this->onMessage) {
                ($this->onMessage)($message);
            }
        }

        return $this->messages;
    }
}
