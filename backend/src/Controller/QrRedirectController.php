<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\QrCode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QrRedirectController extends AbstractController
{
    #[Route('/q/{id}', name: 'qr_redirect', methods: ['GET'])]
    public function redirectQr(string $id, EntityManagerInterface $em): Response
    {
        $qrCode = $em->getRepository(QrCode::class)->find($id);

        if (!$qrCode || !$qrCode->isActive()) {
            throw $this->createNotFoundException('Este código QR no existe o está desactivado.');
        }

        // Increment scan count
        $qrCode->incrementClicks();
        $em->flush();

        // 302 Redirect to target URL
        return $this->redirect($qrCode->getTargetUrl());
    }

    #[Route('/api/qr/generate/{id}', name: 'qr_generate_image', methods: ['GET'])]
    public function generateImage(string $id): Response
    {
        // Generate the URL that the QR points to
        $targetUrl = $this->generateUrl('qr_redirect', ['id' => $id], UrlGeneratorInterface::ABSOLUTE_URL);

        $result = Builder::create()
            ->writer(new SvgWriter())
            ->writerOptions([])
            ->data($targetUrl)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->build();

        return new Response(
            $result->getString(),
            Response::HTTP_OK,
            ['Content-Type' => $result->getMimeType()]
        );
    }
}
