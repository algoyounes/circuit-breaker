<?php

declare(strict_types=1);

namespace AlgoYounes\CircuitBreaker\Guzzle;

use AlgoYounes\CircuitBreaker\Guzzle\Contracts\ServiceNameExtractorContract;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;

class ServiceNameExtractor implements ServiceNameExtractorContract
{
    private const OPTION_KEY = 'circuit-breaker.service_name';
    private const HEADER_NAME = 'X-Circuit-Key';

    /**
     * @param  array<string, mixed>  $requestOptions
     * @throws InvalidArgumentException if the extracted service name is invalid
     */
    public function extract(RequestInterface $request, array $requestOptions): string
    {
        return $this->validateServiceName($this->extractRow($request, $requestOptions));
    }

    /**
     * @param  array<string, mixed>  $requestOptions
     */
    private function extractRow(RequestInterface $request, array $requestOptions): string
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

    private function validateServiceName(string $name): string
    {
        if (! preg_match('/^[a-zA-Z0-9_\-\:]+$/', $name)) {
            throw new InvalidArgumentException('Invalid service name format');
        }

        return $name;
    }
}
