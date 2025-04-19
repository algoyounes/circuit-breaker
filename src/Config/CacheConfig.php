<?php

namespace AlgoYounes\CircuitBreaker\Config;

class CacheConfig
{
    // Default values for cache configuration
    public const DEFAULT_TTL = 86400; // 24 hours.
    public const DEFAULT_STORE = 'default';
    public const DEFAULT_PREFIX = 'circuit-breaker';

    private function __construct(
        private readonly string $store,
        private readonly int $ttl,
        private readonly string $prefix
    ) {}

    /**
     * @param array{
     *     ttl?: int,
     *     prefix?: string,
     *     store?: string
     * } $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            store: $config['store'] ?? self::DEFAULT_STORE,
            ttl: $config['ttl'] ?? self::DEFAULT_TTL,
            prefix: $config['prefix'] ?? self::DEFAULT_PREFIX,
        );
    }

    public function getStore(): string
    {
        return $this->store;
    }

    public function getTtl(int $default = self::DEFAULT_TTL): int
    {
        return $this->ttl > 0 ? $this->ttl : $default;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function isDefaultCacheStore(): bool
    {
        return $this->store === self::DEFAULT_STORE;
    }
}
