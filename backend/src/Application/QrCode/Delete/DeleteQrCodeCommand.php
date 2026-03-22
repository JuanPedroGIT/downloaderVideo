<?php

declare(strict_types=1);

namespace App\Application\QrCode\Delete;

final readonly class DeleteQrCodeCommand
{
    public function __construct(
        public string $id,
        public int $authUserId,
    ) {}
}
