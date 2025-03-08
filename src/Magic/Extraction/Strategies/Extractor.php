<?php

namespace Mateffy\Magic\Extraction\Strategies;

use Closure;
use Illuminate\Support\Str;
use Mateffy\Magic;
use Mateffy\Magic\Chat\ActorTelemetry;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\Chat\Messages\TextMessage;
use Mateffy\Magic\Chat\Prompt;
use Mateffy\Magic\Chat\Prompt\Role;
use Mateffy\Magic\Chat\TokenStats;
use Mateffy\Magic\Extraction\ContextOptions;
use Mateffy\Magic\Models\LLM;

abstract class Extractor implements Strategy
{
	protected ?TokenStats $totalTokenStats = null;

    public function __construct(
		public LLM $llm,
		public ContextOptions $contextOptions,
        public ?string $outputInstructions,
        public array $schema,
		public int $chunkSize,

		/** @var ?Closure(array): void $onDataProgress */
        protected ?Closure $onDataProgress,

        /** @var ?Closure(TokenStats): void $onTokenStats */
        protected ?Closure $onTokenStats = null,

        /** @var ?Closure(Message): void $onMessageProgress */
        protected ?Closure $onMessageProgress = null,

        /** @var ?Closure(Message, string): void $onMessage */
        protected ?Closure $onMessage = null,

        /** @var ?Closure(ActorTelemetry): void $onActorTelemetry */
        protected ?Closure $onActorTelemetry = null,
    ) {}

	/**
	 * @param ?Closure(array $data): void $onDataProgress
	 * @param ?Closure(TokenStats $tokenStats): void $onTokenStats
	 * @param ?Closure(Message $message): void $onMessageProgress
	 * @param ?Closure(Message $message, string $threadId): void $onMessage
	 * @param ?Closure(ActorTelemetry $telemetry): void $onActorTelemetry
	 */
	public static function make(
		LLM $llm,
		ContextOptions $contextOptions,
		?string $outputInstructions,
		array $schema,
		int $chunkSize,
		?Closure $onDataProgress,
		?Closure $onTokenStats = null,
		?Closure $onMessageProgress = null,
		?Closure $onMessage = null,
		?Closure $onActorTelemetry = null,
	): static {
		return new static(
			llm: $llm,
			contextOptions: $contextOptions,
			outputInstructions: $outputInstructions,
			schema: $schema,
			chunkSize: $chunkSize,
			onDataProgress: $onDataProgress,
			onTokenStats: $onTokenStats,
			onMessageProgress: $onMessageProgress,
			onMessage: $onMessage,
			onActorTelemetry: $onActorTelemetry,
		);
	}

	abstract public function run(array $artifacts): array;

	/**
	 * Log some telemetry data about the current actor running, so we know some more info about the current extraction.
	 */
	protected function createActorThread(LLM $llm, Prompt $prompt): string
	{
        $threadId = Str::uuid()->toString();

        if ($this->onActorTelemetry) {
            ($this->onActorTelemetry)(
                ActorTelemetry::fromLLM(
                    id: $threadId,
                    llm: $llm,
                    prompt: $prompt,
                )
            );
        }

		$this->logUserMessages(prompt: $prompt, threadId: $threadId);

		return $threadId;
	}

	/**
	 * We pass all the input messages through the callback to have them displayed in the run page.
	 */
	protected function logUserMessages(Prompt $prompt, string $threadId): void
	{
        if ($this->onMessage) {
            ($this->onMessage)(new TextMessage(role: Role::System, content: $prompt->system()), $threadId);

            foreach ($prompt->messages() as $message) {
                ($this->onMessage)($message, $threadId);
            }
        }
	}

	protected function logMessage(Message $message, string $threadId): void
	{
		if ($this->onMessage) {
			($this->onMessage)($message, $threadId);
		}
	}

	protected function logMessageProgress(Message $message): void
	{
		if ($this->onMessageProgress) {
			($this->onMessageProgress)($message);
		}
	}

	protected function logDataProgressFromMessage(Message $message): void
	{
		if ($data = $message->toArray()['data'] ?? null) {
			$this->logDataProgress(data: $data);
		}
	}

	protected function logDataProgress(array $data): void
	{
		if ($this->onDataProgress) {
			($this->onDataProgress)($data);
		}
	}

	protected function logTokenStats(?TokenStats $tokenStats): void
	{
		if (!$tokenStats) {
			return;
		}

		$this->totalTokenStats = $this->totalTokenStats?->add($tokenStats) ?? $tokenStats;

		if ($this->onTokenStats) {
			($this->onTokenStats)($this->totalTokenStats);
		}
	}

	protected function send(string $threadId, LLM $llm, Prompt $prompt): ?array
	{
		$data = Magic::chat()
            ->model($llm)
            ->prompt($prompt)
            ->onMessageProgress(function (Message $message) {
				$this->logDataProgressFromMessage(message: $message);
                $this->logMessageProgress(message: $message);
            })
            ->onMessage(function (Message $message) use (&$messages, $threadId) {
                $messages[] = $message;

				$this->logDataProgressFromMessage(message: $message);
                $this->logMessage(message: $message, threadId: $threadId);
            })
            ->onTokenStats(function (TokenStats $tokenStats) use (&$lastTokenStats) {
                $lastTokenStats = $tokenStats;

                $this->logTokenStats(tokenStats: $lastTokenStats);
            })
            ->stream()
			->lastData();

		if ($data) {
			$this->logDataProgress(data: $data);
		}

		return $data;
	}
}
