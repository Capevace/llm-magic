<?php

namespace Mateffy\Magic\Builder\Concerns;

use Mateffy\Magic;
use Mateffy\Magic\Extraction\Strategies\Strategy;

trait HasStrategy
{
    public ?string $strategy = null;

    public function strategy(?string $strategy): static
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * @return class-string<Strategy>
     */
    public function getStrategyClass(): string
    {
        $strategies = Magic::getExtractionStrategies();

		if (class_exists($this->strategy) && in_array(Strategy::class, class_implements($this->strategy))) {
			return $this->strategy;
		}

		if (!isset($strategies[$this->strategy])) {
			throw new \InvalidArgumentException("Unknown strategy: {$this->strategy}");
		}

		return $strategies[$this->strategy];
	}

	public function makeStrategy(): Strategy
	{
		$strategyClass = $this->getStrategyClass();

        return $strategyClass::make(
            llm: $this->getModel(),
			contextOptions: $this->getContextOptions(),
			outputInstructions: $this->outputInstructions,
			schema: $this->schema,
			chunkSize: $this->getChunkSize(),
			onDataProgress: $this->onDataProgress,
			onTokenStats: $this->onTokenStats,
			onMessageProgress: $this->onMessageProgress,
			onMessage: $this->onMessage,
			onActorTelemetry: $this->onActorTelemetry,
        );
	}
}
