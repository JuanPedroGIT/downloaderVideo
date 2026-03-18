<?php

declare(strict_types=1);

namespace App\Service\Provider;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * YouTube provider – handles youtube.com and youtu.be URLs.
 */
#[AutoconfigureTag('app.video_provider')]
class YouTubeProvider implements VideoProviderInterface
{
    private const SUPPORTED_HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'youtu.be',
        'm.youtube.com',
        'music.youtube.com',
    ];

    public function supports(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === false || $host === null) {
            return false;
        }

        return in_array(strtolower($host), self::SUPPORTED_HOSTS, true);
    }

    /**
     * Builds the yt-dlp argument array for the requested format.
     *
     * @param array<string,string> $formatsConfig
     * @return string[]
     */
    public function buildArgs(string $url, string $format, array $formatsConfig): array
    {
        $flags = $formatsConfig[$format] ?? '';

        // Split flag string into individual arguments, respecting quoted strings.
        $args = $this->splitArgs($flags);
        $args[] = '--'; // end of options – prevents URL injection
        $args[] = $url;

        return $args;
    }

    /**
     * Splits a space-separated flag string into individual arguments.
     * Handles quoted sub-strings that must not be split.
     *
     * @return string[]
     */
    private function splitArgs(string $flags): array
    {
        if (empty(trim($flags))) {
            return [];
        }

        // Use preg_match_all to handle quoted strings gracefully
        preg_match_all('/(?:[^\s"\']+|"[^"]*"|\'[^\']*\')+/', $flags, $matches);

        return array_map(static function (string $arg): string {
            // Strip surrounding quotes
            return trim($arg, '"\'');
        }, $matches[0]);
    }
}
