<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Enabled/Disabled
    |--------------------------------------------------------------------------
    |
    | This option allows you to enable or disable the Circuit Breaker.
    |
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | These settings define the cache configuration used for circuit breaker.
    | TTL specifies the lifetime of the cache in seconds, and store
    | allows you to specify the cache store to be used.
    |
    */
    'cache' => [
        'ttl'    => 86400, // 1 day in seconds
        'prefix' => 'circuit-breaker',
        'store'  => 'default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Circuit Breaker Settings
    |--------------------------------------------------------------------------
    | These settings are used as defaults for all services.
    */
    'defaults' => [
        'failure_threshold' => 5,  // Number of failures before opening the circuit
        'cooldown_period'   => 60, // Time in seconds before attempting to half-open the circuit
        'success_threshold' => 1, // Number of successes required to close the circuit
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Configuration
    |--------------------------------------------------------------------------
    | These settings are used to configure the circuit breaker for specific services.
    | service_name => [settings]
    */
    'services' => [
        // Example service configuration
        /*
        'delivery_service' => [
            'failure_threshold' => 10,
            'cooldown_period' => 120,
            'success_threshold' => 5,
        ],
        */
    ],
];
