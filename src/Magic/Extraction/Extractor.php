<?php

namespace Mateffy\Magic\Extraction;

use Closure;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\Chat\TokenStats;
use Mateffy\Magic\Models\LLM;

abstract class Extractor implements Strategy
{
    public function __construct(
		public LLM $llm,
        public ?string $outputInstructions,
        public array $schema,

		/** @var ?Closure(array): void */
        protected ?Closure $onDataProgress,

        /** @var ?Closure(TokenStats): void */
        protected ?Closure $onTokenStats = null,

        /** @var ?Closure(Message): void */
        protected ?Closure $onMessageProgress = null,

        /** @var ?Closure(Message, string): void */
        protected ?Closure $onMessage = null,

        /** @var ?Closure(ActorTelemetry): void */
        protected ?Closure $onActorTelemetry = null,
    ) {}

	public static function make(
		LLM $llm,
		?string $outputInstructions,
		array $schema,

		/** @var ?Closure(array): void */
		?Closure $onDataProgress,

		/** @var ?Closure(TokenStats): void */
		?Closure $onTokenStats = null,

		/** @var ?Closure(Message): void */
		?Closure $onMessageProgress = null,

		/** @var ?Closure(Message, string): void */
		?Closure $onMessage = null,

		/** @var ?Closure(ActorTelemetry): void */
		?Closure $onActorTelemetry = null,
	): static {
		return new static(
			llm: $llm,
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
