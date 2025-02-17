<?php

namespace AlgoYounes\CircuitBreaker\Managers\State\Cache;

use AlgoYounes\CircuitBreaker\Config\CircuitBreakerConfig;
use AlgoYounes\CircuitBreaker\Contracts\StateManagerContract;
use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class CircuitStateCacheManager implements StateManagerContract
{
    private const CACHE_KEY = '%s:%s:%s';

    // Suffixes for cache keys
    private const STATUS_SUFFIX = 'status';
    private const COOLDOWN_END_SUFFIX = 'cooldown_end';
    private const FAILURE_SUFFIX = 'failure';
    private const SUCCESS_SUFFIX = 'success';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly CircuitBreakerConfig $config
    ) {}

    private function getCacheKey(string $circuitKey, string $suffix): string
    {
        return sprintf(
            self::CACHE_KEY,
            $this->config->getCachePrefix(),
            $circuitKey,
            $suffix
        );
    }

    private function setWithConfigTtl(string $key, mixed $value): void
    {
        $this->cache->set($key, $value, $this->config->getCacheTtl());
    }

    public function getStatus(string $service): CircuitStatus
    {
        $value = $this->cache->get(
            $this->getCacheKey($service, self::STATUS_SUFFIX),
            CircuitStatus::CLOSED->value
        );

        if (! is_string($value)) {
            return CircuitStatus::CLOSED;
        }

        return CircuitStatus::tryFrom($value) ?? CircuitStatus::CLOSED;
    }

    public function open(string $service): void
    {
        $serviceConfig = $this->config->getServiceConfig($service);
        $cooldownPeriod = max(1, $serviceConfig->getCooldownPeriod());
        $currentStatus = $this->getStatus($service);

        if ($currentStatus !== CircuitStatus::OPEN) {
            $cooldownEnd = Carbon::now()->addSeconds($cooldownPeriod)->getTimestamp();

            $this->setWithConfigTtl(
                $this->getCacheKey($service, self::COOLDOWN_END_SUFFIX),
                $cooldownEnd
            );
        }

        $this->setWithConfigTtl(
            $this->getCacheKey($service, self::STATUS_SUFFIX),
            CircuitStatus::OPEN->value
        );

        $this->resetCounters($service);
    }

    public function close(string $service): void
    {
        $this->cache->forget($this->getCacheKey($service, self::STATUS_SUFFIX));
        $this->cache->forget($this->getCacheKey($service, self::COOLDOWN_END_SUFFIX));
        $this->resetCounters($service);
    }

    public function halfOpen(string $service): void
    {
        $this->setWithConfigTtl(
            $this->getCacheKey($service, self::STATUS_SUFFIX),
            CircuitStatus::HALF_OPEN->value
        );

        $this->resetCounters($service);
    }

    public function isInCooldown(string $service): bool
    {
        $cooldownEnd = $this->cache->get(
            $this->getCacheKey($service, self::COOLDOWN_END_SUFFIX)
        );

        if (! is_int($cooldownEnd)) {
            return false;
        }

        return $cooldownEnd && Carbon::now()->lessThan(Carbon::createFromTimestamp($cooldownEnd));
    }

    public function recordSuccess(string $service): void
    {
        $this->cache->increment($this->getCacheKey($service, self::SUCCESS_SUFFIX));
        $this->cache->forget($this->getCacheKey($service, self::FAILURE_SUFFIX));
    }

    public function recordFailure(string $service): void
    {
        $this->cache->increment($this->getCacheKey($service, self::FAILURE_SUFFIX));
    }

    public function hasExceededThreshold(string $service): bool
    {
        $threshold = $this->config->getServiceConfig($service)->getFailureThreshold();

        return $this->getCounter($service, self::FAILURE_SUFFIX) >= $threshold;
    }

    public function hasSufficientSuccess(string $service): bool
    {
        $threshold = $this->config->getServiceConfig($service)->getSuccessThreshold();

        return $this->getCounter($service, self::SUCCESS_SUFFIX) >= $threshold;
    }

    private function resetCounters(string $service): void
    {
        $this->cache->forget($this->getCacheKey($service, self::FAILURE_SUFFIX));
        $this->cache->forget($this->getCacheKey($service, self::SUCCESS_SUFFIX));
    }

    private function getCounter(string $service, string $type): int
    {
        $value = $this->cache->get($this->getCacheKey($service, $type), 0);

        return is_int($value) ? $value : 0;
    }
}
