<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Domain\Download\ValueObject\JobId;
use App\Infrastructure\Repository\JobRepositoryInterface;
use App\Message\DownloadRequest;
use App\Service\DownloaderService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

/**
 * Background consumer for DownloadRequest messages.
 * Executes the download and updates the job status via the repository.
 */
#[AsMessageHandler]
class DownloadRequestHandler
{
    public function __construct(
        private readonly DownloaderService $downloader,
        private readonly JobRepositoryInterface $jobs,
    ) {}

    public function __invoke(DownloadRequest $message): void
    {
        $jobId = JobId::fromString($message->getJobId());

        $this->jobs->updateStatus($jobId, [
            'status'   => 'processing',
            'progress' => 10,
            'message'  => 'Starting download process...',
        ]);

        try {
            $result = $this->downloader->download(
                $message->getUrl(),
                $message->getFormat(),
                function (int $progress) use ($jobId): void {
                    $this->jobs->updateStatus($jobId, [
                        'progress' => 10 + (int) ($progress * 0.8),
                        'message'  => "Downloading... {$progress}%",
                    ]);
                }
            );

            $this->jobs->updateStatus($jobId, [
                'status'   => 'completed',
                'progress' => 100,
                'path'     => $result['path'],
                'filename' => $result['filename'],
                'mimeType' => $result['mimeType'],
                'message'  => 'Download complete! Preparing file...',
            ]);
        } catch (Throwable $e) {
            error_log("Download job {$jobId} failed: " . $e->getMessage());

            $this->jobs->updateStatus($jobId, [
                'status'   => 'error',
                'progress' => 0,
                'message'  => 'Error en la descarga. Revisa la URL o inténtalo de nuevo más tarde.',
            ]);
        }
    }
}
