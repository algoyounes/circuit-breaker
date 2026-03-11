<?php

use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use AlgoYounes\CircuitBreaker\Guzzle\Exceptions\RejectedException;
use AlgoYounes\CircuitBreaker\Managers\CircuitManager;
use AlgoYounes\CircuitBreaker\Middleware\GuzzleMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

beforeEach(function () {
    $this->circuitManager = app(CircuitManager::class);
});

it('opens circuit after threshold HTTP 500 errors', function () {
    $service = 'guzzle-500-test-'.uniqid();
    $threshold = config('circuit-breaker.defaults.failure_threshold', 5);

    // Create mock responses - all 500 errors
    $responses = array_fill(0, $threshold + 2, new Response(500, [], 'Server Error'));
    $mock = new MockHandler($responses);

    $stack = HandlerStack::create($mock);
    $stack->push(GuzzleMiddleware::create());

    $client = new Client(['handler' => $stack]);

    // Trigger failures
    for ($i = 0; $i < $threshold; $i++) {
        try {
            $client->get('http://test.example.com', [
                'headers' => ['X-Circuit-Key' => $service],
            ]);
        } catch (RequestException $e) {
            // Expected 500 errors
        }
    }

    // Verify circuit is OPEN
    $status = $this->circuitManager->getStatus($service);
    expect($status)->toBe(CircuitStatus::OPEN);
});

it('opens circuit after threshold HTTP 502 errors', function () {
    $service = 'guzzle-502-test-'.uniqid();
    $threshold = config('circuit-breaker.defaults.failure_threshold', 5);

    $responses = array_fill(0, $threshold + 2, new Response(502, [], 'Bad Gateway'));
    $mock = new MockHandler($responses);

    $stack = HandlerStack::create($mock);
    $stack->push(GuzzleMiddleware::create());

    $client = new Client(['handler' => $stack]);

    for ($i = 0; $i < $threshold; $i++) {
        try {
            $client->get('http://test.example.com', [
                'headers' => ['X-Circuit-Key' => $service],
            ]);
        } catch (RequestException $e) {
            // Expected
        }
    }

    $status = $this->circuitManager->getStatus($service);
    expect($status)->toBe(CircuitStatus::OPEN);
});

it('opens circuit after threshold HTTP 503 errors', function () {
    $service = 'guzzle-503-test-'.uniqid();
    $threshold = config('circuit-breaker.defaults.failure_threshold', 5);

    $responses = array_fill(0, $threshold + 2, new Response(503, [], 'Service Unavailable'));
    $mock = new MockHandler($responses);

    $stack = HandlerStack::create($mock);
    $stack->push(GuzzleMiddleware::create());

    $client = new Client(['handler' => $stack]);

    for ($i = 0; $i < $threshold; $i++) {
        try {
            $client->get('http://test.example.com', [
                'headers' => ['X-Circuit-Key' => $service],
            ]);
        } catch (RequestException $e) {
            // Expected
        }
    }

    $status = $this->circuitManager->getStatus($service);
    expect($status)->toBe(CircuitStatus::OPEN);
});

it('opens circuit after threshold HTTP 429 rate limit errors', function () {
    $service = 'guzzle-429-test-'.uniqid();
    $threshold = config('circuit-breaker.defaults.failure_threshold', 5);

    $responses = array_fill(0, $threshold + 2, new Response(429, [], 'Too Many Requests'));
    $mock = new MockHandler($responses);

    $stack = HandlerStack::create($mock);
    $stack->push(GuzzleMiddleware::create());

    $client = new Client(['handler' => $stack]);

    for ($i = 0; $i < $threshold; $i++) {
        try {
            $client->get('http://test.example.com', [
                'headers' => ['X-Circuit-Key' => $service],
            ]);
        } catch (RequestException $e) {
        }
    }

    $status = $this->circuitManager->getStatus($service);
    expect($status)->toBe(CircuitStatus::OPEN);
});

it('does NOT open circuit for 404 errors (client errors)', function () {
    $service = 'guzzle-404-test-'.uniqid();
    $threshold = config('circuit-breaker.defaults.failure_threshold', 5);

    $responses = array_fill(0, $threshold + 2, new Response(404, [], 'Not Found'));
    $mock = new MockHandler($responses);

    $stack = HandlerStack::create($mock);
    $stack->push(GuzzleMiddleware::create());

    $client = new Client(['handler' => $stack, 'http_errors' => false]);

    // 404 should NOT trigger circuit breaker (client error, not server failure)
    for ($i = 0; $i < $threshold; $i++) {
        $client->get('http://test.example.com', [
            'headers' => ['X-Circuit-Key' => $service],
        ]);
    }

    // Circuit should still be CLOSED
    $status = $this->circuitManager->getStatus($service);
    expect($status)->toBe(CircuitStatus::CLOSED);
});

it('records failure count correctly in cache', function () {
    $service = 'guzzle-count-test-'.uniqid();

    $mock = new MockHandler([
        new Response(500),
        new Response(500),
        new Response(500),
    ]);

    $stack = HandlerStack::create($mock);
    $stack->push(GuzzleMiddleware::create());

    $client = new Client(['handler' => $stack]);

    // Trigger 3 failures
    for ($i = 0; $i < 3; $i++) {
        try {
            $client->get('http://test.example.com', [
                'headers' => ['X-Circuit-Key' => $service],
            ]);
        } catch (RequestException $e) {
            // Expected
        }
    }

    // Verify cache has correct count
    $count = cache()->get('circuit-breaker:'.$service.':failure');
    expect($count)->toBe(3);
});

it('short-circuits requests after circuit opens via Guzzle failures', function () {
    $service = 'guzzle-short-circuit-'.uniqid();
    $threshold = config('circuit-breaker.defaults.failure_threshold', 5);

    // First: Open the circuit
    $responses = array_fill(0, $threshold + 5, new Response(500));
    $mock = new MockHandler($responses);

    $stack = HandlerStack::create($mock);
    $stack->push(GuzzleMiddleware::create());

    $client = new Client(['handler' => $stack]);

    for ($i = 0; $i < $threshold; $i++) {
        try {
            $client->get('http://test.example.com', [
                'headers' => ['X-Circuit-Key' => $service],
            ]);
        } catch (RequestException $e) {
        }
    }

    // Verify circuit is OPEN
    expect($this->circuitManager->getStatus($service))->toBe(CircuitStatus::OPEN);

    // Next request should be short-circuited (not reach handler)
    expect(fn () => $client->get('http://test.example.com', [
        'headers' => ['X-Circuit-Key' => $service],
    ]))->toThrow(RejectedException::class);
});
