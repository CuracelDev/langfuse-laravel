<?php

declare(strict_types=1);

namespace Curacel\LangFuse\DTO;

use ArrayIterator;

class MetadataCollection implements \Countable, \IteratorAggregate, \JsonSerializable
{
    private array $items;

    public function __construct(public array $metadata = [])
    {
        $this->items = $this->validateAndNormalize($metadata);
    }

    public static function fromArray(array $metadata): static
    {
        return new static($metadata);
    }

    public static function empty(): static
    {
        return new static([]);
    }

    private function validateAndNormalize(array $metadata): array
    {
        $normalized = [];
        foreach ($metadata as $key => $value) {
            if (! is_string($key)) {
                throw new \InvalidArgumentException('Metadata keys must be strings');
            }
            // Allow strings, numbers, booleans, and null
            if (! is_scalar($value) && $value !== null) {
                throw new \InvalidArgumentException('Metadata values must be scalar or null');
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function with(string $key, mixed $value): static
    {
        $newItems = $this->items;
        $newItems[$key] = $value;

        return new static($newItems);
    }

    public function except(string $key): static
    {
        $newItems = $this->items;
        unset($newItems[$key]);

        return new static($newItems);
    }

    public function merge(MetadataCollection $other): static
    {
        return new static(array_merge($this->items, $other->items));
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function keys(): array
    {
        return array_keys($this->items);
    }

    public function values(): array
    {
        return array_values($this->items);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
