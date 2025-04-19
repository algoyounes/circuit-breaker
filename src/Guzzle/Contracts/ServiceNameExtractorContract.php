<?php

namespace AlgoYounes\CircuitBreaker\Guzzle\Contracts;

use Psr\Http\Message\RequestInterface;

interface ServiceNameExtractorContract
{
    /** @param array<string, mixed> $requestOptions */
    public function extract(RequestInterface $request, array $requestOptions): string;
}
