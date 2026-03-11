<?php

use AlgoYounes\CircuitBreaker\Guzzle\Exceptions\RejectedException;
use AlgoYounes\CircuitBreaker\Middleware\GuzzleMiddleware;
use AlgoYounes\CircuitBreaker\ValueObjects\CircuitResult;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;

it('returns success packet when circuit is closed and operation succeeds', function () {
    $operation = fn () => 'payment processed';

    $result = $this->circuitManager->run('payment-service', $operation);

    expect($result)->toBeInstanceOf(CircuitResult::class)
        ->and($result->isSuccess())->toBeTrue()
        ->and($result->getResult())->toBe('payment processed');
});

it('returns failure packet when circuit is closed and operation fails', function () {
    $operation = fn () => throw new Exception('Payment service is down');

    $result = $this->circuitManager->run('payment-service', $operation);

    expect($result)->toBeInstanceOf(CircuitResult::class)
        ->and($result->isSuccess())->toBeFalse()
        ->and($result->getErrorMessage())->toBe('Payment service is down');
});

it('returns success packet when circuit is half-open and operation succeeds', function () {
    $this->stateManager->open('payment-service');
    $this->stateManager->halfOpen('payment-service');

    $operation = fn () => 'payment processed';

    $result = $this->circuitManager->run('payment-service', $operation);

    expect($result)->toBeInstanceOf(CircuitResult::class)
        ->and($result->isSuccess())->toBeTrue()
        ->and($result->getResult())->toBe('payment processed');
});

it('returns failure packet when circuit is half-open and operation fails', function () {
    $this->stateManager->open('payment-service');
    $this->stateManager->halfOpen('payment-service');

    $operation = fn () => throw new Exception('Payment service is down');

    $result = $this->circuitManager->run('payment-service', $operation);

    expect($result)->toBeInstanceOf(CircuitResult::class)
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
        ->and($this->circuitManager->isAvailable('payment-service'))->toBeFalse();
});

it('closes circuit after successful operation in half-open state', function () {
    $this->stateManager->open('payment-service');
    $this->stateManager->halfOpen('payment-service');

    // Trigger 1 success (matches the success_threshold of 1)
    $this->circuitManager->run('payment-service', fn () => 'success');

    // Assert: Circuit should be CLOSED (service available)
    expect($this->circuitManager->isAvailable('payment-service'))->toBeTrue();
});

it('returns true when circuit is half-open', function () {
    $this->stateManager->open('payment-service');
    $this->stateManager->halfOpen('payment-service');

    expect($this->circuitManager->isAvailable('payment-service'))->toBeTrue();
});

it('returns true only when all services in array are closed', function () {
    // Service A: CLOSED, Service B: OPEN
    $this->stateManager->close('service-a');
    $this->stateManager->open('service-b');

    expect($this->circuitManager->isAvailable(['service-a', 'service-b']))->toBeFalse();
});

it('returns false for empty service array', function () {
    expect($this->circuitManager->isAvailable([]))->toBeFalse();
});

it('ensures circuit remains available after Guzzle request', function () {
    $handlers = HandlerStack::create();
    $handlers->push(GuzzleMiddleware::create());

    $client = new Client(['handler' => $handlers]);

    $client->get('http://example.com', [
        'headers' => ['X-Circuit-Key' => 'payment-service'],
    ]);

    expect($this->circuitManager->isAvailable('payment-service'))->toBeTrue();
});

it('throws RejectedException when circuit is open for request', function () {
    $this->expectException(RejectedException::class);

    $this->stateManager->open('payment-service');

    $handlers = HandlerStack::create();
    $handlers->push(GuzzleMiddleware::create());

    $client = new Client(['handler' => $handlers]);

    $client->get('http://example.com', [
        'headers' => ['X-Circuit-Key' => 'payment-service'],
    ]);
});

it('closes circuit after cooldown elapsed and successful probe', function () {
    // Arrange: Open circuit and simulate cooldown period has elapsed using Carbon::setTestNow
    $this->stateManager->open('payment-service');

    Carbon::setTestNow(Carbon::now()->addSeconds(120));
    try {
        $result = $this->circuitManager->run('payment-service', fn () => 'ok');
    } finally {
        // Reset time travel
        Carbon::setTestNow();
    }

    expect($result)->toBeInstanceOf(CircuitResult::class)
        ->and($result->isSuccess())->toBeTrue()
        ->and($this->circuitManager->isAvailable('payment-service'))->toBeTrue();
});

it('rejected exception factory sets message and service name correctly', function () {
    $ex = RejectedException::withServiceName('payment-service');

    expect($ex->getMessage())->toBe('"payment-service" is not available')
        ->and($ex->serviceName())->toBe('payment-service');
});

it('short-circuits without invoking the handler when circuit is open', function () {
    $this->stateManager->open('payment-service');

    $invoked = false;

    $baseHandler = function ($request, array $options) use (&$invoked) {
        $invoked = true;

        return Create::promiseFor(new Response(200));
    };

    $stack = new HandlerStack($baseHandler);
    $stack->push(GuzzleMiddleware::create());

    $client = new Client(['handler' => $stack]);

    try {
        $client->get('http://example.com', [
            'headers' => ['X-Circuit-Key' => 'payment-service'],
        ]);
        // If we got here, the middleware did not short-circuit
        expect()->fail('Expected RejectedException to be thrown');
    } catch (RejectedException $e) {
        // Expected
    }

    expect($invoked)->toBeFalse();
});
