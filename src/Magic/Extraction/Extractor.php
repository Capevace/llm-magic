<?php

namespace Mateffy\Magic\Extraction;

use Closure;
use Mateffy\Magic\Chat\ActorTelemetry;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\Chat\TokenStats;
use Mateffy\Magic\Models\LLM;

abstract class Extractor implements Strategy
{
    public function __construct(
		public LLM $llm,
		public ContextOptions $contextOptions,
        public ?string $outputInstructions,
        public array $schema,

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
			onDataProgress: $onDataProgress,
			onTokenStats: $onTokenStats,
			onMessageProgress: $onMessageProgress,
			onMessage: $onMessage,
			onActorTelemetry: $onActorTelemetry,
		);
	}

	abstract public function run(array $artifacts): array;
}
