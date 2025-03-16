<?php

namespace AlgoYounes\CircuitBreaker\Guzzle;

use AlgoYounes\CircuitBreaker\Guzzle\Contracts\ServiceNameExtractorContract;
use Psr\Http\Message\RequestInterface;

class ServiceNameExtractor implements ServiceNameExtractorContract
{
    private const OPTION_KEY = 'circuit-breaker.service_name';
    private const HEADER_NAME = 'X-Circuit-Key';

    public function extract(RequestInterface $request, array $requestOptions): string
    {
        if (array_key_exists(self::OPTION_KEY, $requestOptions)) {
            return $requestOptions[self::OPTION_KEY];
        }

        $header = $request->getHeader(self::HEADER_NAME);
        if ($header !== []) {
            return $header[0];
        }

        return $request->getUri()->getHost();
    }
}
