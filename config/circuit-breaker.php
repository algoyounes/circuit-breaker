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
        'ttl'   => 86400, // 1 day in seconds
        'store' => 'default',
    ],

    // default config for all service
    'default' => [

    ],

    // TODO: add custom config per service
];
