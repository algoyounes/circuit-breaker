<?php

namespace AlgoYounes\CircuitBreaker\Config;

class CircuitBreakerConfig
{
    // Circuit Breaker config keys
    public const ENABLED_KEY = 'enabled';

    // Default values
    public const DEFAULT_ENABLED = true;

    public function __construct(
        private readonly bool $enabled = self::DEFAULT_ENABLED,
    ) {}

    public static function createFromArray(array $attributes): self
    {
        $get = static fn (string $key, int|bool|string|array|null $default = null) => $attributes[$key] ?? $default;

        return new self(
            $get(self::ENABLED_KEY, self::DEFAULT_ENABLED)
        );
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
