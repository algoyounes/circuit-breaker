<?php

namespace AlgoYounes\CircuitBreaker\Config;

class CircuitBreakerConfig
{
    // Config keys
    public const ENABLED_KEY = 'enabled';
    public const DEFAULTS_KEY = 'defaults';
    public const SERVICES_KEY = 'services';

    // Cache config keys
    public const CACHE_TTL_KEY = 'cache.ttl';
    public const CACHE_PREFIX_KEY = 'cache.prefix';
    public const CACHE_STORE_KEY = 'cache.store';

    // Default values
    private const DEFAULT_ENABLED = true;
    private const DEFAULT_CACHE_TTL = 86400; // 24 hours
    public const DEFAULT_CACHE_STORE = 'default';
    public const DEFAULT_CACHE_PREFIX = 'circuit-breaker';

    private const DEFAULT_SERVICE_PARAMS = [
        ServiceConfig::FAILURE_THRESHOLD_KEY => 5,
        ServiceConfig::COOLDOWN_PERIOD_KEY   => 60,
        ServiceConfig::SUCCESS_THRESHOLD_KEY => 1,
    ];

    public function __construct(
        private readonly bool $enabled,
        private readonly int $cacheTtl,
        private readonly string $cachePrefix,
        private readonly string $cacheStore,
        private readonly ServiceConfig $defaults,
        /** @var array<string, array<string, int>> $services */
        private readonly array $services
    ) {}

    // @phpstan-ignore-next-line
    public static function createFromArray(array $attributes): self
    {
        $get = static fn (string $key, int|bool|string|array|null $default = null) => $attributes[$key] ?? $default;

        $defaultSettings = ServiceConfig::fromArray(
            array_merge(
                self::DEFAULT_SERVICE_PARAMS,
                array_intersect_key($get(self::DEFAULTS_KEY, []), self::DEFAULT_SERVICE_PARAMS)
            )
        );

        /** @var array<string, array<string, int>> $rawServices */
        $rawServices = $get(self::SERVICES_KEY, []);

        $services = [];
        foreach ($rawServices as $serviceName => $serviceConfig) {
            $filteredConfig = array_intersect_key($serviceConfig, self::DEFAULT_SERVICE_PARAMS);

            $services[$serviceName] = array_map('intval', $filteredConfig);
        }

        return new self(
            $get(self::ENABLED_KEY, self::DEFAULT_ENABLED),
            $get(self::CACHE_TTL_KEY, self::DEFAULT_CACHE_TTL),
            $get(self::CACHE_PREFIX_KEY, self::DEFAULT_CACHE_PREFIX),
            $get(self::CACHE_STORE_KEY, self::DEFAULT_CACHE_STORE),
            $defaultSettings,
            $services,
        );
    }

    public function getServiceConfig(string $serviceName): ServiceConfig
    {
        $service = $this->services[$serviceName] ?? [];
        if ($service === []) {
            return $this->getDefaultSettings();
        }

        return ServiceConfig::fromArray($service);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isNotEnabled(): bool
    {
        return $this->isEnabled() === false;
    }

    public function getCacheTtl(int $default = self::DEFAULT_CACHE_TTL): int
    {
        return $this->cacheTtl ?? $default;
    }

    public function getCacheStore(): string
    {
        return $this->cacheStore;
    }

    public function isDefaultCacheStore(): bool
    {
        return $this->getCacheStore() === self::DEFAULT_CACHE_STORE;
    }

    public function getCachePrefix(): string
    {
        return $this->cachePrefix;
    }

    public function getDefaultSettings(): ServiceConfig
    {
        return $this->defaults;
    }
}
