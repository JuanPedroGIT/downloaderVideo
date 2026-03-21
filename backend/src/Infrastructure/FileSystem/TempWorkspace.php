<?php

declare(strict_types=1);

namespace App\Infrastructure\FileSystem;

use RuntimeException;

/**
 * Manages a temporary working directory for a single download job.
 */
final class TempWorkspace
{
    private readonly string $rootDir;
    private readonly string $dlDir;

    public function __construct(string $slug)
    {
        $this->rootDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dl_' . $slug;
        $this->dlDir   = $this->rootDir . DIRECTORY_SEPARATOR . 'out';
    }

    public function create(): void
    {
        if (!mkdir($this->rootDir, 0700, true) && !is_dir($this->rootDir)) {
            throw new RuntimeException("Could not create temporary directory: {$this->rootDir}");
        }
        mkdir($this->dlDir);
    }

    public function dlDir(): string
    {
        return $this->dlDir;
    }

    public function rootDir(): string
    {
        return $this->rootDir;
    }

    public function outputTemplate(): string
    {
        return $this->dlDir . DIRECTORY_SEPARATOR . '%(title)s.%(ext)s';
    }

    public function cleanup(): void
    {
        $this->removeDirectory($this->rootDir);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $item) {
            is_dir($item) ? $this->removeDirectory($item) : @unlink($item);
        }

        @rmdir($dir);
    }
}
