<?php

namespace Curacel\LangFuse\Contracts;

use Curacel\LangFuse\Resources\Observability\Event;
use Curacel\LangFuse\Resources\Observability\Generation;
use Curacel\LangFuse\Resources\Observability\Span;

interface TraceContract
{
    /**
     * Gets the trace identifier
     */
    public function getId(): string;

    /**
     * Gets the trace name
     */
    public function getName(): string;

    /**
     * Gets the trace start time
     */
    public function getStartTime(): string;

    /**
     * Gets the trace end time
     */
    public function getEndTime(): ?string;

    /**
     * Gets the user identifier
     */
    public function getUserId(): ?string;

    /**
     * Gets the session identifier
     */
    public function getSessionId(): ?string;

    /**
     * Gets the trace metadata
     */
    public function getMetadata(): ?array;

    /**
     * Gets the trace tags
     */
    public function getTags(): array;

    /**
     * Gets the trace version
     */
    public function getVersion(): ?string;

    /**
     * Gets the trace release
     */
    public function getRelease(): ?string;

    /**
     * Gets the trace input
     */
    public function getInput(): mixed;

    /**
     * Gets the trace output
     */
    public function getOutput(): mixed;

    /**
     * Gets all observations within this trace
     *
     * @return array<ObservationContract>
     */
    public function getObservations(): array;

    /**
     * Gets all scores within this trace
     */
    public function getScores(): array;

    public function event(
        string $name,
        string $level = 'DEFAULT',
        ?array $metadata = null,
        mixed $input = null,
        ?string $statusMessage = null
    ): Event;

    public function span(
        string $name,
        ?array $metadata = null,
        mixed $input = null
    ): Span;

    public function generation(
        string $name,
        string $model,
        ?array $modelParameters = null,
        ?array $metadata = null,
        mixed $input = null
    ): Generation;

    public function score(
        string $name,
        float|int $value,
        ?string $comment = null,
        ?ObservationContract $observation = null
    ): self;

    public function update(
        mixed $input = null,
        mixed $output = null,
        ?array $metadata = null
    ): self;

    public function mergeMetadata(array $metadata): self;

    public function end(?array $data = null): self;
}
