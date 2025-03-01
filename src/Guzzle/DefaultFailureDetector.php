<?php

namespace AlgoYounes\CircuitBreaker\Guzzle;

use Psr\Http\Message\ResponseInterface;

class DefaultFailureDetector implements FailureDetectorContract
{
    public function isFailureResponse(ResponseInterface $response): bool
    {
        return false;
    }
}
