<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Document\Exception\ConversionException;
use App\Domain\Document\Exception\InvalidDocumentException;
use App\Domain\Document\Exception\MergeException;
use App\Service\DocumentService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Exposes the document merge endpoint.
 *
 * POST /api/documents/merge
 *   Content-Type: multipart/form-data
 *   Body:         files[] = <file1.pdf>, files[] = <file2.docx>, ...
 *
 * Response: application/pdf attachment (merged_YYYY-MM-DD_HH-MM-SS.pdf)
 */
#[Route('/api/documents')]
final class DocumentController
{
    public function __construct(
        private readonly DocumentService $documentService,
    ) {}

    #[Route('/merge', name: 'documents_merge', methods: ['POST'])]
    public function merge(Request $request): Response
    {
        $files = $request->files->get('files') ?? [];

        // Symfony returns a single UploadedFile when only one file is sent
        if (!is_array($files)) {
            $files = [$files];
        }

        // Filter out any null/empty entries that browsers may send
        $files = array_values(array_filter($files));

        if (empty($files)) {
            return new Response(
                json_encode(['error' => 'No files received. Send files via multipart/form-data with key "files[]".']),
                Response::HTTP_BAD_REQUEST,
                ['Content-Type' => 'application/json']
            );
        }

        $mergedPath = $this->documentService->process($files);

        $response = new BinaryFileResponse($mergedPath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'merged_' . date('Y-m-d_H-i-s') . '.pdf'
        );
        $response->headers->set('Content-Type', 'application/pdf');
        $response->deleteFileAfterSend(true);

        return $response;
    }
}
