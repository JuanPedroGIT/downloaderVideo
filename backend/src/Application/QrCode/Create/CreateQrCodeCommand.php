<?php

declare(strict_types=1);

namespace App\Application\QrCode\Create;

final readonly class CreateQrCodeCommand
{
    public function __construct(
        public string $id,
        public string $targetUrl,
        public int $authUserId,
    ) {}
}
