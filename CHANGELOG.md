# Changelog

All notable changes to `laravel circuit breaker` will be documented in this file

## v1.0.3 - 2025-02-17

### What's Changed

* refactor > improve circuit breaker handler
* add .gitattributes + remove compose.lock

**Full Changelog**: https://github.com/algoyounes/circuit-breaker/compare/v1.0.2...v1.0.3

## v1.0.2 - 2025-02-07

### What's Changed

* Service Provider > Simplified getConfig method by removing an unnecessary parameter
* Facade > Added PHP-doc annotations for service methods
* Service Provider > Streamlined default cache store check

**Full Changelog**: https://github.com/algoyounes/circuit-breaker/compare/v1.0.0...v1.0.2

## v1.0.0 - 2025-01-27

🎉 **Circuit Breaker v1.0.0** is here!

The first stable release of **Circuit Breaker v1.0.0** is now available, bringing advanced failure management to your applications.

### What's Changed

* **Attach Callbacks**: Hook into circuit state changes *(Open, Half-Open, Closed, Success, Steady-State)*
* **Chainable Syntax:** Use a fluent, chainable syntax for defining complex workflows
* **Multi-Service Management:** Handle circuits for multiple services at once
* **Rich Execution Results**: Access detailed status metadata for each operation
* **Configurable Thresholds**: Fine-tune failure windows, success thresholds, and cooldown periods

**Full Changelog**: https://github.com/algoyounes/circuit-breaker/commits/v1.0.0
