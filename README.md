<p align="center">
<img style="border-radius: 5px; max-width: 100%;" src="assets/logo.png" alt="Circuit Breaker Logo"/>
</p>
<p align="center">
<a href="https://github.com/algoyounes/circuit-breaker/actions"><img src="https://github.com/algoyounes/circuit-breaker/actions/workflows/unit-tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/algoyounes/circuit-breaker"><img src="https://img.shields.io/packagist/dt/algoyounes/circuit-breaker" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/algoyounes/circuit-breaker"><img src="https://img.shields.io/packagist/v/algoyounes/circuit-breaker" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/algoyounes/circuit-breaker"><img src="https://img.shields.io/packagist/l/algoyounes/circuit-breaker" alt="License"></a>
</p>

**Circuit Breaker** is a Laravel package that provides a simple and efficient way to manage service calls and prevent cascading failures. 
It lets you define custom callbacks for key circuit states and run operations with circuit breaker logic.

The following diagram illustrates how the **Circuit Breaker Pattern** works:

![circuit-breaker.png](assets/circuit-breaker.png)

For more info, check the official pattern doc [here](https://learn.microsoft.com/en-us/azure/architecture/patterns/circuit-breaker).

## Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Usage](#usage)
  - [Custom Callbacks](#custom-callbacks)
  - [Running an Operation](#running-an-operation)
  - [Guzzle Middleware Integration](#guzzle-middleware-integration)
  - [Laravel Http Facade Integration](#laravel-http-facade-integration)
- [How It Works](#how-it-works)
  - [State Transitions](#state-transitions)
  - [Half-Open State Behavior](#half-open-state-behavior)
- [Configuration](#configuration)
- [Contributing](#contributing)
- [License](#license)

## Prerequisites

This package requires:
- **PHP 8.2+**
- **Laravel 11+**
- **A configured Laravel cache driver**

## Installation

You can install the package via Composer:

```bash
composer require algoyounes/circuit-breaker
```

You can publish the configuration file using the following command:

```bash
php artisan vendor:publish --provider="AlgoYounes\CircuitBreaker\Providers\CircuitBreakerServiceProvider" --tag="config"
```

## Usage

You can manage specific services with granular control using the `forService(...)` method. the `service-name` parameter is a unique identifier key for your service, ensuring its circuit breaker configuration is isolated from other services.
```php
$circuit = $this->circuitManager->forService('service-name');
```

> [!TIP]
> Use the unique `service-name` key across your application to consistently reference the same circuit configuration _(e.g., 'payment-service', ...)_

### Custom Callbacks
You can define callbacks for key circuit states:

| Callback        | Description                                                                               | Parameters Received                  |
|-----------------|-------------------------------------------------------------------------------------------|--------------------------------------| 
| `onOpen`        | Triggered when the circuit goes into **OPEN**, blocking calls to prevent further failures | `CircuitTransition`                  |
| `onHalfOpen`    | The circuit enters **HALF-OPEN** to test stability, letting one request through           | `CircuitTransition`                  |
| `onClose`       | The circuit returns to **CLOSED**, allowing all requests without restrictions             | `CircuitTransition`                  |
| `onSuccess`     | Fires when a request succeeds, indicating system availability                             | `CircuitResult`, `CircuitTransition` |
| `onFailure`     | Triggered when a request fails, potentially opening the circuit                           | `CircuitResult`, `CircuitTransition` |
| `onSteadyState` | Indicates the circuit is stable, with no need for changes                                 | `CircuitTransition`                  |

Example of defining callbacks:

```php

$circuit->onOpen(function (CircuitTransition $circuitTransition) { 
    // Your custom logic here
});

$circuit->onSuccess(function (CircuitResult $circuitResult, CircuitTransition $circuitTransition) {
    // Your custom logic here
});

// Params passed are optional
```

### Running an Operation

To run an operation and manage its state through the circuit breaker, use the `run` method:

```php
$circuit->run(function () {
    // Your service call here
});
// or
$circuit->run($this->serviceName->create(...));
```
This will execute the provided closure, applying the circuit breaker logic _(e.g., open, half-open, closed states)_ around the service call.

> [!NOTE]
> If you prefer a more direct approach, you can create a `CircuitBuilder` instance:
> ```php
> $circuit = CircuitBuilder::make('service-name')
> ```

#### Simplified Operation

For a simplified approach, use the `run` method directly from `CircuitManager`:

```php
$this->circuitManager->run('service-name', function () {
    // Your service call here
});
// or
$this->circuitManager->run('service-name', $this->serviceName->create(...));
```

### Guzzle Middleware Integration

The package provides a Guzzle middleware to automatically manage circuit breaker logic for HTTP requests.

To enable the middleware, add the following to your Guzzle client configuration:

```php
use AlgoYounes\CircuitBreaker\Middleware\GuzzleMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

$stack = HandlerStack::create();
$stack->push(GuzzleMiddleware::create());

$client = new Client([
    'handler' => $stack,
]);

$response = $client->get('https://api.example.com', [
    'headers' => [
        'X-Circuit-Key' => 'service-name',
    ],
]);
```

### Laravel Http Facade Integration

The package integrates with Laravel's built-in `Http` facade out of the box:

```php
use Illuminate\Support\Facades\Http;

$response = Http::withCircuitBreaker('payment-service')
    ->get('https://api.payment.com/charge', [
        'amount' => 1000,
    ]);
```

This automatically applies the circuit breaker middleware to the request using `payment-service` as the circuit key. You can chain it with any `Http` method:

```php
$response = Http::withCircuitBreaker('shipping-service')
    ->withToken($apiToken)
    ->timeout(10)
    ->post('https://api.example.com/track', $payload);
```

When the circuit is open, a `RejectedException` is thrown:

```php
use AlgoYounes\CircuitBreaker\Guzzle\Exceptions\RejectedException;

try {
    $response = Http::withCircuitBreaker('payment-service')
        ->get('https://api.example.com/charge');
} catch (RejectedException $e) {
    // Circuit is open — handle gracefully (e.g., return cached response, queue for retry)
}
```

## How It Works

### State Transitions

The circuit breaker operates in three states:

```
    ┌──────────────────────────────────────────────────────┐
    │                                                      │
    ▼                                                      │
 CLOSED ──── failures ≥ threshold ────► OPEN               │
 (normal)                               (all requests      │
                                         rejected)         │
                                           │               │
                                    cooldown expires       │
                                           │               │
                                           ▼               │
                                       HALF-OPEN           │
                                      (single probe)       │
                                        │       │          │
                                   success    failure      │
                                        │       │          │
                                        │       └──► OPEN  │
                                        │                  │
                                        └──────────────────┘
```

- **CLOSED** — Normal operation. All requests pass through. Failures are counted.
- **OPEN** — The service is considered down. All requests are rejected immediately without calling the service. After the `cooldown_period` expires, the circuit moves to HALF-OPEN.
- **HALF-OPEN** — A single probe request is sent to test the service. If it succeeds, the circuit closes. If it fails, the circuit re-opens.

### Half-Open State Behavior

When a circuit transitions from **OPEN** to **HALF-OPEN**, this package uses a **single-probe** approach — only one request is allowed to test the recovering service at a time. All other concurrent requests are rejected immediately until the probe completes.

```
OPEN (cooldown expired)
  │
  ├── Request A → probes service → success → CLOSED (all traffic resumes)
  ├── Request B → rejected (fail-fast)
  ├── Request C → rejected (fail-fast)
  └── ...
```

- If the probe **succeeds**, the circuit closes and normal traffic resumes.
- If the probe **fails**, the circuit re-opens and a new cooldown period begins.

**What your code receives when a request is rejected:**

- Via `run()` — returns a `CircuitResult` where `isSuccess()` and `isAvailable()` both return `false`
- Via `Http::withCircuitBreaker()` or Guzzle middleware — throws `RejectedException`

## Configuration

After publishing the config file, you can adjust these settings in `config/circuit-breaker.php`:

| Option | Default | Description |
|--------|---------|-------------|
| `enabled` | `true` | Enable or disable the circuit breaker globally |
| `defaults.failure_threshold` | `5` | Number of failures before the circuit opens |
| `defaults.cooldown_period` | `60` | Seconds to wait before transitioning from OPEN to HALF-OPEN |
| `defaults.success_threshold` | `1` | Successful probes needed in HALF-OPEN to close the circuit |
| `cache.ttl` | `86400` | Cache entry lifetime in seconds |
| `cache.prefix` | `circuit-breaker` | Prefix for cache keys |
| `cache.store` | `default` | Laravel cache store to use |

You can also override settings per service:

```php
'services' => [
    'payment-service' => [
        'failure_threshold' => 10,
        'cooldown_period'   => 120,
        'success_threshold' => 3,
    ],
],
```

## Contributing

Thank you for considering contributing to the Circuit Breaker package! Please check the [CONTRIBUTING](CONTRIBUTING.md) file for more details.

## License

The Circuit Breaker package is open-sourced software licensed under the [MIT license](LICENSE).
