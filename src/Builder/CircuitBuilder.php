<?php

namespace AlgoYounes\CircuitBreaker\Builder;

use AlgoYounes\CircuitBreaker\Handlers\CallbackHandler;
use AlgoYounes\CircuitBreaker\Managers\CircuitManager;
use AlgoYounes\CircuitBreaker\ValueObjects\CircuitResult;
use AlgoYounes\CircuitBreaker\ValueObjects\CircuitTransition;
use Closure;

class CircuitBuilder
{
    private readonly CallbackHandler $callbackHandler;

    public function __construct(
        private readonly CircuitManager $circuitManager,
        private readonly string $serviceName
    ) {
        $this->callbackHandler = new CallbackHandler;
    }

    public static function make(string $serviceName): self
    {
        return new self(
            app(CircuitManager::class),
            $serviceName,
        );
    }

    public function onOpen(Closure $callback): self
    {
        $this->callbackHandler->setOnOpen($callback);

        return $this;
    }

    public function onHalfOpen(Closure $callback): self
    {
        $this->callbackHandler->setOnHalfOpen($callback);

        return $this;
    }

    public function onSteadyState(Closure $callback): self
    {
        $this->callbackHandler->setOnSteadyState($callback);

        return $this;
    }

    public function onClose(Closure $callback): self
    {
        $this->callbackHandler->setOnClose($callback);

        return $this;
    }

    public function onFailure(Closure $callback): self
    {
        $this->callbackHandler->setOnFailure($callback);

        return $this;
    }

    public function onSuccess(Closure $callback): self
    {
        $this->callbackHandler->setOnSuccess($callback);

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

        $this->callbackHandler->handleResult($result, $context);

        return $result;
    }
}
