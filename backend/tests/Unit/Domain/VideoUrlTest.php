<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Download\Exception\InvalidVideoUrlException;
use App\Domain\Download\ValueObject\VideoUrl;
use PHPUnit\Framework\TestCase;

class VideoUrlTest extends TestCase
{
    public function test_creates_from_valid_url(): void
    {
        $url = VideoUrl::fromString('https://www.youtube.com/watch?v=abc123');

        $this->assertSame('https://www.youtube.com/watch?v=abc123', $url->value());
    }

    public function test_trims_whitespace(): void
    {
        $url = VideoUrl::fromString('  https://youtu.be/abc  ');

        $this->assertSame('https://youtu.be/abc', $url->value());
    }

    public function test_throws_on_empty_string(): void
    {
        $this->expectException(InvalidVideoUrlException::class);
        $this->expectExceptionMessage('URL cannot be empty');

        VideoUrl::fromString('');
    }

    public function test_throws_on_whitespace_only(): void
    {
        $this->expectException(InvalidVideoUrlException::class);

        VideoUrl::fromString('   ');
    }

    public function test_throws_on_invalid_url_format(): void
    {
        $this->expectException(InvalidVideoUrlException::class);

        VideoUrl::fromString('not-a-url');
    }

    public function test_extracts_host(): void
    {
        $url = VideoUrl::fromString('https://www.youtube.com/watch?v=abc');

        $this->assertSame('www.youtube.com', $url->host());
    }

    public function test_host_is_lowercased(): void
    {
        $url = VideoUrl::fromString('https://WWW.YOUTUBE.COM/watch?v=abc');

        $this->assertSame('www.youtube.com', $url->host());
    }

    public function test_to_string_returns_value(): void
    {
        $raw = 'https://youtu.be/abc123';
        $url = VideoUrl::fromString($raw);

        $this->assertSame($raw, (string) $url);
    }
}
