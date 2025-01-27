<?php

namespace AlgoYounes\CircuitBreaker\Managers;

use AlgoYounes\CircuitBreaker\Builder\CircuitBuilder;
use AlgoYounes\CircuitBreaker\Config\CircuitBreakerConfig;
use AlgoYounes\CircuitBreaker\Contracts\StateManagerContract;
use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use AlgoYounes\CircuitBreaker\ValueObjects\Packet;
use Throwable;

class CircuitManager
{
    public function __construct(
        private readonly StateManagerContract $stateManager,
        private readonly CircuitBreakerConfig $config
    ) {
    }

    public function forService(string $service): CircuitBuilder
    {
        return new CircuitBuilder($this, $service);
    }

    public function getStatus(string $service): CircuitStatus
    {
        return $this->stateManager->getStatus($service);
    }

    /**
     * @param  string|array<string>  $services
     */
    public function isServiceAvailable(string|array $services): bool
    {
        if (is_string($services)) {
            return $this->getStatus($services) !== CircuitStatus::OPEN;
        }

        foreach ($services as $service) {
            if ($this->getStatus($service) === CircuitStatus::OPEN) {
                return false;
            }
        }

        return true;
    }

    public function run(string $service, callable $operation): Packet
    {
        if ($this->config->isNotEnabled()) {
            return Packet::success($operation());
        }

        $status = $this->getStatus($service);

        // Handle OPEN status (check cooldown first, otherwise transition to half-open)
        if ($status === CircuitStatus::OPEN) {
            if ($this->stateManager->isInCooldown($service)) {
                return Packet::circuitOpen($service);
            }

            $this->stateManager->halfOpen($service);
        }

        try {
            $result = $operation();

            // If the service was HALF_OPEN, record success and possibly close the circuit
            if ($status === CircuitStatus::HALF_OPEN) {
                $this->stateManager->recordSuccess($service);

                // If the service has had sufficient successful calls, close the circuit
                if ($this->stateManager->hasSufficientSuccess($service)) {
                    $this->stateManager->close($service);
                }
            }

            return Packet::success($result);
        } catch (Throwable $e) {
            // Record the failure and check if the failure threshold has been exceeded
            $this->stateManager->recordFailure($service);

            if ($this->stateManager->hasExceededThreshold($service)) {
                $this->stateManager->open($service);

                return Packet::circuitOpen($service);
            }

            return Packet::failure($e->getMessage(), $this->getStatus($service));
        }
    }
}
