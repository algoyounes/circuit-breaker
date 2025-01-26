<?php

use AlgoYounes\CircuitBreaker\ValueObjects\Packet;

it('returns success packet when circuit is closed and operation succeeds', function () {
    $operation = fn () => 'payment processed';

    $result = $this->circuitManager->run('payment-service', $operation);

    expect($result)->toBeInstanceOf(Packet::class)
        ->and($result->isSuccess())->toBeTrue()
        ->and($result->getResult())->toBe('payment processed');
});
