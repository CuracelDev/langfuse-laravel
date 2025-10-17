<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Langfuse Public Key
    |--------------------------------------------------------------------------
    |
    | The public API key used to authenticate with Langfuse.
    | This is safe to share in client-side environments if necessary.
    |
    */
    'public_key' => env('LANGFUSE_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Langfuse Secret Key
    |--------------------------------------------------------------------------
    |
    | The secret API key for authenticating requests from your server to Langfuse.
    |
    */
    'secret_key' => env('LANGFUSE_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Langfuse Host URL
    |--------------------------------------------------------------------------
    |
    | The base URL for your Langfuse instance. By default, it points to
    | the Langfuse Cloud instance. If you self-host Langfuse, update this
    | to your own server URL.
    |
    | Example:
    |   'https://cloud.langfuse.com' (default)
    |   'https://langfuse.yourcompany.com'
    |
    */
    'host' => env('LANGFUSE_HOST', 'https://cloud.langfuse.com'),

    /*
    |--------------------------------------------------------------------------
    | Enable or Disable Langfuse
    |--------------------------------------------------------------------------
    |
    | Set this to `false` to disable all Langfuse instrumentation and stop
    | sending any trace or telemetry data. Useful for local or testing
    | environments.
    |
    */
    'enabled' => env('LANGFUSE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Exception on Failure
    |--------------------------------------------------------------------------
    |
    | When set to `true`, the package will throw an exception if the final
    | retry attempt to send data to Langfuse fails.
    | When `false`, failures will be silently ignored.
    |
    */
    'throw_exception_on_failure' => false,

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | The circuit breaker prevents repeated failed requests from overwhelming
    | your application or the Langfuse API.
    |
    | - 'circuit_breaker_enabled': Enables/disables circuit breaker.
    | - 'circuit_breaker_threshold': Number of consecutive failures before
    |   the circuit opens and stops sending requests.
    | - 'circuit_breaker_timeout': Number of seconds before retrying
    |   requests after the circuit opens.
    |
    */
    'circuit_breaker_enabled' => env('LANGFUSE_ENABLE_CIRCUIT_BREAKER', true),

    'circuit_breaker_threshold' => env('LANGFUSE_CIRCUIT_BREAKER_THRESHOLD', 5),

    'circuit_breaker_timeout' => env('LANGFUSE_CIRCUIT_BREAKER_TIMEOUT', 60),

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Configure how the client retries failed requests to Langfuse.
    |
    | - 'max_retries': Maximum number of retry attempts for failed API calls.
    | - 'retry_delay': Delay (in seconds) between each retry attempt.
    |
    */
    'max_retries' => env('LANGFUSE_MAX_RETRIES', 3),

    'retry_delay' => env('LANGFUSE_RETRY_DELAY', 1),
];
