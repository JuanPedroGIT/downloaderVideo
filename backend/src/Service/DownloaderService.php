<?php

declare(strict_types=1);

namespace App\Service;

use App\Domain\Download\Exception\InvalidVideoUrlException;
use App\Domain\Download\ValueObject\DownloadFormat;
use App\Domain\Download\ValueObject\VideoUrl;
use App\Infrastructure\FileSystem\TempWorkspace;
use App\Infrastructure\Process\YtDlpRunner;
use App\Service\Provider\VideoProviderInterface;
use RuntimeException;

/**
 * Orchestrates the download workflow:
 *   1. Parse and validate URL / format as Value Objects
 *   2. Select the appropriate provider
 *   3. Create an isolated temp workspace
 *   4. Run yt-dlp via YtDlpRunner
 *   5. Return the resulting file info (or ZIP for playlists)
 */
class DownloaderService
{
    /** @param iterable<VideoProviderInterface> $providers */
    public function __construct(
        private readonly iterable $providers,
        private readonly array $formats,
        private readonly array $allowedHosts,
        private readonly YtDlpRunner $ytDlpRunner,
    ) {}

    /**
     * @param callable|null $progressCallback Optional function(int $percent) called during download.
     * @return array{path: string, filename: string, mimeType: string}
     */
    public function download(string $url, string $format, ?callable $progressCallback = null): array
    {
        $videoUrl       = VideoUrl::fromString($url);
        $this->assertHostAllowed($videoUrl);
        $downloadFormat = DownloadFormat::fromString($format);

        $provider  = $this->selectProvider($videoUrl);
        $slug      = bin2hex(random_bytes(8));
        $workspace = new TempWorkspace($slug);
        $workspace->create();

        try {
            $args = $provider->buildArgs($videoUrl->value(), $downloadFormat->value, $this->formats);
            $this->ytDlpRunner->run($workspace->dlDir(), $workspace->outputTemplate(), $args, $progressCallback);

            $files = glob($workspace->dlDir() . DIRECTORY_SEPARATOR . '*') ?: [];
            if (empty($files)) {
                throw new RuntimeException('yt-dlp did not produce any output file.');
            }

            if (count($files) === 1) {
                $file = $files[0];
                return [
                    'path'     => $file,
                    'filename' => basename($file),
                    'mimeType' => $this->guessMimeType($file, $format),
                ];
            }

            return $this->createZip($workspace->rootDir(), $slug, $files);
        } catch (\Throwable $e) {
            $workspace->cleanup();
            throw $e;
        }
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function assertHostAllowed(VideoUrl $url): void
    {
        $host = $url->host();
        foreach ($this->allowedHosts as $allowed) {
            if (str_contains($host, $allowed)) {
                return;
            }
        }

        throw new InvalidVideoUrlException(
            "Host \"{$host}\" is not supported. Allowed: " . implode(', ', $this->allowedHosts)
        );
    }

    private function selectProvider(VideoUrl $url): VideoProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($url->value())) {
                return $provider;
            }
        }

        throw new InvalidVideoUrlException('No provider found for the given URL.');
    }

    /** @param string[] $files */
    private function createZip(string $rootDir, string $slug, array $files): array
    {
        $zipPath = $rootDir . DIRECTORY_SEPARATOR . "playlist_{$slug}.zip";
        $zip     = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
            throw new RuntimeException("Failed to create ZIP archive: {$zipPath}");
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                $zip->addFile($file, basename($file));
            }
        }

        $zip->close();

        return [
            'path'     => $zipPath,
            'filename' => "playlist_{$slug}.zip",
            'mimeType' => 'application/zip',
        ];
    }

    private function guessMimeType(string $filePath, string $format): string
    {
        $map = [
            'mp3'   => 'audio/mpeg',
            'mp4'   => 'video/mp4',
            'webm'  => 'video/webm',
            'audio' => 'audio/mpeg',
            'video' => 'video/webm',
            'ogg'   => 'audio/ogg',
            'opus'  => 'audio/opus',
            'm4a'   => 'audio/mp4',
        ];

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return $map[$ext] ?? ($map[$format] ?? 'application/octet-stream');
    }
}
