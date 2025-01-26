<?php

namespace AlgoYounes\CircuitBreaker\Exceptions;

class InvalidConfigurationException extends \Exception
{
    public function __construct(string $message = "invalid configuration")
    {
        parent::__construct($message);
    }
}
