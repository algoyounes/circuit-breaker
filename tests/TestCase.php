<?php

namespace AlgoYounes\CircuitBreaker\Tests;

use AlgoYounes\CircuitBreaker\Providers\CircuitBreakerServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CircuitBreakerServiceProvider::class,
        ];
    }
}
