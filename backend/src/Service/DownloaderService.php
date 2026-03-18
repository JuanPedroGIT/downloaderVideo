<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Provider\VideoProviderInterface;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Central service responsible for orchestrating the download workflow:
 *   1. Validate URL (host whitelist)
 *   2. Validate requested format
 *   3. Select the appropriate provider
 *   4. Execute yt-dlp as a subprocess
 *   5. Locate and return the generated file path
 */
class DownloaderService
{
    /** @param iterable<VideoProviderInterface> $providers */
    public function __construct(
        private readonly iterable $providers,
        private readonly array $formats,
        private readonly array $allowedHosts,
    ) {
    }

    /**
     * Downloads the video/audio from the given URL in the requested format.
     *
     * @param callable|null $progressCallback Optional function(int $percent) called during download.
     * @return array{path: string, filename: string, mimeType: string}
     *
     * @throws BadRequestHttpException on validation failure
     * @throws RuntimeException        on download/process failure
     */
    public function download(string $url, string $format, ?callable $progressCallback = null): array
    {
        $this->validateUrl($url);
        $this->validateFormat($format);

        $provider = $this->selectProvider($url);

        $slug = bin2hex(random_bytes(8));
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dl_' . $slug;
        if (!mkdir($tmpDir, 0700, true) && !is_dir($tmpDir)) {
            throw new RuntimeException("Could not create temporary directory: {$tmpDir}");
        }

        try {
            // Using a subfolder for the actual downloads allows easier ZIP creation later
            $dlDir = $tmpDir . DIRECTORY_SEPARATOR . 'out';
            mkdir($dlDir);
            $outputTemplate = $dlDir . DIRECTORY_SEPARATOR . '%(title)s.%(ext)s';
            
            $args = $provider->buildArgs($url, $format, $this->formats);

            $this->runYtDlp($dlDir, $outputTemplate, $args, $progressCallback);

            $files = glob($dlDir . DIRECTORY_SEPARATOR . '*');
            if (!$files) {
                throw new RuntimeException('yt-dlp did not produce any output file.');
            }

            // Return a single file or a ZIP if multiple files (playlist)
            if (count($files) === 1) {
                $file = $files[0];
                return [
                    'path'     => $file,
                    'filename' => basename($file),
                    'mimeType' => $this->guessMimeType($file, $format),
                ];
            }

            // Multiple files (Playlist) -> ZIP
            $zipPath = $tmpDir . DIRECTORY_SEPARATOR . "playlist_{$slug}.zip";
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) !== true) {
                throw new RuntimeException("Failed to create ZIP archive: {$zipPath}");
            }
            foreach ($files as $file) {
                // Ignore any partial downloads or dotfiles
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
        } catch (\Throwable $e) {
            $this->cleanup($tmpDir);
            throw $e;
        }
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function validateUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new BadRequestHttpException('Invalid URL provided.');
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        // Simple host contains check for broader support
        $isAllowed = false;
        foreach ($this->allowedHosts as $allowed) {
            if (str_contains($host, $allowed)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            throw new BadRequestHttpException(
                "Host \"{$host}\" is not supported. Allowed: " . implode(', ', $this->allowedHosts)
            );
        }
    }

    private function validateFormat(string $format): void
    {
        if (!isset($this->formats[$format])) {
            throw new BadRequestHttpException(
                "Format \"{$format}\" is not supported. Allowed: " . implode(', ', array_keys($this->formats))
            );
        }
    }

    private function selectProvider(string $url): VideoProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($url)) {
                return $provider;
            }
        }

        throw new BadRequestHttpException('No provider found for the given URL.');
    }

    /**
     * Executes yt-dlp and waits for completion.
     */
    private function runYtDlp(string $cwd, string $outputTemplate, array $extraArgs, ?callable $progressCallback): void
    {
        // Add --ignore-errors, --js-runtimes, --yes-playlist and progress reporting
        $command = array_merge(
            ['yt-dlp', '--ignore-errors', '--js-runtimes', 'node', '--yes-playlist', '--newline', '-o', $outputTemplate],
            $extraArgs,
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'], // stdout (where progress is)
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes, $cwd);

        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start yt-dlp process.');
        }

        fclose($pipes[0]);

        // Non-blocking read for progress reporting
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';

        while (true) {
            $r = [$pipes[1], $pipes[2]];
            $w = $e = null;
            if (stream_select($r, $w, $e, 1) > 0) {
                foreach ($r as $pipe) {
                    $line = fgets($pipe);
                    if ($line === false) continue;
                    
                    if ($pipe === $pipes[1]) {
                        $stdout .= $line;
                        // Extract progress: [download]  12.3% of 45.6MiB...
                        if ($progressCallback && preg_match('/\[download\]\s+([\d\.]+)%/', $line, $matches)) {
                            $progressCallback((int)$matches[1]);
                        }
                    } else {
                        $stderr .= $line;
                    }
                }
            }

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
        }

        // Catch remaining output
        $stdout .= stream_get_contents($pipes[1]);
        $stderr .= stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                "yt-dlp exited with code {$exitCode}.\nSTDOUT: {$stdout}\nSTDERR: {$stderr}"
            );
        }
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

    public function cleanup(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . '*');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        @rmdir($dir);
    }
}
