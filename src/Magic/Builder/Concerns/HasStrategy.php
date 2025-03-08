<?php

namespace Mateffy\Magic\Builder\Concerns;

use Mateffy\Magic\Extraction\Strategies\ParallelStrategy;
use Mateffy\Magic\Extraction\Strategies\SequentialStrategy;
use Mateffy\Magic\Extraction\Strategies\SimpleStrategy;
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
     * @return class-string<Strategy>|null
     */
    public function getStrategyClass(): string
    {
        return match ($this->strategy) {
            'simple' => SimpleStrategy::class,
            'sequential' => SequentialStrategy::class,
            'parallel' => ParallelStrategy::class,
            default => throw new \InvalidArgumentException("Invalid strategy: {$this->strategy}"),
        };
    }
}
