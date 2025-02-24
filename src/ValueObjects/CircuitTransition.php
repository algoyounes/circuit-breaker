<?php

namespace AlgoYounes\CircuitBreaker\ValueObjects;

use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use Carbon\CarbonImmutable;

readonly class CircuitTransition
{
    private CarbonImmutable $occurredAt;

    public function __construct(
        public CircuitStatus $previousState,
        public CircuitStatus $newState,
    ) {
        $this->occurredAt = CarbonImmutable::now();
    }

    public static function forStateChange(
        CircuitStatus $previousState,
        CircuitStatus $newState
    ): self {
        return new self($previousState, $newState);
    }

    public function getOccurredAt(): CarbonImmutable
    {
        return $this->occurredAt;
    }

    public function getPreviousState(): CircuitStatus
    {
        return $this->previousState;
    }

    public function getNewState(): CircuitStatus
    {
        return $this->newState;
    }
}
