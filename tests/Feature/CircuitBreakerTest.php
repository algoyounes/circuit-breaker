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

