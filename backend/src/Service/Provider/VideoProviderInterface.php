<?php

declare(strict_types=1);

namespace App\Service\Provider;

/**
 * Contract that every video provider must implement.
 * Add new providers (Vimeo, TikTok…) by creating a class that implements this interface
 * and tagging it with `app.video_provider` in services.yaml.
 */
interface VideoProviderInterface
{
    /**
     * Returns true if this provider is able to handle the given URL.
     */
    public function supports(string $url): bool;

    /**
     * Returns the yt-dlp CLI arguments for the requested format.
     *
     * @param array<string,string> $formatsConfig Map of format => yt-dlp flags
     */
    public function buildArgs(string $url, string $format, array $formatsConfig): array;
}
