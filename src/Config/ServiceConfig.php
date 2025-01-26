<?php

namespace AlgoYounes\CircuitBreaker\Config;

use Illuminate\Contracts\Support\Arrayable;

readonly class ServiceConfig implements Arrayable
{
    // Service parameter keys
    public const FAILURE_THRESHOLD_KEY = 'failure_threshold';

    public const COOLDOWN_PERIOD_KEY = 'cooldown_period';

    public const SUCCESS_THRESHOLD_KEY = 'success_threshold';

    public const FAILURE_WINDOW_KEY = 'failure_window';

    public const SUCCESS_WINDOW_KEY = 'success_window';

    public function __construct(
        private int $failureThreshold,
        private int $cooldownPeriod,
        private int $successThreshold,
        private int $failureWindow,
        private int $successWindow
    ) {
    }

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

    public function getFailureWindow(): int
    {
        return $this->failureWindow;
    }

    public function getSuccessWindow(): int
    {
        return $this->successWindow;
    }

    /**
     * @param  array<string, int>  $config
     */
    public static function fromArray(array $config): self
    {
        return new self(
            $config[self::FAILURE_THRESHOLD_KEY],
            $config[self::COOLDOWN_PERIOD_KEY],
            $config[self::SUCCESS_THRESHOLD_KEY],
            $config[self::FAILURE_WINDOW_KEY],
            $config[self::SUCCESS_WINDOW_KEY]
        );
    }

    public function toArray(): array
    {
        return [
            self::FAILURE_THRESHOLD_KEY => $this->getFailureThreshold(),
            self::COOLDOWN_PERIOD_KEY   => $this->getCooldownPeriod(),
            self::SUCCESS_THRESHOLD_KEY => $this->getSuccessThreshold(),
            self::FAILURE_WINDOW_KEY    => $this->getFailureWindow(),
            self::SUCCESS_WINDOW_KEY    => $this->getSuccessWindow(),
        ];
    }
}
