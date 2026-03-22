<?php

declare(strict_types=1);

namespace App\Domain\QrCode\Repository;

use App\Entity\AdminUser;
use App\Entity\QrCode;

interface QrCodeRepositoryInterface
{
    public function findById(string $id): ?QrCode;

    /** @return QrCode[] */
    public function findByUser(AdminUser $user): array;

    public function save(QrCode $qrCode): void;

    public function remove(QrCode $qrCode): void;
}
