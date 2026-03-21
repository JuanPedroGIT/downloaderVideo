<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Download\ValueObject\JobId;
use App\Infrastructure\Repository\JobRepositoryInterface;
use App\Message\DownloadRequest;
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
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly JobRepositoryInterface $jobs,
    ) {}

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

        $jobId = JobId::generate();

        $this->jobs->initialize($jobId, $url, $format);
        $this->bus->dispatch(new DownloadRequest($url, $format, $jobId->value()));

        return new JsonResponse([
            'jobId'  => $jobId->value(),
            'status' => 'pending',
        ], Response::HTTP_ACCEPTED);
    }

    #[Route('/status/{jobId}', name: 'status', methods: ['GET'])]
    public function status(string $jobId): JsonResponse
    {
        $job = $this->jobs->find(JobId::fromString($jobId));

        if ($job === null) {
            throw new NotFoundHttpException('Job not found or expired.');
        }

        return new JsonResponse($job);
    }

    #[Route('/fetch/{jobId}', name: 'fetch', methods: ['GET'])]
    public function fetch(string $jobId): Response
    {
        $job = $this->jobs->find(JobId::fromString($jobId));

        if ($job === null) {
            throw new NotFoundHttpException('Job not found or expired.');
        }

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
        $response->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return new JsonResponse(['status' => 'ok', 'service' => 'youtube-downloader-api']);
    }
}
