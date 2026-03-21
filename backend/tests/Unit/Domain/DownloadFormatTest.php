<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\Download\Exception\UnsupportedFormatException;
use App\Domain\Download\ValueObject\DownloadFormat;
use PHPUnit\Framework\TestCase;

class DownloadFormatTest extends TestCase
{
    /** @dataProvider validFormats */
    public function test_creates_from_valid_string(string $format): void
    {
        $result = DownloadFormat::fromString($format);

        $this->assertSame($format, $result->value);
    }

    /** @return array<array{string}> */
    public static function validFormats(): array
    {
        return [
            ['mp4'],
            ['mp3'],
            ['webm'],
            ['audio'],
            ['video'],
        ];
    }

    public function test_throws_on_unsupported_format(): void
    {
        $this->expectException(UnsupportedFormatException::class);
        $this->expectExceptionMessage('avi');

        DownloadFormat::fromString('avi');
    }

    public function test_throws_on_empty_string(): void
    {
        $this->expectException(UnsupportedFormatException::class);

        DownloadFormat::fromString('');
    }

    public function test_throws_on_uppercase_format(): void
    {
        $this->expectException(UnsupportedFormatException::class);

        DownloadFormat::fromString('MP4');
    }

    public function test_error_message_lists_allowed_formats(): void
    {
        try {
            DownloadFormat::fromString('avi');
            $this->fail('Expected exception was not thrown.');
        } catch (UnsupportedFormatException $e) {
            $this->assertStringContainsString('mp4', $e->getMessage());
            $this->assertStringContainsString('mp3', $e->getMessage());
        }
    }
}
