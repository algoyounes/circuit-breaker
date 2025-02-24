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

> [!NOTE]
> This package requires PHP 8.2+ and Laravel 11+ 

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
| `onHalfOpen`    | The circuit enters **HALF-OPEN** to test stability, letting a few requests through        | `CircuitTransition`                  |
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

## Contributing

Thank you for considering contributing to the Circuit Breaker package! Please check the [CONTRIBUTING](CONTRIBUTING.md) file for more details.

## License

The Circuit Breaker package is open-sourced software licensed under the [MIT license](LICENSE).
