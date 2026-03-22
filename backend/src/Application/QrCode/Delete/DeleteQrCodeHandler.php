<?php

declare(strict_types=1);

namespace App\Application\QrCode\Delete;

use App\Domain\QrCode\Exception\QrCodeForbiddenException;
use App\Domain\QrCode\Exception\QrCodeNotFoundException;
use App\Domain\QrCode\Repository\QrCodeRepositoryInterface;

final class DeleteQrCodeHandler
{
    public function __construct(private readonly QrCodeRepositoryInterface $qrCodes) {}

    public function handle(DeleteQrCodeCommand $command): void
    {
        $qr = $this->qrCodes->findById($command->id);

        if (!$qr) {
            throw new QrCodeNotFoundException($command->id);
        }

        if ($qr->getUser()?->getId() !== $command->authUserId) {
            throw new QrCodeForbiddenException();
        }

        $this->qrCodes->remove($qr);
    }
}
