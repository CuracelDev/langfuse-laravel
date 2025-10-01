<?php

declare(strict_types=1);

namespace Curacel\LangFuse\Resources\Observability;

use Curacel\LangFuse\Contracts\ObservationContract;

class Span extends AbstractObservability
{
    protected string $type = 'span';

    protected array $observations = [];

    public function __construct(
        string $traceId,
        string $name,
        ?array $metadata = null,
        mixed $input = null
    ) {
        parent::__construct($traceId, $name, $metadata, $input);
    }

    /**
     * Creates a nested generation within this span
     */
    public function generation(
        string $name,
        string $model,
        ?array $modelParameters = null,
        ?array $metadata = null,
        mixed $input = null
    ): Generation {
        $generation = new Generation(
            $this->traceId,
            $name,
            $model,
            $modelParameters,
            $metadata,
            $input
        );

        // Set this span as the parent
        $generation->setParentObservationId($this->id);
        $this->observations[] = $generation;

        return $generation;
    }

    /**
     * Creates a nested span within this span
     */
    public function createSpan(
        string $name,
        ?array $metadata = null,
        mixed $input = null
    ): Span {
        $span = new Span(
            $this->traceId,
            $name,
            $metadata,
            $input
        );

        // Set this span as the parent
        $span->setParentObservationId($this->id);
        $this->observations[] = $span;

        return $span;
    }

    /**
     * Creates a nested event within this span
     */
    public function event(
        string $name,
        string $level = 'DEFAULT',
        ?array $metadata = null,
        mixed $input = null,
        ?string $statusMessage = null
    ): Event {
        $event = new Event(
            $this->traceId,
            $name,
            $level,
            $metadata,
            $input,
            $statusMessage
        );
        // Set this span as the parent
        $event->setParentObservationId($this->id);
        $this->observations[] = $event;

        return $event;
    }

    /**
     * Returns all nested observations within this span
     *
     * @return array<ObservationContract>
     */
    public function getObservations(): array
    {
        return $this->observations;
    }

    /**
     * Ends this span (optionally updating data) and ensures:
     * 1) Any unended child observations are ended.
     * 2) If a child ended after this span's endTime, update the span's endTime accordingly.
     */
    public function end(?array $data = null): static
    {
        foreach ($this->observations as $obs) {
            if ($obs->getEndTime() === null) {
                $obs->end();
            }
        }

        parent::end($data);

        return $this;
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        if (! empty($this->observations)) {
            $data['observations'] = array_map(
                static fn (ObservationContract $obs) => $obs->toArray(),
                $this->observations
            );
        }

        return $data;
    }

    protected function maybeExtendParentEndTime(): void
    {
        // If this span has an end time, see if any child's end time goes beyond it.
        if ($this->endTime === null) {
            return;
        }
        foreach ($this->observations as $child) {
            if ($child->getEndTime() !== null && $child->getEndTime() > $this->endTime) {
                $this->endTime = $child->getEndTime();
            }
        }
    }
}
