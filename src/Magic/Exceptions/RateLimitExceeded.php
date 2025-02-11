<?php

namespace Mateffy\Magic\Exceptions;

use Mateffy\Magic\Exceptions\RateLimitExceeded\RateLimits;
use Throwable;

class RateLimitExceeded extends \Exception implements LLMException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
        public readonly ?RateLimits $rateLimits = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getTitle(): string
    {
        return 'Rate Limit Exceeded';
    }
}
