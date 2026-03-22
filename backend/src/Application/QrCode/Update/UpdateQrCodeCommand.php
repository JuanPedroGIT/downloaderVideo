<?php

declare(strict_types=1);

namespace App\Application\QrCode\Update;

final readonly class UpdateQrCodeCommand
{
    public function __construct(
        public string $id,
        public int $authUserId,
        public ?string $targetUrl = null,
        public ?bool $isActive = null,
    ) {}
}
