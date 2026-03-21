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

        $cookiesRaw = $_ENV['YT_COOKIES'] ?? getenv('YT_COOKIES') ?: null;
        if ($cookiesRaw) {
            $cookiesFile = $cwd . DIRECTORY_SEPARATOR . 'cookies.txt';
            file_put_contents($cookiesFile, $cookiesRaw);
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
