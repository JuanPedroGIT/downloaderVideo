<?php

declare(strict_types=1);

namespace App\Application\QrCode\Create;

use App\Application\QrCode\QrCodeDto;
use App\Domain\Auth\Repository\AdminUserRepositoryInterface;
use App\Domain\QrCode\Exception\QrCodeAlreadyExistsException;
use App\Domain\QrCode\Repository\QrCodeRepositoryInterface;
use App\Entity\QrCode;

final class CreateQrCodeHandler
{
    public function __construct(
        private readonly QrCodeRepositoryInterface $qrCodes,
        private readonly AdminUserRepositoryInterface $users,
    ) {}

    public function handle(CreateQrCodeCommand $command): QrCodeDto
    {
        if ($this->qrCodes->findById($command->id)) {
            throw new QrCodeAlreadyExistsException($command->id);
        }

        $user = $this->users->findById($command->authUserId);

        $qr = (new QrCode())
            ->setId($command->id)
            ->setTargetUrl($command->targetUrl)
            ->setUser($user);

        $this->qrCodes->save($qr);

        return QrCodeDto::fromEntity($qr);
    }
}
