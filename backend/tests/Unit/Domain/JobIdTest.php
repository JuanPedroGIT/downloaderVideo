<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Download\ValueObject\JobId;
use PHPUnit\Framework\TestCase;

class JobIdTest extends TestCase
{
    public function test_generate_creates_non_empty_id(): void
    {
        $id = JobId::generate();

        $this->assertNotEmpty($id->value());
    }

    public function test_generate_creates_unique_ids(): void
    {
        $a = JobId::generate();
        $b = JobId::generate();

        $this->assertNotSame($a->value(), $b->value());
    }

    public function test_from_string_preserves_value(): void
    {
        $raw = 'abc123def456';
        $id  = JobId::fromString($raw);

        $this->assertSame($raw, $id->value());
    }

    public function test_to_string_returns_value(): void
    {
        $raw = 'test-job-id';
        $id  = JobId::fromString($raw);

        $this->assertSame($raw, (string) $id);
    }

    public function test_generate_produces_hex_string(): void
    {
        $id = JobId::generate();

        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $id->value());
    }
}
