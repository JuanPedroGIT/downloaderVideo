<?php

declare(strict_types=1);

namespace App\Application\QrCode\Update;

use App\Application\QrCode\QrCodeDto;
use App\Domain\QrCode\Exception\QrCodeForbiddenException;
use App\Domain\QrCode\Exception\QrCodeNotFoundException;
use App\Domain\QrCode\Repository\QrCodeRepositoryInterface;

final class UpdateQrCodeHandler
{
    public function __construct(private readonly QrCodeRepositoryInterface $qrCodes) {}

    public function handle(UpdateQrCodeCommand $command): QrCodeDto
    {
        $qr = $this->qrCodes->findById($command->id);

        if (!$qr) {
            throw new QrCodeNotFoundException($command->id);
        }

        if ($qr->getUser()?->getId() !== $command->authUserId) {
            throw new QrCodeForbiddenException();
        }

        if ($command->targetUrl !== null) {
            $qr->setTargetUrl($command->targetUrl);
        }

        if ($command->isActive !== null) {
            $qr->setIsActive($command->isActive);
        }

        $this->qrCodes->save($qr);

        return QrCodeDto::fromEntity($qr);
    }
}
