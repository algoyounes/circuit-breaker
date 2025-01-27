<p align="center">
<img style="border-radius: 5px; max-width: 100%;" src="assets/logo.jpeg" alt="Circuit Breaker Logo"/>
</p>
<p align="center">
<a href="https://github.com/algoyounes/circuit-breaker/actions"><img src="https://github.com/algoyounes/circuit-breaker/actions/workflows/unit-tests.yml/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/algoyounes/circuit-breaker"><img src="https://img.shields.io/packagist/dt/algoyounes/circuit-breaker" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/algoyounes/circuit-breaker"><img src="https://img.shields.io/packagist/v/algoyounes/circuit-breaker" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/algoyounes/circuit-breaker"><img src="https://img.shields.io/packagist/l/algoyounes/circuit-breaker" alt="License"></a>
</p>

Circuit Breaker is a Laravel package that provides a simple and efficient way to manage service calls and prevent cascading failures. 
It allows you to define custom callbacks for key circuit states and run operations with circuit breaker logic.

The following diagram illustrates how the Circuit Breaker pattern works:

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

You can manage specific services with granular control using the `forService(...)` method:
```php
$circuit = $this->circuitManager->forService('service-name');
```

### Custom Callbacks
You can define callbacks for key circuit states:

- `onOpen`: Triggered when the circuit goes into **OPEN**, blocking calls to prevent further failures
- `onHalfOpen`: The circuit enters **HALF-OPEN** to test if things are stable again, letting a few requests through
- `onClose`: The circuit returns to **CLOSED**, allowing all requests to pass through without restrictions
- `onSuccess`: Fires when a request succeeds, indicating the system is available
- `onFailure`: Triggered when a request fails, which may cause the circuit to open and block further requests
- `onSteadyState`: Indicates the circuit is stable, with no need for changes

Example of defining callbacks:

```php

$circuit->onOpen(function () {
    // Your custom logic here
});

$circuit->onSuccess(function () {
    // Your custom logic here
});
```

### Running an Operation

To run an operation and manage its state through the circuit breaker, use the `run` method:

```php
$circuit->run(function () {
    // Your service call here
});
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
```

