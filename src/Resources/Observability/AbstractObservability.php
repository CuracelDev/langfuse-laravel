<?php

declare(strict_types=1);

namespace Curacel\LangFuse\Resources\Observability;

use Curacel\LangFuse\Contracts\ObservationContract;
use Illuminate\Support\Str;

class AbstractObservability implements ObservationContract
{
    /* The id of the span/generation/event can be set, otherwise a random id is generated. */
    protected string $id;

    /* The time at which the span/generation/event started, defaults to the current time. */
    protected string $startTime;

    /* The time at which the span/generation/event ended. */
    protected ?string $endTime = null;

    /* The output to the span/generation/event. Can be any JSON object. */
    protected mixed $output = null;

    protected string $type = 'abstract';

    protected ?string $parentObservationId = null;

    public function __construct(
        public string $traceId,
        public string $name,
        public ?array $metadata = null,
        public mixed $input = null
    ) {
        $this->id = Str::orderedUuid()->toString();
        $this->startTime = (new \DateTime)->format('Y-m-d\TH:i:s.vP');
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function getInput(): mixed
    {
        return $this->input;
    }

    public function getOutput(): mixed
    {
        return $this->output;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function update(array $data): ObservationContract
    {
        if (isset($data['input'])) {
            $this->input = $data['input'];
        }

        if (isset($data['output'])) {
            $this->output = $data['output'];
        }

        if (isset($data['metadata'])) {
            if (! is_array($data['metadata'])) {
                throw new \InvalidArgumentException('Metadata must be an array.');
            }

            $this->metadata = array_merge($this->metadata ?? [], $data['metadata']);
        }

        return $this;
    }

    public function end(?array $data = null): self
    {
        if ($this->endTime === null) {
            $this->endTime = (new \DateTime)->format('Y-m-d\TH:i:s.vP');
        }
        if ($data !== null) {
            $this->update($data);
        }

        $this->maybeExtendParentEndTime();

        return $this;
    }

    public function setParentObservationId(?string $parentObservationId): void
    {
        $this->parentObservationId = $parentObservationId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'traceId' => $this->traceId,
            'type' => $this->type,
            'name' => $this->name,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'metadata' => $this->metadata,
            'input' => $this->input,
            'output' => $this->output,
            'parentObservationId' => $this->parentObservationId,
        ];
    }

    protected function maybeExtendParentEndTime(): void
    {
        // Overridden in Span or child classes to adjust parent end time if needed.
    }
}
