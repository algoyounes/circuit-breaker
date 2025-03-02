<?php

namespace AlgoYounes\CircuitBreaker;

use AlgoYounes\CircuitBreaker\Managers\CircuitManager;

class Circuit
{
    private readonly CircuitManager $circuitManager;
    public function __construct(
        private readonly string $serviceName
    ) {
        $this->circuitManager = app(CircuitManager::class);
    }

    public static function create(string $serviceName): self
    {
        return new self($serviceName);
    }

    public function isAvailable(): bool
    {
        return $this->circuitManager->isAvailable($this->serviceName);
    }

    public function recordSuccess(): void
    {
        $this->circuitManager->recordSuccess($this->serviceName);
    }

    public function recordFailure(): void
    {
        $this->circuitManager->recordFailure($this->serviceName);
    }
}
