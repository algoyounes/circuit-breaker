<?php

use AlgoYounes\CircuitBreaker\Contracts\StateManagerContract;
use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use AlgoYounes\CircuitBreaker\Guzzle\Exceptions\RejectedException;
use AlgoYounes\CircuitBreaker\Managers\CircuitManager;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->circuitManager = app(CircuitManager::class);
    $this->stateManager = app(StateManagerContract::class);
});

it('works with Laravel Http facade via withCircuitBreaker', function () {
    Http::fake([
        'api.example.com/*' => Http::response(['status' => 'ok'], 200),
    ]);

    $response = Http::withCircuitBreaker('example-service')
        ->get('https://api.example.com/health');

    expect($response->status())->toBe(200)
        ->and($response->json('status'))->toBe('ok')
        ->and($this->circuitManager->isAvailable('example-service'))->toBeTrue();
});

it('records failures through Http facade', function () {
    $threshold = config('circuit-breaker.defaults.failure_threshold', 5);

    Http::fake([
        'api.example.com/*' => Http::response('Server Error', 500),
    ]);

    for ($i = 0; $i < $threshold; $i++) {
        Http::withCircuitBreaker('failing-service')
            ->get('https://api.example.com/health');
    }

    expect($this->circuitManager->getStatus('failing-service'))->toBe(CircuitStatus::OPEN);
});

it('rejects requests when circuit is open via Http facade', function () {
    $this->stateManager->open('blocked-service');

    Http::fake([
        'api.example.com/*' => Http::response(['status' => 'ok'], 200),
    ]);

    expect(fn () => Http::withCircuitBreaker('blocked-service')
        ->get('https://api.example.com/health')
    )->toThrow(RejectedException::class);
});

it('uses hostname as service name when withCircuitBreaker is not used', function () {
    Http::fake([
        'api.example.com/*' => Http::response(['status' => 'ok'], 200),
    ]);

    $response = Http::withCircuitBreaker('custom-name')
        ->get('https://api.example.com/health');

    expect($response->status())->toBe(200)
        ->and($this->circuitManager->isAvailable('custom-name'))->toBeTrue();
});
