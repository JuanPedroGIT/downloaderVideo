<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\QrCode\Repository\QrCodeRepositoryInterface;
use App\Entity\AdminUser;
use App\Entity\QrCode;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineQrCodeRepository implements QrCodeRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(string $id): ?QrCode
    {
        return $this->em->getRepository(QrCode::class)->find($id);
    }

    public function findByUser(AdminUser $user): array
    {
        return $this->em->getRepository(QrCode::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );
    }

    public function save(QrCode $qrCode): void
    {
        $this->em->persist($qrCode);
        $this->em->flush();
    }

    public function remove(QrCode $qrCode): void
    {
        $this->em->remove($qrCode);
        $this->em->flush();
    }
}
