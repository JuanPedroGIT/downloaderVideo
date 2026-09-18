<?php

declare(strict_types=1);

namespace App\Infrastructure\Process;

use RuntimeException;

/**
 * Executes yt-dlp as a subprocess and streams progress via an optional callback.
 */
final class YtDlpRunner
{
    public function run(
        string $cwd,
        string $outputTemplate,
        array $extraArgs,
        ?callable $progressCallback = null,
    ): void {
        $commonArgs = [
            'yt-dlp',
            '--ignore-errors',
            '--js-runtimes', 'node',
            '--yes-playlist',
            '--newline',
            '--extractor-args', 'youtube:player-client=android,web,mweb',
            '-o', $outputTemplate,
        ];

        // Prefer a mounted cookies file (YT_COOKIES_PATH), fallback to inline
        // YT_COOKIES for environments where mounting is not possible.
        // yt-dlp reescribe el fichero de cookies al terminar, así que se copia
        // a un sitio escribible (el workspace) aunque el montaje sea ro.
        $cookiesPath = $_ENV['YT_COOKIES_PATH'] ?? getenv('YT_COOKIES_PATH') ?: null;
        if ($cookiesPath && is_file($cookiesPath)) {
            $cookiesSource = $cookiesPath;
        } else {
            $cookiesSource = $_ENV['YT_COOKIES'] ?? getenv('YT_COOKIES') ?: null;
        }

        if ($cookiesSource) {
            $cookiesFile = $cwd . DIRECTORY_SEPARATOR . 'cookies.txt';
            if ($cookiesPath && is_file($cookiesPath)) {
                copy($cookiesPath, $cookiesFile);
            } else {
                file_put_contents($cookiesFile, $cookiesSource);
            }
            $commonArgs[] = '--cookies';
            $commonArgs[] = $cookiesFile;
        }

        $command     = array_merge($commonArgs, $extraArgs);
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $cwd);

        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start yt-dlp process.');
        }

        fclose($pipes[0]);
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
                    if ($line === false) {
                        continue;
                    }
                    if ($pipe === $pipes[1]) {
                        $stdout .= $line;
                        if ($progressCallback && preg_match('/\[download\]\s+([\d.]+)%/', $line, $m)) {
                            $progressCallback((int) $m[1]);
                        }
                    } else {
                        $stderr .= $line;
                    }
                }
            }

            if (!proc_get_status($process)['running']) {
                break;
            }
        }

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
}
