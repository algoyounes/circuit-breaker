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
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function isFailure(): bool
    {
        return $this->isSuccess === false;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public static function success(mixed $result): self
    {
        return new self(
            isSuccess: true,
            result: $result,
            status: CircuitStatus::CLOSED
        );
    }

    public static function failure(string $errorMessage, CircuitStatus $status = CircuitStatus::CLOSED): self
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
