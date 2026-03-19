<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ImageDownloaderService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire('%kernel.project_dir%')] private string $projectDir
    ) {}

    public function downloadAndSave(string $url): string
    {
        try {
            // Ensure the uploads directory exists
            $uploadDir = $this->projectDir . '/public/uploads/images';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Download the image
            $response = $this->httpClient->request('GET', $url);
            
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to download image: HTTP ' . $response->getStatusCode());
            }

            $contentType = $response->getHeaders()['content-type'][0] ?? 'image/jpeg';
            $extension = $this->getExtensionFromMimeType($contentType);
            
            $filename = uniqid('img_', true) . '.' . $extension;
            $filePath = $uploadDir . '/' . $filename;
            
            // Save the image content securely to the persistent local volume
            file_put_contents($filePath, $response->getContent());
            
            // Return the public URL path
            return '/uploads/images/' . $filename;
            
        } catch (\Throwable $e) {
             throw new BadRequestHttpException('Error downloading external image: ' . $e->getMessage());
        }
    }

    private function getExtensionFromMimeType(string $mimeType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];
        
        return $map[$mimeType] ?? 'jpg'; // Fallback to jpg
    }
}
