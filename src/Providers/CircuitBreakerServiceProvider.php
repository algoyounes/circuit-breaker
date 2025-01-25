<?php

namespace AlgoYounes\CircuitBreaker\Providers;

use AlgoYounes\CircuitBreaker\Config\CircuitBreakerConfig;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CircuitBreakerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            dirname(__DIR__, 2).'/config/circuit-breaker.php' => config_path('circuit-breaker.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->app->singleton(
            CircuitBreakerConfig::class,
            function (Application $app): CircuitBreakerConfig {
                /** @var ConfigRepository $configRepository */
                $configRepository = $app->make(ConfigRepository::class);
                $config = (array) $configRepository->get('circuit-breaker', []);

                return CircuitBreakerConfig::createFromArray($config);
            }
        );
    }
}
