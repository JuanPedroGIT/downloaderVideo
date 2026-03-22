<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Auth\Repository\AdminUserRepositoryInterface;
use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineAdminUserRepository implements AdminUserRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function findById(int $id): ?AdminUser
    {
        return $this->em->getRepository(AdminUser::class)->find($id);
    }

    public function findByUsername(string $username): ?AdminUser
    {
        return $this->em->getRepository(AdminUser::class)->findOneBy(['username' => $username]);
    }

    public function findByEmail(string $email): ?AdminUser
    {
        return $this->em->getRepository(AdminUser::class)->findOneBy(['email' => $email]);
    }

    public function findByVerificationToken(string $token): ?AdminUser
    {
        return $this->em->getRepository(AdminUser::class)->findOneBy(['verificationToken' => $token]);
    }

    public function findByResetToken(string $token): ?AdminUser
    {
        return $this->em->getRepository(AdminUser::class)->findOneBy(['resetToken' => $token]);
    }

    public function save(AdminUser $user): void
    {
        $this->em->persist($user);
        $this->em->flush();
    }
}
