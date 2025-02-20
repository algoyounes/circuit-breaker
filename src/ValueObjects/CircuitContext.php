<?php

namespace AlgoYounes\CircuitBreaker\ValueObjects;

use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use Carbon\Carbon;

readonly class CircuitContext
{
    private Carbon $occurredAt;

    public function __construct(
        public CircuitStatus $previousState,
        public CircuitStatus $newState,
    ) {
        $this->occurredAt = Carbon::now();
    }

    public static function forStateChange(
        CircuitStatus $previousState,
        CircuitStatus $newState
    ): self {
        return new self($previousState, $newState);
    }

    public function getOccurredAt(): Carbon
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
