<?php

namespace Curacel\LangFuse\Concerns;

use Curacel\LangFuse\Exceptions\NetworkErrorException;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

trait Transportable
{
    private array $circuitBreakerState = [];

    public function __construct(private readonly PendingRequest $transporter, private readonly ?Repository $config = null) {}

    /**
     * @throws NetworkErrorException
     */
    private function send(string $method, string $url, array $options = [], string $serviceKey = 'default'): Response
    {
        if ($this->isCircuitOpen($serviceKey)) {
            throw new NetworkErrorException('Service is temporarily unavailable (circuit breaker open)');
        }

        try {
            $response = $this->transporter
                ->retry(
                    times: $this->config->get('langfuse.max_retries', 3),
                    sleepMilliseconds: function (int $attempt, \Exception $exception) {
                        return $this->calculateRetryDelay($attempt) * 1_000_000;
                    },
                    when: function (\Exception $exception, $request) use ($serviceKey) {
                        $this->recordFailure($serviceKey);

                        if ($exception instanceof \OutOfBoundsException && str_contains($exception->getMessage(), 'Mock queue is empty')) {
                            return false;
                        }

                        if ($exception instanceof ConnectionException) {
                            return true;
                        }

                        if ($exception instanceof RequestException && $exception->response) {
                            return in_array($exception->response->status(), [408, 429, 500, 502, 503, 504], true);
                        }

                        return false;
                    }
                )
                ->when(! empty($option['query']), fn (PendingRequest $client) => $client->withQueryParameters($options['query'] ?? []))
                ->withOptions($options)
                ->throw()
                ->{strtolower($method)}($url);

            $this->recordSuccess($serviceKey);

            return $response;
        } catch (\Exception $e) {
            throw new NetworkErrorException($e->getMessage());
        }
    }

    private function calculateRetryDelay(int $attempt): float
    {
        $delay = $this->config->get('langfuse.retry_delay');

        return match ($this->config->get('langfuse.retry_strategy')) {
            'exponential' => $delay * (2 ** ($attempt - 1)),
            'linear' => $delay * $attempt,
            default => $delay,
        };
    }

    private function isCircuitOpen(string $serviceKey): bool
    {
        if (! $this->config->get('langfuse.circuit_breaker_enabled')) {
            return false;
        }

        $state = $this->getCircuitBreakerState($serviceKey);

        if ($state['status'] === 'open') {
            if (time() - $state['last_failure'] >= config('langfuse.circuit_breaker_timeout')) {
                $this->circuitBreakerState[$serviceKey]['status'] = 'half-open';

                return false;
            }

            return true;
        }

        return false;
    }

    private function getCircuitBreakerState(string $serviceKey): array
    {
        $default = [
            'status' => 'closed', // closed, open, half-open
            'failure_count' => 0,
            'last_failure' => null,
        ];

        if (! isset($this->circuitBreakerState[$serviceKey])) {
            return $default;
        }

        $state = $this->circuitBreakerState[$serviceKey];

        return array_merge($default, $state);
    }

    private function recordFailure(string $serviceKey): void
    {
        if (! $this->config->get('langfuse.circuit_breaker_enabled')) {
            return;
        }

        $state = $this->getCircuitBreakerState($serviceKey);
        $state['failure_count']++;
        $state['last_failure'] = time();

        if ($state['failure_count'] >= config('langfuse.circuit_breaker_threshold')) {
            $state['status'] = 'open';
        }

        $this->circuitBreakerState[$serviceKey] = $state;
    }

    private function recordSuccess(string $serviceKey): void
    {
        if (! $this->config->get('langfuse.circuit_breaker_enabled')) {
            return;
        }

        $state = $this->getCircuitBreakerState($serviceKey);

        if ($state['status'] === 'half-open') {
            $this->circuitBreakerState[$serviceKey] = [
                'status' => 'closed',
                'failure_count' => 0,
                'last_failure' => null,
            ];
        } else {
            $this->circuitBreakerState[$serviceKey]['failure_count'] = 0;
        }
    }

    public function getCircuitBreakerStatus(): array
    {
        return $this->circuitBreakerState;
    }

    private function resetCircuitBreaker(string $serviceKey): void
    {
        $this->circuitBreakerState[$serviceKey] = [
            'status' => 'closed',
            'failure_count' => 0,
            'last_failure' => null,
        ];
    }
}
