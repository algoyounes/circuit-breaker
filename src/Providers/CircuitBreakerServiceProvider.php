<?php

namespace AlgoYounes\CircuitBreaker\Providers;

use AlgoYounes\CircuitBreaker\Config\CircuitBreakerConfig;
use AlgoYounes\CircuitBreaker\Contracts\StateManagerContract;
use AlgoYounes\CircuitBreaker\Guzzle\Contracts\FailureDetectorContract;
use AlgoYounes\CircuitBreaker\Guzzle\Contracts\ServiceNameExtractorContract;
use AlgoYounes\CircuitBreaker\Guzzle\DefaultFailureDetector;
use AlgoYounes\CircuitBreaker\Guzzle\ServiceNameExtractor;
use AlgoYounes\CircuitBreaker\Managers\State\Cache\CircuitStateCacheManager;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CircuitBreakerServiceProvider extends ServiceProvider
{
    private ?CircuitBreakerConfig $config = null;

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                dirname(__DIR__, 2).'/config/circuit-breaker.php' => config_path('circuit-breaker.php'),
            ], 'config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 2).'/config/circuit-breaker.php', 'circuit-breaker');

        $this->app->singleton(
            CircuitBreakerConfig::class,
            function (Application $app): CircuitBreakerConfig {
                /** @var ConfigRepository $configRepository */
                $configRepository = $app->make(ConfigRepository::class);
                $config = (array) $configRepository->get('circuit-breaker', []);

                // @phpstan-ignore-next-line
                return $this->config = CircuitBreakerConfig::createFromArray($config);
            }
        );

        if ($this->getConfig()->isNotEnabled()) {
            return;
        }

        $this->app->bind(CacheRepository::class, fn (Application $app): CacheRepository => $this->getCacheRepository($app));

        $this->app->bind(
            StateManagerContract::class,
            function (Application $app): StateManagerContract {
                $config = $this->getConfig();
                $cache = $this->getCacheRepository($app);

                return new CircuitStateCacheManager($config, $cache);
            }
        );

        $this->app->bind(ServiceNameExtractorContract::class, ServiceNameExtractor::class);
        $this->app->bind(FailureDetectorContract::class, DefaultFailureDetector::class);
    }

    private function getCacheRepository(Application $app): CacheRepository
    {
        /** @var CacheFactory $cacheFactory */
        $cacheFactory = $app->make(CacheFactory::class);

        return $cacheFactory->store($this->getCacheStoreName());
    }

    private function getCacheStoreName(): string
    {
        if ($this->getConfig()->isDefaultCacheStore()) {
            // @phpstan-ignore-next-line
            return config('cache.default');
        }

        return $this->getConfig()->getCacheStore();
    }

    private function getConfig(): CircuitBreakerConfig
    {
        if ($this->config instanceof CircuitBreakerConfig) {
            return $this->config;
        }

        /** @var CircuitBreakerConfig $config */
        $config = $this->app->make(CircuitBreakerConfig::class);

        return $this->config = $config;
    }
}
