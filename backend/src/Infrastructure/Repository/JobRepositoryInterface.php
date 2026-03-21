<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Download\ValueObject\JobId;

interface JobRepositoryInterface
{
    public function initialize(JobId $jobId, string $url, string $format): void;

    public function updateStatus(JobId $jobId, array $data): void;

    /** @return array<string, mixed>|null */
    public function find(JobId $jobId): ?array;
}
