<?php

declare(strict_types=1);

namespace App\Domain\Download\ValueObject;

use App\Domain\Download\Exception\InvalidVideoUrlException;

final class VideoUrl
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $url): self
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            throw new InvalidVideoUrlException('URL cannot be empty.');
        }

        if (!filter_var($trimmed, FILTER_VALIDATE_URL)) {
            throw new InvalidVideoUrlException("Invalid URL format: \"{$trimmed}\"");
        }

        return new self($trimmed);
    }

    public function host(): string
    {
        return strtolower((string) parse_url($this->value, PHP_URL_HOST));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
