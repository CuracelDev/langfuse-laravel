<?php

declare(strict_types=1);

namespace Curacel\LangFuse\DTO;

use Illuminate\Support\Collection;

class TagCollection implements \Countable, \IteratorAggregate, \JsonSerializable
{
    private array|Collection $items;

    public function __construct(array $tags = [])
    {
        $this->items = $this->validateAndNormalize($tags);
    }

    public static function fromArray(array $tags): static
    {
        return new static($tags);
    }

    public static function from(string ...$tags): static
    {
        return new static($tags);
    }

    public static function empty(): static
    {
        return new static([]);
    }

    private function validateAndNormalize(array $tags): array
    {
        $normalized = [];
        foreach ($tags as $tag) {
            if (! is_string($tag)) {
                throw new \InvalidArgumentException('Tags must be strings');
            }
            $tag = trim($tag);
            if ($tag === '') {
                throw new \InvalidArgumentException('Tags cannot be empty');
            }

            $normalized[] = $tag;
        }

        return array_values(array_unique($normalized));
    }

    public function has(string $tag): bool
    {
        return in_array(trim($tag), $this->items, true);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function add(string $tag): static
    {
        if ($this->has($tag)) {
            return $this;
        }

        $newItems = $this->items;
        $newItems[] = trim($tag);

        return new static($newItems);
    }

    public function addMany(string ...$tags): static
    {
        $newItems = $this->items;

        foreach ($tags as $tag) {
            $tag = trim($tag);
            if ($tag !== '' && ! in_array($tag, $newItems, true)) {
                $newItems[] = $tag;
            }
        }

        return new static($newItems);
    }

    public function remove(string $tag): static
    {
        return new static(array_values(array_filter($this->items, fn ($item) => $item !== trim($tag))));
    }

    public function merge(TagCollection $other): static
    {
        return new static(array_merge($this->items, $other->items));
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function filter(callable $callback): static
    {
        return new static(array_values(array_filter($this->items, $callback)));
    }

    public function toString(string $separator = ', '): string
    {
        return implode($separator, $this->items);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
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
