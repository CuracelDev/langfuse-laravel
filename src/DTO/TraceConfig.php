<?php

declare(strict_types=1);

namespace Curacel\LangFuse\DTO;

class TraceConfig
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $userId = null,
        public readonly ?string $sessionId = null,
        public readonly ?MetadataCollection $metadata = null,
        public readonly TagCollection $tags = new TagCollection([]),
        public readonly ?string $version = null,
        public readonly ?string $release = null,
        public readonly mixed $input = null,
        public readonly mixed $output = null,
        public readonly ?string $id = null
    ) {}

    public static function create(
        string $name,
        ?string $userId = null,
        ?string $sessionId = null,
        ?array $metadata = null,
        array $tags = [],
        ?string $version = null,
        ?string $release = null,
        mixed $input = null,
        mixed $output = null,
        ?string $id = null
    ): static {
        return new static(
            name: $name,
            userId: $userId,
            sessionId: $sessionId,
            metadata: $metadata ? MetadataCollection::fromArray($metadata) : null,
            tags: TagCollection::fromArray($tags),
            version: $version,
            release: $release,
            input: $input,
            output: $output,
            id: $id
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            ...($this->id !== null ? ['id' => $this->id] : []),
            ...($this->userId !== null ? ['userId' => $this->userId] : []),
            ...($this->sessionId !== null ? ['sessionId' => $this->sessionId] : []),
            ...($this->metadata !== null ? ['metadata' => $this->metadata->toArray()] : []),
            ...(! $this->tags->isEmpty() ? ['tags' => $this->tags->toArray()] : []),
            ...($this->version !== null ? ['version' => $this->version] : []),
            ...($this->release !== null ? ['release' => $this->release] : []),
        ];
    }

    public function mergeUsing(
        ?string $name = null,
        ?string $userId = null,
        ?string $sessionId = null,
        ?MetadataCollection $metadata = null,
        ?TagCollection $tags = null,
        ?string $version = null,
        ?string $release = null,
        ?string $id = null
    ): self {
        return new self(
            name: $name ?? $this->name,
            userId: $userId ?? $this->userId,
            sessionId: $sessionId ?? $this->sessionId,
            metadata: $metadata ?? $this->metadata,
            tags: $tags ?? $this->tags,
            version: $version ?? $this->version,
            release: $release ?? $this->release,
            id: $id ?? $this->id
        );
    }
}
