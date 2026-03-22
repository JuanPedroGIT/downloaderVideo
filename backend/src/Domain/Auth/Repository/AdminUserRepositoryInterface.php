<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repository;

use App\Entity\AdminUser;

interface AdminUserRepositoryInterface
{
    public function findById(int $id): ?AdminUser;

    public function findByUsername(string $username): ?AdminUser;

    public function findByEmail(string $email): ?AdminUser;

    public function findByVerificationToken(string $token): ?AdminUser;

    public function findByResetToken(string $token): ?AdminUser;

    public function save(AdminUser $user): void;
}
