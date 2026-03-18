<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message DTO for asynchronous download requests.
 */
class DownloadRequest
{
    public function __construct(
        private readonly string $url,
        private readonly string $format,
        private readonly string $jobId,
    ) {
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }
}
