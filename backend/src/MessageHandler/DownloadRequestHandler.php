<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\DownloadRequest;
use App\Service\DownloaderService;
use Predis\Client;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

/**
 * Background consumer for DownloadRequest messages.
 * Executes the download and updates Redis with the job status.
 */
#[AsMessageHandler]
class DownloadRequestHandler
{
    private Client $redis;

    public function __construct(
        private readonly DownloaderService $downloader,
        string $redisUrl,
    ) {
        $this->redis = new Client($redisUrl);
    }

    public function __invoke(DownloadRequest $message): void
    {
        $jobId = $message->getJobId();
        $redisKey = "job:{$jobId}";

        // 1. Mark as processing
        $this->updateStatus($redisKey, [
            'status'   => 'processing',
            'progress' => 10,
            'message'  => 'Starting download process...',
        ]);

        try {
            // 2. Execute download
            $result = $this->downloader->download(
                $message->getUrl(),
                $message->getFormat(),
                function (int $progress) use ($redisKey) {
                    // Update progress during download
                    $this->updateStatus($redisKey, [
                        'progress' => 10 + (int)($progress * 0.8), // map 0-100 to 10-90
                        'message'  => "Downloading... {$progress}%",
                    ]);
                }
            );

            // 3. Complete
            $this->updateStatus($redisKey, [
                'status'   => 'completed',
                'progress' => 100,
                'path'     => $result['path'],
                'filename' => $result['filename'],
                'mimeType' => $result['mimeType'],
                'message'  => 'Download complete! Preparing file...',
            ]);
        } catch (Throwable $e) {
            // 4. Handle error - provide a generic message to the frontend while keeping the full error in logs
            error_log("Download job {$jobId} failed: " . $e->getMessage());
            
            $this->updateStatus($redisKey, [
                'status'   => 'error',
                'progress' => 0,
                'message'  => 'Error en la descarga. Revisa la URL o inténtalo de nuevo más tarde.',
            ]);
        }
    }

    private function updateStatus(string $key, array $data): void
    {
        $current = json_decode($this->redis->get($key) ?: '{}', true);
        $updated = array_merge($current, $data);
        $this->redis->setex($key, 3600, json_encode($updated)); // expire in 1h
    }
}
