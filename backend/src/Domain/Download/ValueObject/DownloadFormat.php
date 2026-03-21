<?php

declare(strict_types=1);

namespace App\Domain\Download\ValueObject;

use App\Domain\Download\Exception\UnsupportedFormatException;

enum DownloadFormat: string
{
    case Mp4   = 'mp4';
    case Mp3   = 'mp3';
    case WebM  = 'webm';
    case Audio = 'audio';
    case Video = 'video';

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
