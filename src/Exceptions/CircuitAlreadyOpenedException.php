<?php

declare(strict_types=1);

namespace AlgoYounes\CircuitBreaker\Exceptions;

use Exception;

class CircuitAlreadyOpenedException extends Exception
{
    public function __construct(
        private readonly string $service,
        string $message = 'circuit breaker is already opened'
    ) {
        parent::__construct($message);
    }

    public function getService(): string
    {
        return $this->service;
    }
}
