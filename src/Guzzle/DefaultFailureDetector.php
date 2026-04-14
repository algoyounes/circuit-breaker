<?php

declare(strict_types=1);

namespace AlgoYounes\CircuitBreaker\Guzzle;

use AlgoYounes\CircuitBreaker\Guzzle\Contracts\FailureDetectorContract;
use Psr\Http\Message\ResponseInterface;

class DefaultFailureDetector implements FailureDetectorContract
{
    public function isFailureResponse(ResponseInterface $response): bool
    {
        $code = $response->getStatusCode();

        return $code >= 500 || $code === 429;
    }
}
