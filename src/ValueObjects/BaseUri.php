<?php

declare(strict_types=1);

namespace Curacel\LangFuse\ValueObjects;

final class BaseUri
{
    /**
     * Creates a new Base URI value object.
     */
    private function __construct(private readonly string $baseUri)
    {
        // ..
    }

    /**
     * Creates a new Base URI value object.
     */
    public static function from(string $baseUri): self
    {
        return new self($baseUri);
    }

    public function toString(): string
    {
        foreach (['http://', 'https://'] as $protocol) {
            if (str_starts_with($this->baseUri, $protocol)) {
                return "$this->baseUri/";
            }
        }

        return "https://$this->baseUri/";
    }
}
