<?php

namespace AlgoYounes\CircuitBreaker\Tests;

use AlgoYounes\CircuitBreaker\Contracts\StateManagerContract;
use AlgoYounes\CircuitBreaker\Managers\CircuitManager;
use AlgoYounes\CircuitBreaker\Providers\CircuitBreakerServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected CircuitManager $circuitManager;
    protected StateManagerContract $stateManager;

    protected function getPackageProviders($app): array
    {
        return [
            CircuitBreakerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app->singleton('cache', function ($app) {
            return new \Illuminate\Cache\Repository(
                new \Illuminate\Cache\ArrayStore
            );
        });

        $this->circuitManager = $app->make(CircuitManager::class);
        $this->stateManager = $app->make(StateManagerContract::class);
    }
}
