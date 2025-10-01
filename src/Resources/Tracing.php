<?php

declare(strict_types=1);

namespace Curacel\LangFuse\Resources;

use Curacel\LangFuse\Concerns\Transportable;
use Curacel\LangFuse\Contracts\ObservationContract;
use Curacel\LangFuse\DTO\TraceConfig;
use Curacel\LangFuse\Exceptions\NetworkErrorException;
use Curacel\LangFuse\Exceptions\TraceException;
use Curacel\LangFuse\Resources\Observability\Event;
use Curacel\LangFuse\Resources\Observability\Generation;
use Curacel\LangFuse\Resources\Observability\Span;
use Curacel\LangFuse\Resources\Observability\Trace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

final class Tracing
{
    use Transportable;

    private ?Trace $activeTrace = null;

    /** @var Trace[] */
    private array $traces = [];

    public function trace(TraceConfig $config): Trace
    {
        $trace = new Trace(
            name: $config->name,
            userId: $config->userId,
            sessionId: $config->sessionId,
            metadata: $config->metadata->toArray(),
            tags: $config->tags->toArray(),
            version: $config->version,
            release: $config->release,
            input: $config->input,
            output: $config->output
        );

        $this->traces[] = $trace;
        $this->activeTrace = $trace;

        return $trace;
    }

    /**
     * @throws TraceException
     */
    private function ensureActiveTrace(): void
    {
        if ($this->activeTrace === null) {
            throw new TraceException(
                'No active trace. Create a trace using trace() before creating observations.'
            );
        }
    }

    /**
     * @throws TraceException
     */
    public function setActiveTrace(string $traceId): void
    {
        foreach ($this->traces as $trace) {
            if ($trace->getId() === $traceId) {
                $this->activeTrace = $trace;

                return;
            }
        }

        throw new TraceException("Trace with ID {$traceId} not found.");
    }

    /**
     * @throws TraceException
     */
    public function event(string $name, ?array $metadata = null, mixed $input = null): Event
    {
        $this->ensureActiveTrace();

        return $this->activeTrace->event($name, 'DEFAULT', $metadata, $input);
    }

    /**
     * Starts a generation in the active trace
     *
     * @throws TraceException
     */
    public function generation(
        string $name,
        string $model,
        ?array $modelParameters = null,
        ?array $metadata = null,
        mixed $input = null
    ): Generation {
        $this->ensureActiveTrace();

        return $this->activeTrace->generation(
            $name,
            $model,
            $modelParameters,
            $metadata,
            $input
        );
    }

    /**
     * Starts a span in the active trace
     *
     * @throws TraceException
     */
    public function span(
        string $name,
        ?array $metadata = null,
        mixed $input = null
    ): Span {
        $this->ensureActiveTrace();

        return $this->activeTrace->span($name, $metadata, $input);
    }

    /**
     * Records a score in the active trace
     *
     * @throws TraceException
     */
    public function score(
        string $name,
        float|int $value,
        ?string $comment = null,
        ?ObservationContract $observation = null
    ): void {
        $this->ensureActiveTrace();
        $this->activeTrace->score($name, $value, $comment, $observation);
    }

    /**
     * @throws TraceException
     * @throws NetworkErrorException
     */
    public function syncTraces(bool $async = false): void
    {
        if (! $this->config->get('langfuse.enabled', true)) {
            return;
        }

        if (empty($this->traces)) {
            return;
        }

        $batch = collect($this->traces)->flatMap(function (Trace $trace) {
            if (! $trace->getEndTime()) {
                $trace->end();
            }

            $items = collect();

            $items->push([
                'id' => $trace->getId(),
                'timestamp' => $trace->getStartTime(),
                'type' => 'trace-create',
                'body' => [
                    'id' => $trace->getId(),
                    'name' => $trace->getName(),
                    'userId' => $trace->getUserId(),
                    'sessionId' => $trace->getSessionId(),
                    'input' => $trace->getInput(),
                    'output' => $trace->getOutput(),
                    'metadata' => $trace->getMetadata(),
                    'tags' => $trace->getTags(),
                    'version' => $trace->getVersion(),
                    'environment' => $this->config->get('app.env'),
                    'release' => $trace->getRelease(),
                    'startTime' => $trace->getStartTime(),
                    'endTime' => $trace->getEndTime(),
                ],
            ]);

            // Observations (flatten recursively)
            $observations = $this->flattenObservations($trace->getObservations());

            foreach ($observations as $obs) {
                $items->push([
                    'id' => $obs->getId(),
                    'timestamp' => $obs->getStartTime(),
                    'type' => $obs->getType().'-create',
                    'body' => $obs->toArray(),
                ]);
            }

            foreach ($trace->getScores() as $score) {
                $items->push([
                    'id' => $score['id'],
                    'timestamp' => $score['timestamp'],
                    'type' => 'score-create',
                    'body' => $score,
                ]);
            }

            return $items;
        });

        $batch = $batch->all();
        $async ?
            dispatch(function () use ($batch) {
                $this->ingestTrace($batch);
            })->afterResponse() : $this->ingestTrace($batch);
    }

    /**
     * @throws NetworkErrorException
     * @throws TraceException
     */
    protected function ingestTrace(array $batch): void
    {
        try {
            $response = $this->send(
                method: 'post',
                url: '/api/public/ingestion',
                options: ['json' => ['batch' => $batch]],
                serviceKey: 'langfuse-ingestion'
            );

            if ($response->status() === 207 && ! empty($errors = $response->json('errors', []))) {
                $this->config->get('langfuse.throw_exception_on_failure', false)
                    ? throw new TraceException('Batch ingestion contained errors')
                    : Log::error('Batch ingestion contained errors', ['errors' => $errors]);
            }
        } catch (\Exception $e) {
            if ($this->config->get('langfuse.throw_exception_on_failure', false)) {
                throw $e instanceof NetworkErrorException
                    ? $e
                    : new TraceException('Unexpected error sending langfuse event: '.$e->getMessage());
            }

            Log::error('Unexpected error sending langfuse event: '.$e->getMessage());
        }
    }

    protected function flattenObservations(iterable $observations): Collection
    {
        return collect($observations)->flatMap(function ($obs) {
            return collect([$obs])->merge(
                method_exists($obs, 'getObservations')
                    ? $this->flattenObservations($obs->getObservations())
                    : []
            );
        });
    }

    /**
     * @throws TraceException
     */
    public function getTraceId(): string
    {
        $this->ensureActiveTrace();

        return $this->activeTrace->getId();
    }
}
