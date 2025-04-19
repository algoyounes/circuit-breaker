<?php

namespace AlgoYounes\CircuitBreaker\Guzzle;

use AlgoYounes\CircuitBreaker\Guzzle\Contracts\ServiceNameExtractorContract;
use Psr\Http\Message\RequestInterface;

class ServiceNameExtractor implements ServiceNameExtractorContract
{
    private const OPTION_KEY = 'circuit-breaker.service_name';
    private const HEADER_NAME = 'X-Circuit-Key';

    /**
     * @param  array<string, mixed>  $requestOptions
     */
    public function extract(RequestInterface $request, array $requestOptions): string
    {
        $value = $requestOptions[self::OPTION_KEY] ?? null;
        if (is_string($value)) {
            return $value;
        }

        $header = $request->getHeader(self::HEADER_NAME);
        if ($header !== []) {
            return $header[0];
        }

        return $request->getUri()->getHost();
    }
}
