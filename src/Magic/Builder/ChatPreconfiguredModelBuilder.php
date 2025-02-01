<?php

namespace Mateffy\Magic\Builder;

use Mateffy\Magic\Builder\Concerns\HasArtifacts;
use Mateffy\Magic\Builder\Concerns\HasDebugger;
use Mateffy\Magic\Builder\Concerns\HasMessages;
use Mateffy\Magic\Builder\Concerns\HasModel;
use Mateffy\Magic\Builder\Concerns\HasMessageCallbacks;
use Mateffy\Magic\Builder\Concerns\HasPrompt;
use Mateffy\Magic\Builder\Concerns\HasSchema;
use Mateffy\Magic\Builder\Concerns\HasSystemPrompt;
use Mateffy\Magic\Builder\Concerns\HasTokenCallback;
use Mateffy\Magic\Builder\Concerns\HasTools;
use Mateffy\Magic\Functions\InvokableFunction;
use Mateffy\Magic\LLM\Message\FunctionInvocationMessage;
use Mateffy\Magic\LLM\Message\FunctionOutputMessage;
use Mateffy\Magic\LLM\Message\Message;
use Mateffy\Magic\LLM\Message\MultimodalMessage;
use Mateffy\Magic\LLM\Message\TextMessage;
use Mateffy\Magic\LLM\MessageCollection;
use Mateffy\Magic\Loop\EndConversation;
use Mateffy\Magic\Prompt\Prompt;

class ChatPreconfiguredModelBuilder
{
    use HasArtifacts;
	use HasDebugger;
    use HasMessages;
    use HasModel;
    use HasMessageCallbacks;
    use HasPrompt;
    use HasSchema;
    use HasSystemPrompt;
    use HasTokenCallback;
    use HasTools;

    public function build(): Prompt
    {
        return $this->prompt ?? new class($this) implements Prompt
        {
            public function __construct(protected ChatPreconfiguredModelBuilder $builder) {}

            public function system(): ?string
            {
                return $this->builder->systemPrompt ?? 'You are a helpful chatbot.';
            }

            public function messages(): array
            {
                $messages = MessageCollection::make();
                $current = [];
                $role = null;

                foreach ($this->builder->messages as $message) {
                    if ($message instanceof MultimodalMessage && count($current) > 0) {
                        $messages->push(new MultimodalMessage(role: $role, content: $current));
                        $current = [];

                        $messages->push($message);

                        continue;
                    } else if ($message instanceof MultimodalMessage) {
                        $messages->push($message);

                        continue;
                    } else if ($role && $message->role !== $role) {
                        $messages->push(new MultimodalMessage(role: $role, content: $current));
                        $current = [];
                    }

                    $role = $message->role;
                    $current[] = match($message::class) {
                        TextMessage::class => MultimodalMessage\Text::make($message->content),
                        FunctionInvocationMessage::class => MultimodalMessage\ToolUse::call($message->call),
                        FunctionOutputMessage::class => MultimodalMessage\ToolResult::output($message->call, $message->output),
                    };
                }

                if (count($current) > 0) {
                    $messages->push(new MultimodalMessage(role: $role, content: $current));
                }

//                dd($messages);

                return $messages->all();
            }

            public function functions(): array
            {
                return array_values($this->builder->tools);
            }

            public function forceFunction(): ?InvokableFunction
            {
                if (!$this->builder->toolChoice) {
                    return null;
                }

                return $this->builder->tools[$this->builder->toolChoice] ?? null;
            }

            public function shouldParseJson(): bool
            {
                return false;
            }
        };
    }

    public function stream(): MessageCollection
    {
		$self = $this;
        $prompt = $this->build();
		$debugger = $this->getDebugger();

        if ($debugger) {
            $debugger->onMessage(TextMessage::user($prompt->system()));

            foreach ($prompt->messages() as $message) {
                $debugger->onMessage($message);
            }
        }

        $messages = $this->model
			->stream(
				prompt: $prompt,
				onMessageProgress: function () use ($self, $debugger) {
					$args = func_get_args();

					if ($debugger) {
						$debugger->onMessageProgress(...$args);
					}

					if ($self->onMessageProgress) {
						($self->onMessageProgress)(...$args);
					}
				},
				onMessage: function () use ($self, $debugger) {
					$args = func_get_args();

					if ($debugger) {
						$debugger->onMessage(...$args);
					}

					if ($self->onMessage) {
						($self->onMessage)(...$args);
					}
				},
				onTokenStats: function () use ($self, $debugger) {
					$args = func_get_args();

					if ($debugger) {
						$debugger->onTokenStats(...$args);
					}

					if ($self->onTokenStats) {
						($self->onTokenStats)(...$args);
					}
				},
				onDataPacket: function () use ($self, $debugger) {
					$args = func_get_args();

					if ($debugger) {
						$debugger->onDataPacket(...$args);
					}
				},
			);

        return $this->handleMessages($messages);
    }

    public function send(): MessageCollection
    {
        $prompt = $this->build();

        return MessageCollection::make($this->model->send($prompt));
    }

    public function handleMessages(MessageCollection $messages, bool $ignoreInterrupts = false): MessageCollection
    {
        $continue = true;

        foreach ($messages as $message) {
            $this->addMessage($message);

            if ($message instanceof FunctionInvocationMessage) {
                if ($fn = $this->tools[$message->call->name] ?? null) {
                    /** @var InvokableFunction $fn */

                    $message->call->arguments = $fn->validate($message->call->arguments);

                    if (!$ignoreInterrupts && $this->shouldInterrupt && ($this->shouldInterrupt)($message->call)) {
                        return MessageCollection::make([$message]);
                    }

                    try {
                        $output = $fn->execute($message->call);

                        if ($output instanceof FunctionOutputMessage) {
                            $outputMessage = $output;
                        } else if ($output instanceof EndConversation) {
                            $outputMessage = FunctionOutputMessage::end(call: $message->call, output: $output->getOutput());
                        } else if ($output instanceof MultimodalMessage) {
                            $outputMessage = $output;
                        } else {
                            $outputMessage = FunctionOutputMessage::output(call: $message->call, output: $output);
                        }
                    } catch (\Throwable $e) {
                        $outputMessage = FunctionOutputMessage::error(call: $message->call, message: $e->getMessage());

                        if ($this->onToolError) {
                            ($this->onToolError)($e);
                        }
                    }

                    $messages->push($outputMessage);

                    if ($this->onMessageProgress) {
                        ($this->onMessageProgress)($outputMessage);
                    }

                    if ($this->onMessage) {
                        ($this->onMessage)($outputMessage);
                    }

                    $this->addMessage($outputMessage);

                    if (property_exists($outputMessage, 'endConversation') && $outputMessage->endConversation) {
                        $continue = false;
                    }
                }
            }
        }

        // Immediately continue if
        if ($continue && $messages->last() instanceof FunctionOutputMessage && !$messages->last()->endConversation) {
            return MessageCollection::make([
                ...$messages,
                ...$this->stream()
            ]);
        }

        return $messages;
    }
}
