<?php

namespace AlgoYounes\CircuitBreaker\Managers;

use AlgoYounes\CircuitBreaker\Config\CircuitBreakerConfig;
use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use AlgoYounes\CircuitBreaker\Contracts\StateManagerContract;
use AlgoYounes\CircuitBreaker\ValueObjects\Packet;
use Throwable;

class CircuitManager
{
    public function __construct(
        private StateManagerContract $stateManager,
        private CircuitBreakerConfig $config
    ) {}

    public function getStatus(string $service): CircuitStatus
    {
        return $this->stateManager->getStatus($service);
    }

    public function areAvailable(array $services): bool
    {
        foreach ($services as $service) {
            if ($this->getStatus($service) === CircuitStatus::OPEN) {
                return false;
            }
        }

        return true;
    }

    public function run(string $service, callable $operation): Packet
    {
        if (! $this->config->isEnabled()) {
            try {
                return Packet::success($operation());
            } catch (\Exception $e) {
                return Packet::failure($e->getMessage(), CircuitStatus::CLOSED);
            }
        }

        $status = $this->getStatus($service);

        if ($status === CircuitStatus::OPEN) {
            if ($this->stateManager->isInCooldown($service)) {
                return Packet::circuitOpen($service);
            }
            $this->stateManager->halfOpen($service);
        }

        try {
            $result = $operation();

            if ($status === CircuitStatus::HALF_OPEN) {
                $this->stateManager->recordSuccess($service);
                if ($this->stateManager->hasSufficientSuccesses($service)) {
                    $this->stateManager->close($service);
                }
            }

            return Packet::success($result);
        } catch (Throwable $e) {
            $this->stateManager->recordFailure($service);

            if ($this->stateManager->hasExceededThreshold($service)) {
                $this->stateManager->open($service);

                return Packet::circuitOpen($service);
            }

            return Packet::failure($e->getMessage(), $this->getStatus($service));
        }
    }
}
