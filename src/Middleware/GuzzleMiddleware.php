<?php

namespace AlgoYounes\CircuitBreaker\Middleware;

use AlgoYounes\CircuitBreaker\Guzzle\Contracts\FailureDetectorContract;
use AlgoYounes\CircuitBreaker\Guzzle\Exceptions\RejectedException;
use AlgoYounes\CircuitBreaker\Guzzle\ServiceNameExtractor;
use AlgoYounes\CircuitBreaker\Managers\CircuitManager;
use Closure;
use GuzzleHttp\Promise\Create as PromiseCreate;
use Psr\Http\Message\RequestInterface;

class GuzzleMiddleware
{
    public function __construct(
        private readonly CircuitManager $circuitManager,
        private readonly ServiceNameExtractor $serviceNameExtractor,
        private readonly FailureDetectorContract $failureDetector
    ) {}

    public function __invoke(callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $serviceName = $this->serviceNameExtractor->extract($request, $options);
            $promise = $handler($request, $options);

            if (! $this->circuitManager->isServiceAvailable($serviceName)) {
                return PromiseCreate::rejectionFor(
                    RejectedException::withServiceName($serviceName)
                );
            }

            return $promise->then(
                function ($response) use ($serviceName) {
                    if ($this->failureDetector->isFailureResponse($response)) {
                        $this->circuitManager->recordFailure($serviceName);

                        return PromiseCreate::promiseFor($response);
                    }

                    $this->circuitManager->recordSuccess($serviceName);

                    return PromiseCreate::promiseFor($response);
                },
                function ($reason) use ($serviceName) {
                    $this->circuitManager->recordFailure($serviceName);

                    return PromiseCreate::rejectionFor($reason);
                }
            );
        };
    }
}
