<?php

declare(strict_types=1);

namespace App\Domain\Download\ValueObject;

final class JobId
{
    private function __construct(private readonly string $value) {}

    public static function generate(): self
    {
        return new self(bin2hex(random_bytes(16)));
    }

    public static function fromString(string $id): self
    {
        return new self($id);
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
