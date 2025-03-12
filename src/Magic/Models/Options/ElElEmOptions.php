<?php

namespace Mateffy\Magic\Models\Options;

class ElElEmOptions
{
    public const DEFAULT_MAX_TOKENS = 4096;

    public function __construct(
        public int $maxTokens = self::DEFAULT_MAX_TOKENS,
    ) {}

    public function withOptions(array $data): static
    {
        return new static(
            maxTokens: $data['maxTokens'] ?? $this->maxTokens,
        );
    }
}
