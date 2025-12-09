<?php

namespace AlgoYounes\CircuitBreaker\Config;

class CircuitBreakerConfig
{
    // Config keys
    public const ENABLED_KEY = 'enabled';
    public const TIMEZONE_KEY = 'timezone';
    public const DEFAULTS_KEY = 'defaults';
    public const SERVICES_KEY = 'services';
    public const CACHE_KEY = 'cache';

    // Default values
    private const DEFAULT_ENABLED = true;
    public const DEFAULT_TIMEZONE = 'UTC';

    private const DEFAULT_SERVICE_PARAMS = [
        ServiceConfig::FAILURE_THRESHOLD_KEY => 5,
        ServiceConfig::COOLDOWN_PERIOD_KEY   => 60,
        ServiceConfig::SUCCESS_THRESHOLD_KEY => 1,
    ];

    public function __construct(
        private readonly bool $enabled,
        private readonly string $timezone,
        private readonly CacheConfig $cacheConfig,
        private readonly ServiceConfig $defaults,
        /** @var array<string, array<string, int>> $services */
        private readonly array $services
    ) {}

    /**
     * @param array{
     *   enabled?: bool,
     *   timezone?: string,
     *   cache?: array{ttl?: int, prefix?: string, store?: string},
     *   defaults?: array{
     *     failure_threshold?: int,
     *     cooldown_period?: int,
     *     success_threshold?: int
     *   },
     *   services?: array<string, array<string, int>>
     * } $attributes
     */
    public static function createFromArray(array $attributes): self
    {
        $defaultSettings = ServiceConfig::fromArray(
            array_merge(
                self::DEFAULT_SERVICE_PARAMS,
                array_intersect_key($attributes[self::DEFAULTS_KEY] ?? [], self::DEFAULT_SERVICE_PARAMS)
            )
        );

        $services = [];
        $rawServices = $attributes[self::SERVICES_KEY] ?? [];

        foreach ($rawServices as $serviceName => $serviceConfig) {
            $filteredConfig = array_intersect_key($serviceConfig, self::DEFAULT_SERVICE_PARAMS);
            if ($filteredConfig === []) {
                continue;
            }

            foreach ($filteredConfig as $key => $value) {
                $services[$serviceName][$key] = (int) $value;
            }
        }

        return new self(
            enabled: $attributes[self::ENABLED_KEY] ?? self::DEFAULT_ENABLED,
            timezone: $attributes[self::TIMEZONE_KEY] ?? self::DEFAULT_TIMEZONE,
            cacheConfig: CacheConfig::fromArray($attributes[self::CACHE_KEY] ?? []),
            defaults: $defaultSettings,
            services: $services,
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

    public function getCacheTtl(int $default = CacheConfig::DEFAULT_TTL): int
    {
        return $this->cacheConfig->getTtl($default);
    }

    public function getCacheStore(): string
    {
        return $this->cacheConfig->getStore();
    }

    public function isDefaultCacheStore(): bool
    {
        return $this->cacheConfig->isDefaultCacheStore();
    }

    public function getCachePrefix(): string
    {
        return $this->cacheConfig->getPrefix();
    }

    public function getDefaultSettings(): ServiceConfig
    {
        return $this->defaults;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }
}
