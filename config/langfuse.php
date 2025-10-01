<?php

return [

    'public_key' => env('LANGFUSE_PUBLIC_KEY'),

    'secret_key' => env('LANGFUSE_SECRET_KEY'),

    'host' => env('LANGFUSE_HOST', 'https://cloud.langfuse.com'),

    /*
    | Setting "false" the package stop sending data to langfuse.
    */
    'enabled' => env('LANGFUSE_ENABLED', true),

    /*
     | When set to true, an exception will be thrown when the last attempt fails
     */
    'throw_exception_on_failure' => false,

    'circuit_breaker_enabled' => env('LANGFUSE_ENABLE_CIRCUIT_BREAKER', true),

    'circuit_breaker_threshold' => env('LANGFUSE_CIRCUIT_BREAKER_THRESHOLD', 5),

    'circuit_breaker_timeout' => env('LANGFUSE_CIRCUIT_BREAKER_TIMEOUT', 60),

    'max_retries' => env('LANGFUSE_MAX_RETRIES', 3),

    'retry_delay' => env('LANGFUSE_RETRY_DELAY', 1),
];
