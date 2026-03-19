<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ImageDownloaderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ImageController extends AbstractController
{
    #[Route('/api/image/download', name: 'api_image_download', methods: ['POST'])]
    public function downloadExternalImage(Request $request, ImageDownloaderService $imageDownloader): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $url = $data['url'] ?? null;

        if (!$url) {
            return new JsonResponse(['error' => 'URL is required in the JSON payload'], 400);
        }

        try {
            $localPath = $imageDownloader->downloadAndSave($url);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Image successfully saved to persistent volume',
                'local_url' => $localPath
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
