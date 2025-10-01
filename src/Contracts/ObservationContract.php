<?php

namespace Curacel\LangFuse\Contracts;

interface ObservationContract
{
    /**
     * Get the unique identifier for this observation
     */
    public function getId(): string;

    public function getName(): string;

    public function getStartTime(): string;

    public function getEndTime(): ?string;

    public function getMetadata(): ?array;

    public function getInput(): mixed;

    public function getOutput(): mixed;

    /**
     * Update observation with new data
     */
    public function update(array $data): ObservationContract;

    public function end(?array $data = null): self;

    public function toArray(): array;

    public function getType(): string;
}
