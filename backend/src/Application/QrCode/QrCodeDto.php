<?php

declare(strict_types=1);

namespace App\Application\QrCode;

use App\Entity\QrCode;

final readonly class QrCodeDto
{
    public function __construct(
        public string $id,
        public string $targetUrl,
        public int $clicks,
        public bool $isActive,
        public string $createdAt,
    ) {}

    public static function fromEntity(QrCode $qr): self
    {
        return new self(
            id:        $qr->getId() ?? '',
            targetUrl: $qr->getTargetUrl() ?? '',
            clicks:    $qr->getClicks(),
            isActive:  $qr->isActive(),
            createdAt: $qr->getCreatedAt()?->format('Y-m-d H:i:s') ?? '',
        );
    }
}
