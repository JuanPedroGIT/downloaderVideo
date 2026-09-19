<?php

declare(strict_types=1);

namespace App\Domain\Download\ValueObject;

use App\Domain\Download\Exception\UnsupportedFormatException;

enum DownloadFormat: string
{
    case Mp4      = 'mp4';
    case Mp41080  = 'mp4-1080';
    case Mp4720   = 'mp4-720';
    case Mp4480   = 'mp4-480';
    case Mp3      = 'mp3';
    case Mp3128   = 'mp3-128';

    public static function fromString(string $format): self
    {
        $instance = self::tryFrom($format);

        if ($instance === null) {
            $allowed = implode(', ', array_column(self::cases(), 'value'));
            throw new UnsupportedFormatException(
                "Format \"{$format}\" is not supported. Allowed: {$allowed}"
            );
        }

        return $instance;
    }
}
