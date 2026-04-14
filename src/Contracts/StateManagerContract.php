<?php

declare(strict_types=1);

namespace AlgoYounes\CircuitBreaker\Contracts;

use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;

interface StateManagerContract
{
    public function getStatus(string $service): CircuitStatus;

    public function open(string $service): void;

    public function close(string $service): void;

    public function halfOpen(string $service): void;

    public function isInCooldown(string $service): bool;

    public function recordSuccess(string $service): void;

    public function recordFailure(string $service): void;

    public function hasExceededThreshold(string $service): bool;

    public function hasSufficientSuccess(string $service): bool;

    public function lockHalfOpen(string $service): bool;

    public function unlockHalfOpen(string $service): void;
}
