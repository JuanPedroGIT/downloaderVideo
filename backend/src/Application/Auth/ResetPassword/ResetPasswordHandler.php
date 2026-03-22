<?php

declare(strict_types=1);

namespace App\Application\Auth\ResetPassword;

use App\Domain\Auth\Exception\InvalidTokenException;
use App\Domain\Auth\Exception\TokenExpiredException;
use App\Domain\Auth\Repository\AdminUserRepositoryInterface;

final class ResetPasswordHandler
{
    public function __construct(private readonly AdminUserRepositoryInterface $users) {}

    public function handle(ResetPasswordCommand $command): void
    {
        $user = $this->users->findByResetToken($command->token);

        if (!$user) {
            throw new InvalidTokenException('reset link');
        }

        if ($user->getResetTokenExpires() < new \DateTimeImmutable()) {
            throw new TokenExpiredException('reset link');
        }

        $user->setPasswordHash(password_hash($command->password, PASSWORD_BCRYPT));
        $user->clearResetToken();
        $this->users->save($user);
    }
}
