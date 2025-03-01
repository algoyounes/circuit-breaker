<?php

namespace AlgoYounes\CircuitBreaker\Guzzle\Contracts;

use Psr\Http\Message\ResponseInterface;

interface FailureDetectorContract
{
    public function isFailureResponse(ResponseInterface $response): bool;
}
