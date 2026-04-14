<?php

declare(strict_types=1);

namespace AlgoYounes\CircuitBreaker\Facades;

use AlgoYounes\CircuitBreaker\Builder\CircuitBuilder;
use AlgoYounes\CircuitBreaker\Enums\CircuitStatus;
use AlgoYounes\CircuitBreaker\Managers\CircuitManager;
use AlgoYounes\CircuitBreaker\ValueObjects\CircuitResult;
use Illuminate\Support\Facades\Facade;

/**
 * @method static CircuitBuilder forService(string $service)
 * @method static CircuitStatus getStatus(string $service)
 * @method static bool isAvailable(string|string[] $services)
 * @method static CircuitResult run(string $service, callable $operation)
 */
class Circuit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CircuitManager::class;
    }
}
