<?php

namespace Mateffy\Magic\Extraction;

use Closure;
use Mateffy\Magic\Chat\ActorTelemetry;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\Chat\TokenStats;
use Mateffy\Magic\Models\LLM;

interface Strategy
{
	public static function make(
		LLM $llm,
		ContextOptions $contextOptions,
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
    ): static;

    public function run(array $artifacts): array;
}
