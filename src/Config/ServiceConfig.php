<?php

namespace AlgoYounes\CircuitBreaker\Config;

use AlgoYounes\CircuitBreaker\Exceptions\InvalidConfigurationException;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, int>
 */
readonly class ServiceConfig implements Arrayable
{
    // Service parameter keys
    public const FAILURE_THRESHOLD_KEY = 'failure_threshold';
    public const COOLDOWN_PERIOD_KEY = 'cooldown_period';
    public const SUCCESS_THRESHOLD_KEY = 'success_threshold';

    public function __construct(
        private int $failureThreshold,
        private int $cooldownPeriod,
        private int $successThreshold,
    ) {}

    public function getFailureThreshold(): int
    {
        return $this->failureThreshold;
    }

    public function getCooldownPeriod(): int
    {
        return $this->cooldownPeriod;
    }

    public function getSuccessThreshold(): int
    {
        return $this->successThreshold;
    }

    /**
     * @param  array<string, int>  $config
     */
    public static function fromArray(array $config): self
    {
        $failureThreshold = $config[self::FAILURE_THRESHOLD_KEY];
        $cooldownPeriod = $config[self::COOLDOWN_PERIOD_KEY];
        $successThreshold = $config[self::SUCCESS_THRESHOLD_KEY];

        if ($failureThreshold < 1 || $cooldownPeriod < 1 || $successThreshold < 1) {
            throw new InvalidConfigurationException(
                'Service configuration values must be positive integers'
            );
        }

        return new self(
            failureThreshold: $failureThreshold,
            cooldownPeriod: $cooldownPeriod,
            successThreshold: $successThreshold
        );
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            self::FAILURE_THRESHOLD_KEY => $this->getFailureThreshold(),
            self::COOLDOWN_PERIOD_KEY   => $this->getCooldownPeriod(),
            self::SUCCESS_THRESHOLD_KEY => $this->getSuccessThreshold(),
        ];
    }
}
