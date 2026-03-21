<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Download\ValueObject\JobId;
use Predis\Client;

final class RedisJobRepository implements JobRepositoryInterface
{
    private const TTL = 3600;

    public function __construct(private readonly Client $redis) {}

    public function initialize(JobId $jobId, string $url, string $format): void
    {
        $this->redis->setex(
            $this->key($jobId),
            self::TTL,
            json_encode([
                'id'       => $jobId->value(),
                'status'   => 'pending',
                'progress' => 0,
                'message'  => 'Queued...',
                'url'      => $url,
                'format'   => $format,
            ], JSON_THROW_ON_ERROR)
        );
    }

    public function updateStatus(JobId $jobId, array $data): void
    {
        $key     = $this->key($jobId);
        $current = json_decode($this->redis->get($key) ?: '{}', true);
        $updated = array_merge($current, $data);
        $this->redis->setex($key, self::TTL, json_encode($updated, JSON_THROW_ON_ERROR));
    }

    public function find(JobId $jobId): ?array
    {
        $data = $this->redis->get($this->key($jobId));

        return $data ? json_decode($data, true) : null;
    }

    private function key(JobId $jobId): string
    {
        return "job:{$jobId}";
    }
}
