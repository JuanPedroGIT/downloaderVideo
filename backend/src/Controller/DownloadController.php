<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\DownloadRequest;
use Predis\Client;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;


class DownloadController
{
    private Client $redis;

    public function __construct(
        private readonly MessageBusInterface $bus,
        string $redisUrl,
    ) {
        $this->redis = new Client($redisUrl);
    }

    #[Route('/download', name: 'download', methods: ['POST'])]
    public function download(Request $request): Response
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $url    = trim((string) ($data['url']    ?? ''));
        $format = trim((string) ($data['format'] ?? ''));

        if ($url === '') {
            throw new BadRequestHttpException('"url" field is required.');
        }
        if ($format === '') {
            throw new BadRequestHttpException('"format" field is required.');
        }

        // 1. Generate unique Job ID
        $jobId = bin2hex(random_bytes(16));

        // 2. Initialize job status in Redis
        $this->redis->setex("job:{$jobId}", 3600, json_encode([
            'id'       => $jobId,
            'status'   => 'pending',
            'progress' => 0,
            'message'  => 'Queued...',
            'url'      => $url,
            'format'   => $format,
        ]));

        // 3. Dispatch message to queue
        $this->bus->dispatch(new DownloadRequest($url, $format, $jobId));

        return new JsonResponse([
            'jobId'  => $jobId,
            'status' => 'pending'
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/status/{jobId}', name: 'status', methods: ['GET'])]
    public function status(string $jobId): JsonResponse
    {
        $data = $this->redis->get("job:{$jobId}");
        if (!$data) {
            throw new NotFoundHttpException('Job not found or expired.');
        }

        return new JsonResponse(json_decode($data, true));
    }

    #[Route('/fetch/{jobId}', name: 'fetch', methods: ['GET'])]
    public function fetch(string $jobId): Response
    {
        $data = $this->redis->get("job:{$jobId}");
        if (!$data) {
            throw new NotFoundHttpException('Job not found or expired.');
        }

        $job = json_decode($data, true);
        if ($job['status'] !== 'completed') {
            throw new BadRequestHttpException('Job is not completed yet.');
        }

        $filePath = $job['path'] ?? '';
        if (!file_exists($filePath)) {
            throw new NotFoundHttpException('File not found on server.');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $job['filename'] ?? 'download'
        );
        $response->headers->set('Content-Type', $job['mimeType'] ?? 'application/octet-stream');

        // Note: deleteFileAfterSend might be risky if we want to allow retries,
        // but for now let's keep it to save space.
        $response->deleteFileAfterSend(true);
        
        // Remove from Redis after successful fetch (optional)
        // $this->redis->del("job:{$jobId}");

        return $response;
    }


    // ── Global error handler for bad requests ───────────────────────────────
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok', 'service' => 'youtube-downloader-api']);
    }
}
