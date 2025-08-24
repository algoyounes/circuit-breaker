<?php

namespace AlgoYounes\CircuitBreaker\Guzzle\Exceptions;

class RejectedException extends \RuntimeException
{
    public function __construct(
        private readonly string $serviceName,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function withServiceName(string $serviceName): self
    {
        return new self($serviceName, sprintf('"%s" is not available', $serviceName));
    }

    public function serviceName(): string
    {
        return $this->serviceName;
    }
}
