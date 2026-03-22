<?php

declare(strict_types=1);

namespace App\Application\QrCode\List;

use App\Application\QrCode\QrCodeDto;
use App\Domain\Auth\Repository\AdminUserRepositoryInterface;
use App\Domain\QrCode\Repository\QrCodeRepositoryInterface;

final class ListQrCodesHandler
{
    public function __construct(
        private readonly QrCodeRepositoryInterface $qrCodes,
        private readonly AdminUserRepositoryInterface $users,
    ) {}

    /** @return QrCodeDto[] */
    public function handle(ListQrCodesQuery $query): array
    {
        $user = $this->users->findById($query->authUserId);

        if (!$user) {
            return [];
        }

        return array_map(
            QrCodeDto::fromEntity(...),
            $this->qrCodes->findByUser($user)
        );
    }
}
