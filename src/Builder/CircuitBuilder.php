<?php

namespace AlgoYounes\CircuitBreaker\Builder;

use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use AlgoYounes\CircuitBreaker\Managers\CircuitManager;
use AlgoYounes\CircuitBreaker\ValueObjects\Packet;
use Closure;

class CircuitBuilder
{
    private ?Closure $onOpenCallback = null;
    private ?Closure $onHalfOpenCallback = null;
    private ?Closure $onCloseCallback = null;
    private ?Closure $onFailureCallback = null;
    private ?Closure $onSuccessCallback = null;
    private ?Closure $onSteadyStateCallback = null;

    public function __construct(
        private readonly CircuitManager $circuitManager,
        private readonly string $serviceName
    ) {}

    public static function make(string $serviceName): self
    {
        return new self(
            app(CircuitManager::class),
            $serviceName,
        );
    }

    public function onOpen(Closure $callback): self
    {
        $this->onOpenCallback = $callback;

        return $this;
    }

    public function onHalfOpen(Closure $callback): self
    {
        $this->onHalfOpenCallback = $callback;

        return $this;
    }

    public function onSteadyState(Closure $callback): self
    {
        $this->onSteadyStateCallback = $callback;

        return $this;
    }

    public function onClose(Closure $callback): self
    {
        $this->onCloseCallback = $callback;

        return $this;
    }

    public function onFailure(Closure $callback): self
    {
        $this->onFailureCallback = $callback;

        return $this;
    }

    public function onSuccess(Closure $callback): self
    {
        $this->onSuccessCallback = $callback;

        return $this;
    }

    public function run(Closure $operation): Packet
    {
        $initialState = $this->circuitManager->getStatus($this->serviceName);

        $result = $this->circuitManager->run(
            $this->serviceName,
            $operation
        );

        $newState = $this->circuitManager->getStatus($this->serviceName);

        $this->handleStateChange($initialState, $newState);

        if ($this->onSuccessCallback && $result->isSuccess()) {
            $this->triggerCallback($this->onSuccessCallback);

            return $result;
        }

        if ($this->onFailureCallback && $result->isFailure()) {
            $this->triggerCallback($this->onFailureCallback);
        }

        return $result;
    }

    private function handleStateChange(CircuitStatus $initialState, CircuitStatus $newState): void
    {
        if ($initialState === $newState) {
            $this->triggerCallback($this->onSteadyStateCallback);

            return;
        }

        match ($newState) {
            CircuitStatus::OPEN => $this->triggerCallback($this->onOpenCallback),
            CircuitStatus::HALF_OPEN => $this->triggerCallback($this->onHalfOpenCallback),
            CircuitStatus::CLOSED => $this->triggerCallback($this->onCloseCallback),
        };
    }

    private function triggerCallback(?Closure $callback): void
    {
        if (! $callback instanceof Closure) {
            return;
        }

        $callback($this->serviceName);
    }
}
