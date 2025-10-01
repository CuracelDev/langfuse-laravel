<?php

namespace Curacel\LangFuse\DTO;

class ModelParameters
{
    private array $parameters = [];

    public static function fromArray(?array $data): self
    {
        $instance = new self;

        if ($data === null) {
            return $instance;
        }

        if (array_is_list($data)) {
            return $instance;
        }

        foreach ($data as $key => $value) {
            $stringKey = (string) $key;

            if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
                $instance->parameters[$stringKey] = $value;
            } elseif (is_array($value)) {
                $stringArray = array_filter($value, 'is_string');
                if (! empty($stringArray)) {
                    $instance->parameters[$stringKey] = array_values($stringArray);
                }
            }
        }

        return $instance;
    }

    public function set(string $key, string|int|float|bool|array $value): self
    {
        if (is_array($value)) {
            $stringArray = array_filter($value, 'is_string');

            if (count($stringArray) !== count($value)) {
                throw new \InvalidArgumentException('Array values must be strings only');
            }

            $this->parameters[$key] = array_values($stringArray);
        } elseif (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
            $this->parameters[$key] = $value;
        } else {
            throw new \InvalidArgumentException('Invalid parameter type. Must be string, int, float, bool, or array of strings.');
        }

        return $this;
    }

    public function get(string $key): string|int|float|bool|array|null
    {
        return $this->parameters[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    public function remove(string $key): self
    {
        unset($this->parameters[$key]);

        return $this;
    }

    public function toArray(): array
    {
        return $this->parameters;
    }

    public function toObject(): object
    {
        return (object) $this->parameters;
    }

    public function isEmpty(): bool
    {
        return empty($this->parameters);
    }

    public static function empty(): static
    {
        return new static;
    }
}
