<?php

namespace AlgoYounes\CircuitBreaker\Guzzle\Contracts;

use Psr\Http\Message\RequestInterface;

interface ServiceNameExtractorContract
{
    /** @param array<string, string> $requestOptions */
    public function extract(RequestInterface $request, array $requestOptions): string;
}
