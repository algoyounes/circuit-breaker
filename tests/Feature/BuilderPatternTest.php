<?php

use AlgoYounes\CircuitBreaker\Contracts\StateManagerContract;
use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use AlgoYounes\CircuitBreaker\Managers\CircuitManager;
use AlgoYounes\CircuitBreaker\ValueObjects\CircuitTransition;

beforeEach(function () {
    $this->circuitManager = app(CircuitManager::class);
    $this->stateManager = app(StateManagerContract::class);
});

it('executes operation using builder pattern', function () {
    $service = 'builder-test-'.uniqid();

    $result = $this->circuitManager->forService($service)
        ->run(fn () => 'success');

    expect($result->isSuccess())->toBeTrue()
        ->and($result->getResult())->toBe('success');
});

it('executes onSuccess callback', function () {
    $service = 'builder-success-'.uniqid();
    $callbackExecuted = false;

    $this->circuitManager->forService($service)
        ->onSuccess(function () use (&$callbackExecuted) {
            $callbackExecuted = true;
        })
        ->run(fn () => 'success');

    expect($callbackExecuted)->toBeTrue();
});

it('executes onFailure callback', function () {
    $service = 'builder-failure-'.uniqid();
    $callbackExecuted = false;

    $this->circuitManager->forService($service)
        ->onFailure(function () use (&$callbackExecuted) {
            $callbackExecuted = true;
        })
        ->run(function () {
            throw new Exception('failure');
        });

    expect($callbackExecuted)->toBeTrue();
});

it('executes onOpen callback when circuit opens', function () {
    $service = 'builder-open-'.uniqid();
    $callbackExecuted = false;
    $threshold = config('circuit-breaker.defaults.failure_threshold', 5);

    // Trigger failures to open circuit
    for ($i = 0; $i < $threshold; $i++) {
        $this->circuitManager->forService($service)
            ->onOpen(function (CircuitTransition $transition) use (&$callbackExecuted) {
                $callbackExecuted = true;
            })
            ->run(function () {
                throw new Exception('failure');
            });
    }

    expect($callbackExecuted)->toBeTrue();
});

it('executes onClose callback when circuit closes', function () {
    $service = 'builder-close-'.uniqid();
    $callbackExecuted = false;

    // Set to half-open
    $this->stateManager->halfOpen($service);

    // Trigger close
    $this->circuitManager->forService($service)
        ->onClose(function (CircuitTransition $transition) use (&$callbackExecuted) {
            $callbackExecuted = true;
        })
        ->run(fn () => 'success');

    expect($callbackExecuted)->toBeTrue();
});

it('executes multiple callbacks in sequence', function () {
    $service = 'builder-multiple-'.uniqid();
    $callbacks = [];

    $this->circuitManager->forService($service)
        ->onSuccess(function () use (&$callbacks) {
            $callbacks[] = 'success';
        })
        ->onSteadyState(function () use (&$callbacks) {
            $callbacks[] = 'steady';
        })
        ->run(fn () => 'success');

    expect($callbacks)->toContain('success')
        ->and($callbacks)->toContain('steady');
});

it('passes CircuitTransition to callbacks', function () {
    $service = 'builder-transition-'.uniqid();
    $receivedTransition = null;

    $this->circuitManager->forService($service)
        ->onSteadyState(function (CircuitTransition $transition) use (&$receivedTransition) {
            $receivedTransition = $transition;
        })
        ->run(fn () => 'success');

    expect($receivedTransition)->toBeInstanceOf(CircuitTransition::class)
        ->and($receivedTransition->newState)->toBe(CircuitStatus::CLOSED);
});
