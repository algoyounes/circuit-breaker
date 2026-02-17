<?php

namespace AlgoYounes\CircuitBreaker\Middleware;

use AlgoYounes\CircuitBreaker\Contracts\StateManagerContract;
use AlgoYounes\CircuitBreaker\Guzzle\Contracts\FailureDetectorContract;
use AlgoYounes\CircuitBreaker\Guzzle\Contracts\ServiceNameExtractorContract;
use AlgoYounes\CircuitBreaker\Guzzle\DefaultFailureDetector;
use AlgoYounes\CircuitBreaker\Guzzle\Exceptions\RejectedException;
use AlgoYounes\CircuitBreaker\Guzzle\ServiceNameExtractor;
use AlgoYounes\CircuitBreaker\Managers\CircuitManager;
use Closure;
use GuzzleHttp\Promise\Create as PromiseCreate;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleMiddleware
{
    private readonly CircuitManager $circuitManager;
    private readonly StateManagerContract $stateManager;
    private readonly ServiceNameExtractorContract $serviceNameExtractor;
    private readonly FailureDetectorContract $failureDetector;

    public function __construct(
        ?ServiceNameExtractorContract $serviceNameExtractor = null,
        ?FailureDetectorContract $failureDetector = null
    ) {
        $this->circuitManager = app(CircuitManager::class);
        $this->stateManager = app(StateManagerContract::class);
        $this->serviceNameExtractor = $serviceNameExtractor ?: new ServiceNameExtractor;
        $this->failureDetector = $failureDetector ?: new DefaultFailureDetector;
    }

    public static function create(
        ?ServiceNameExtractorContract $serviceNameExtractor = null,
        ?FailureDetectorContract $failureDetector = null
    ): self {
        return new self($serviceNameExtractor, $failureDetector);
    }

    /**
     * @param  callable(RequestInterface, array<string, mixed>): PromiseInterface  $handler
     *
     * @return Closure(RequestInterface, array<string, mixed>): PromiseInterface
     */
    public function __invoke(callable $handler): Closure
    {
        /**
         * @param  RequestInterface  $request
         * @param  array<string, mixed>  $options
         *
         * @return PromiseInterface
         */
        return function (RequestInterface $request, array $options) use ($handler): PromiseInterface {
            /** @var array<string,mixed> $options */
            $serviceName = $this->serviceNameExtractor->extract($request, $options);

            if (! $this->circuitManager->isAvailable($serviceName)) {
                return PromiseCreate::rejectionFor(
                    RejectedException::withServiceName($serviceName)
                );
            }

            $promise = $handler($request, $options);

            return $promise->then(
                function (ResponseInterface $response) use ($serviceName): PromiseInterface {
                    if ($this->failureDetector->isFailureResponse($response)) {
                        $this->circuitManager->recordFailure($serviceName);

                        if ($this->stateManager->hasExceededThreshold($serviceName)) {
                            $this->stateManager->open($serviceName);
                        }

                        return PromiseCreate::promiseFor($response);
                    }

                    $this->circuitManager->recordSuccess($serviceName);

                    return PromiseCreate::promiseFor($response);
                },
                function (mixed $reason) use ($serviceName): PromiseInterface {
                    $this->circuitManager->recordFailure($serviceName);

                    // Check if threshold exceeded, if so, open the circuit
                    if ($this->stateManager->hasExceededThreshold($serviceName)) {
                        $this->stateManager->open($serviceName);
                    }

                    return PromiseCreate::rejectionFor($reason);
                }
            );
        };
    }
}
