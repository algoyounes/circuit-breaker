<?php

namespace AlgoYounes\CircuitBreaker\ValueObjects;

use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;

class Packet
{
    public function __construct(
        public readonly bool $isSuccess,
        public readonly mixed $result = null,
        public readonly ?string $errorMessage = null,
        public readonly ?CircuitStatus $status = null
    ) {}

    public static function success(mixed $result): self
    {
        return new self(
            isSuccess: true,
            result: $result,
            status: CircuitStatus::CLOSED
        );
    }

    public static function failure(string $errorMessage, CircuitStatus $status): self
    {
        return new self(
            isSuccess: false,
            errorMessage: $errorMessage,
            status: $status
        );
    }

    public static function circuitOpen(string $service): self
    {
        return new self(
            isSuccess: false,
            errorMessage: "Circuit is open for service: $service",
            status: CircuitStatus::OPEN
        );
    }
}
