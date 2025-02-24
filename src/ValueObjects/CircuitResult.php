<?php

namespace AlgoYounes\CircuitBreaker\ValueObjects;

use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use AlgoYounes\CircuitBreaker\Exceptions\CircuitAlreadyOpenedException;
use Throwable;

/**
 * @property CircuitStatus $status Circuit state at the time of execution
 */
readonly class CircuitResult
{
    public function __construct(
        public bool $isSuccess,
        public CircuitStatus $status,
        public mixed $result = null,
        public ?Throwable $error = null
    ) {}

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

    public function getError(): ?Throwable
    {
        return $this->error;
    }

    public function getErrorMessage(): ?string
    {
        return $this->error?->getMessage();
    }

    public function getStatus(): CircuitStatus
    {
        return $this->status;
    }

    public static function success(mixed $result): self
    {
        return new self(
            isSuccess: true,
            status: CircuitStatus::CLOSED,
            result: $result
        );
    }

    public static function failure(Throwable $error, CircuitStatus $status = CircuitStatus::CLOSED): self
    {
        return new self(
            isSuccess: false,
            status: $status,
            error: $error
        );
    }

    public static function circuitOpen(string $service, ?Throwable $error = null): self
    {
        $error ??= new CircuitAlreadyOpenedException($service);

        return new self(
            isSuccess: false,
            status: CircuitStatus::OPEN,
            error: $error
        );
    }
}
