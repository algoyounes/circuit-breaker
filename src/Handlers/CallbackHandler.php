<?php

namespace AlgoYounes\CircuitBreaker\Handlers;

use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use AlgoYounes\CircuitBreaker\ValueObjects\CircuitResult;
use AlgoYounes\CircuitBreaker\ValueObjects\CircuitTransition;
use Closure;

class CallbackHandler
{
    private ?Closure $onOpenCallback = null;
    private ?Closure $onHalfOpenCallback = null;
    private ?Closure $onCloseCallback = null;
    private ?Closure $onSuccessCallback = null;
    private ?Closure $onFailureCallback = null;
    private ?Closure $onSteadyStateCallback = null;

    public function setOnOpen(Closure $callback): self
    {
        $this->onOpenCallback = $callback;

        return $this;
    }

    public function setOnHalfOpen(Closure $callback): self
    {
        $this->onHalfOpenCallback = $callback;

        return $this;
    }

    public function setOnClose(Closure $callback): self
    {
        $this->onCloseCallback = $callback;

        return $this;
    }

    public function setOnFailure(Closure $callback): self
    {
        $this->onFailureCallback = $callback;

        return $this;
    }

    public function setOnSuccess(Closure $callback): self
    {
        $this->onSuccessCallback = $callback;

        return $this;
    }

    public function setOnSteadyState(Closure $callback): self
    {
        $this->onSteadyStateCallback = $callback;

        return $this;
    }

    public function handleResult(CircuitResult $result, CircuitTransition $context): void
    {
        $this->handleStateChange($context);

        if ($this->onSuccessCallback && $result->isSuccess()) {
            $this->trigger($this->onSuccessCallback, $result, $context);
        }

        if (! $this->onFailureCallback instanceof \Closure) {
            return;
        }

        if (! $result->isFailure()) {
            return;
        }

        $this->trigger($this->onFailureCallback, $result, $context);
    }

    public function handleStateChange(CircuitTransition $context): void
    {
        if ($context->getPreviousState()->equals($context->getNewState())) {
            $this->trigger($this->onSteadyStateCallback, $context);

            return;
        }

        match ($context->getNewState()) {
            CircuitStatus::OPEN => $this->trigger($this->onOpenCallback, $context),
            CircuitStatus::HALF_OPEN => $this->trigger($this->onHalfOpenCallback, $context),
            CircuitStatus::CLOSED => $this->trigger($this->onCloseCallback, $context),
        };
    }

    private function trigger(?Closure $callback, mixed ...$args): void
    {
        if (! $callback instanceof Closure) {
            return;
        }

        $callback(...$args);
    }
}
