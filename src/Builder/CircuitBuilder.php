<?php

namespace AlgoYounes\CircuitBreaker\Builder;

use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use AlgoYounes\CircuitBreaker\Managers\CircuitManager;
use AlgoYounes\CircuitBreaker\ValueObjects\CircuitResult;
use AlgoYounes\CircuitBreaker\ValueObjects\CircuitTransition;
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

    public function run(Closure $operation): CircuitResult
    {
        $initialState = $this->circuitManager->getStatus($this->serviceName);

        $result = $this->circuitManager->run(
            $this->serviceName,
            $operation
        );

        $newState = $this->circuitManager->getStatus($this->serviceName);

        $context = CircuitTransition::forStateChange($initialState, $newState);

        $this->handleStateChange($context);

        if ($this->onSuccessCallback && $result->isSuccess()) {
            $this->triggerCallback($this->onSuccessCallback, $result, $context);

            return $result;
        }

        if ($this->onFailureCallback && $result->isFailure()) {
            $this->triggerCallback($this->onFailureCallback, $result, $context);
        }

        return $result;
    }

    private function handleStateChange(CircuitTransition $context): void
    {
        if ($context->getPreviousState()->equals($context->getNewState())) {
            $this->triggerCallback($this->onSteadyStateCallback, $context);

            return;
        }

        match ($context->getNewState()) {
            CircuitStatus::OPEN => $this->triggerCallback($this->onOpenCallback, $context),
            CircuitStatus::HALF_OPEN => $this->triggerCallback($this->onHalfOpenCallback, $context),
            CircuitStatus::CLOSED => $this->triggerCallback($this->onCloseCallback, $context),
        };
    }

    private function triggerCallback(?Closure $callback, mixed ...$args): void
    {
        if (! $callback instanceof Closure) {
            return;
        }

        $callback(...$args);
    }
}
