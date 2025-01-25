<?php

namespace AlgoYounes\CircuitBreaker\Facades;

use AlgoYounes\CircuitBreaker\Managers\CircuitManager;
use Illuminate\Support\Facades\Facade;

class Circuit extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CircuitManager::class;
    }
}
