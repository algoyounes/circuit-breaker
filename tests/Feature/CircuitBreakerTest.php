<?php

use AlgoYounes\CircuitBreaker\ValueObjects\Packet;

it('returns success packet when circuit is closed and operation succeeds', function () {
    $operation = fn () => 'payment processed';

    $result = $this->circuitManager->run('payment-service', $operation);

    expect($result)->toBeInstanceOf(Packet::class)
        ->and($result->isSuccess())->toBeTrue()
        ->and($result->getResult())->toBe('payment processed');
});

it('returns failure packet when circuit is closed and operation fails', function () {
    $operation = fn () => throw new Exception('Payment service is down');

    $result = $this->circuitManager->run('payment-service', $operation);

    expect($result)->toBeInstanceOf(Packet::class)
        ->and($result->isSuccess())->toBeFalse()
        ->and($result->getErrorMessage())->toBe('Payment service is down');
});

it('returns success packet when circuit is half-open and operation succeeds', function () {
    $this->stateManager->open('payment-service');
    $this->stateManager->halfOpen('payment-service');

    $operation = fn () => 'payment processed';

    $result = $this->circuitManager->run('payment-service', $operation);

    expect($result)->toBeInstanceOf(Packet::class)
        ->and($result->isSuccess())->toBeTrue()
        ->and($result->getResult())->toBe('payment processed');
});

it('returns failure packet when circuit is half-open and operation fails', function () {
    $this->stateManager->open('payment-service');
    $this->stateManager->halfOpen('payment-service');

    $operation = fn () => throw new Exception('Payment service is down');

    $result = $this->circuitManager->run('payment-service', $operation);

    expect($result)->toBeInstanceOf(Packet::class)
        ->and($result->isSuccess())->toBeFalse()
        ->and($result->getErrorMessage())->toBe('Payment service is down');
});
it('opens circuit after exceeding failure threshold', function () {
    $threshold = 5;

    // Trigger 5 failures (now matching the failure_threshold of 5)
    for ($i = 0; $i < $threshold; $i++) {
        $this->circuitManager->run('payment-service', fn () => throw new Exception('Failed'));
    }

    // Subsequent call should fail (circuit is now OPEN)
    $result = $this->circuitManager->run('payment-service', fn () => 'ignored');

    expect($result->isSuccess())->toBeFalse()
        ->and($this->circuitManager->isServiceAvailable('payment-service'))->toBeFalse();
});

it('closes circuit after successful operation in half-open state', function () {
    $this->stateManager->open('payment-service');
    $this->stateManager->halfOpen('payment-service');

    // Trigger 1 success (matches the success_threshold of 1)
    $this->circuitManager->run('payment-service', fn () => 'success');

    // Assert: Circuit should be CLOSED (service available)
    expect($this->circuitManager->isServiceAvailable('payment-service'))->toBeTrue();
});
