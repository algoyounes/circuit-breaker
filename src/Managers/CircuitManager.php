<?php

namespace AlgoYounes\CircuitBreaker\Managers;

use AlgoYounes\CircuitBreaker\Builder\CircuitBuilder;
use AlgoYounes\CircuitBreaker\Config\CircuitBreakerConfig;
use AlgoYounes\CircuitBreaker\Contracts\StateManagerContract;
use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use AlgoYounes\CircuitBreaker\ValueObjects\CircuitResult;
use Throwable;

class CircuitManager
{
    public function __construct(
        private readonly StateManagerContract $stateManager,
        private readonly CircuitBreakerConfig $config
    ) {}

    public function forService(string $service): CircuitBuilder
    {
        return new CircuitBuilder($this, $service);
    }

    public function getStatus(string $service): CircuitStatus
    {
        return $this->stateManager->getStatus($service);
    }

    /**
     * Check if the service is available based on its circuit breaker status:
     *  - `OPEN`: Service is unavailable (returns `false`)
     *  - `HALF_OPEN`/`CLOSED`: Service is available (returns `true`)
     *
     * @param  string|array<string>  $services
     *
     * @example
     *  isAvailable('payment-service')               // True if payment is CLOSED/HALF_OPEN
     *  isAvailable(['api-service', 'email-service') // True if both are CLOSED/HALF_OPEN
     *  isAvailable([])                              // False (explicit empty array case)
     */
    public function isAvailable(string|array $services): bool
    {
        if (is_string($services)) {
            return $this->getStatus($services)->notEquals(CircuitStatus::OPEN);
        }

        if ($services === []) {
            return false;
        }

        foreach ($services as $service) {
            if ($this->getStatus($service)->equals(CircuitStatus::OPEN)) {
                return false;
            }
        }

        return true;
    }

    public function recordSuccess(string $service): void
    {
        $this->stateManager->recordSuccess($service);

        if ($this->stateManager->hasSufficientSuccess($service)) {
            $this->stateManager->close($service);
        }
    }

    public function recordFailure(string $service): void
    {
        $this->stateManager->recordFailure($service);
    }

    public function run(string $service, callable $operation): CircuitResult
    {
        if ($this->config->isNotEnabled()) {
            return CircuitResult::success($operation());
        }

        $status = $this->getStatus($service);

        // Return early if the service is OPEN and in cooldown
        if ($status->equals(CircuitStatus::OPEN) && $this->stateManager->isInCooldown($service)) {
            return CircuitResult::circuitOpen($service);
        }

        // Transition to half-open if the status is OPEN and not in cooldown
        if ($status->equals(CircuitStatus::OPEN)) {
            $this->stateManager->halfOpen($service);
        }

        try {
            $result = $operation();

            // Return earlier is the status is different from HALF_OPEN
            if ($status->notEquals(CircuitStatus::HALF_OPEN)) {
                return CircuitResult::success($result);
            }

            // If the service was HALF_OPEN, record success and possibly close the circuit
            $this->stateManager->recordSuccess($service);

            // If the service has had sufficient successful calls, close the circuit
            if ($this->stateManager->hasSufficientSuccess($service)) {
                $this->stateManager->close($service);
            }

            return CircuitResult::success($result);
        } catch (Throwable $e) {
            // Record the failure and check if the failure threshold has been exceeded
            $this->stateManager->recordFailure($service);

            if ($this->stateManager->hasExceededThreshold($service)) {
                $this->stateManager->open($service);

                return CircuitResult::circuitOpen($service, $e);
            }

            return CircuitResult::failure($e, $this->getStatus($service));
        }
    }
}
