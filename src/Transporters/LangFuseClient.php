<?php

declare(strict_types=1);

namespace Curacel\LangFuse\Transporters;

use Curacel\LangFuse\Contracts\LangFuseClientContract;
use Curacel\LangFuse\DTO\TraceConfig;
use Curacel\LangFuse\Exceptions\NetworkErrorException;
use Curacel\LangFuse\Exceptions\TraceException;
use Curacel\LangFuse\Resources\Observability\Trace;
use Curacel\LangFuse\Resources\Prompt;
use Curacel\LangFuse\Resources\Tracing;
use Illuminate\Config\Repository;
use Illuminate\Http\Client\PendingRequest;

final class LangFuseClient implements LangFuseClientContract
{
    public function __construct(
        private readonly PendingRequest $transporter,
        private readonly ?Repository $config = null
    ) {}

    public function tracing(): Tracing
    {
        return new Tracing($this->transporter, $this->config);
    }

    public function trace(string|TraceConfig $config): Trace
    {
        $config = $config instanceof TraceConfig ? $config : new TraceConfig($config);

        return $this->tracing()->trace($config);
    }

    public function prompt(): Prompt
    {
        return new Prompt($this->transporter, $this->config);
    }

    /**
     * Flush pending events with optional request overrides.
     *
     * @throws TraceException
     */
    public function flush(): void
    {
        $this->syncTraces();
    }

    /**
     * Syncs all accumulated traces with optional request overrides
     *
     * @throws TraceException|NetworkErrorException
     */
    public function syncTraces(): void
    {
        $this->tracing()->syncTraces();
    }

    public function request(): PendingRequest
    {
        return $this->transporter;
    }
}
