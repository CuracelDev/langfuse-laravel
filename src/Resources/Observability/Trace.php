<?php

declare(strict_types=1);

namespace Curacel\LangFuse\Resources\Observability;

use Curacel\LangFuse\Contracts\ObservationContract;
use Curacel\LangFuse\Contracts\TraceContract;
use Illuminate\Support\Str;

/**
 * Class Trace
 *
 * Acts as a container for all observations (events, spans, generations) within
 * a single execution flow. Implements an accumulator pattern to collect all
 * observations before sending them to the API in a single batch.
 *
 * Example usage:
 * ```php
 * $trace = new Trace("vehicle-inspection-workflow");
 *
 * $retrievalSpan = $trace->span("retrieval");
 * $retrievalSpan->generation("natural-language", "gpt-5");
 * $retrievalSpan->end(['output' => $results]);
 *
 * $trace->sync(); // Sends all accumulated observations
 * ```
 */
class Trace implements TraceContract
{
    private string $id;

    private string $startTime;

    private ?string $endTime = null;

    private array $observations = [];

    private array $scores = [];

    //    private bool $isSynced = false;

    /**
     * Creates a new trace
     *
     * @param  string  $name  Human-readable identifier for this trace
     * @param  string|null  $userId  The id of the user that triggered the execution.
     * @param  string|null  $sessionId  Optional session identifier for grouping related traces
     * @param  array|null  $metadata  Additional contextual information
     * @param  array  $tags  Tags for filtering and organization
     * @param  string|null  $version  Version identifier for the trace
     * @param  string|null  $release  Release/deployment identifier
     * @param  mixed  $input  Input data for the trace
     * @param  mixed  $output  Output data for the trace
     */
    public function __construct(
        public string $name,
        public ?string $userId = null,
        public ?string $sessionId = null,
        public ?array $metadata = null,
        public array $tags = [],
        public ?string $version = null,
        public ?string $release = null,
        public mixed $input = null,
        public mixed $output = null
    ) {
        $this->id = Str::orderedUuid()->toString();
        $this->startTime = (new \DateTime)->format('Y-m-d\TH:i:s.vP');
    }

    /**
     * Creates a new event within this trace
     */
    public function event(
        string $name,
        string $level = 'DEFAULT',
        ?array $metadata = null,
        mixed $input = null,
        ?string $statusMessage = null
    ): Event {
        $event = new Event(
            traceId: $this->id,
            name: $name,
            level: $level,
            metadata: $metadata,
            input: $input,
            statusMessage: $statusMessage
        );

        $this->observations[] = $event;

        return $event;
    }

    /**
     * Creates a new span within this trace
     */
    public function span(
        string $name,
        ?array $metadata = null,
        mixed $input = null
    ): Span {
        $span = new Span(
            $this->id,
            $name,
            $metadata,
            $input
        );
        $this->observations[] = $span;

        return $span;
    }

    /**
     * Creates a new generation within this trace
     */
    public function generation(
        string $name,
        string $model,
        ?array $modelParameters = null,
        ?array $metadata = null,
        mixed $input = null
    ): Generation {
        $generation = new Generation(
            $this->id,
            $name,
            $model,
            $modelParameters,
            $metadata,
            $input
        );

        $this->observations[] = $generation;

        return $generation;
    }

    /**
     * Records a score within this trace
     */
    public function score(
        string $name,
        float|int $value,
        ?string $comment = null,
        ?ObservationContract $observation = null
    ): self {
        $scoreData = [
            'id' => Str::orderedUuid()->toString(),
            'traceId' => $this->id,
            'name' => $name,
            'value' => $value,
            'comment' => $comment,
            'timestamp' => (new \DateTime)->format('Y-m-d\TH:i:s.vP'),
        ];

        if ($observation !== null) {
            $scoreData['observationId'] = $observation->getId();
        }

        // Store score for later batch sending (consistent with accumulation pattern)
        $this->scores[] = $scoreData;

        return $this;
    }

    /**
     * Explicitly end this trace, setting endTime if not already set,
     * and also ending all unended child observations.
     * If a child ended later, this trace's endTime is updated to reflect reality.
     */
    public function end(?array $data = null): self
    {
        // Process end data if provided
        if ($data !== null) {
            $this->update(
                $data['input'] ?? null,
                $data['output'] ?? null,
                $data['metadata'] ?? null
            );
        }

        if ($this->endTime === null) {
            $this->endTime = (new \DateTime)->format('Y-m-d\TH:i:s.vP');
        }

        foreach ($this->observations as $obs) {
            if (! $obs->getEndTime()) {
                $obs->end();
            }
            if ($obs->getEndTime() && $obs->getEndTime() > $this->endTime) {
                $this->endTime = $obs->getEndTime();
            }
        }

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function mergeMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata ?? [], $metadata);

        return $this;
    }

    /**
     * Updates trace with input, output, and/or metadata
     */
    public function update(
        mixed $input = null,
        mixed $output = null,
        ?array $metadata = null
    ): self {
        if ($input !== null) {
            $this->input = $input;
        }
        if ($output !== null) {
            $this->output = $output;
        }
        if ($metadata !== null) {
            $this->mergeMetadata($metadata);
        }

        return $this;
    }

    public function getTraceId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getRelease(): ?string
    {
        return $this->release;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function getObservations(): array
    {
        return $this->observations;
    }

    public function getScores(): array
    {
        return $this->scores;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function getInput(): mixed
    {
        return $this->input;
    }

    public function getOutput(): mixed
    {
        return $this->output;
    }
}
