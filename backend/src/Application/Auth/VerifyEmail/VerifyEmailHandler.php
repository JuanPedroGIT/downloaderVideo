<?php

declare(strict_types=1);

namespace App\Application\Auth\VerifyEmail;

use App\Domain\Auth\Exception\InvalidTokenException;
use App\Domain\Auth\Exception\TokenExpiredException;
use App\Domain\Auth\Repository\AdminUserRepositoryInterface;

final class VerifyEmailHandler
{
    public function __construct(private readonly AdminUserRepositoryInterface $users) {}

    public function handle(VerifyEmailCommand $command): void
    {
        $user = $this->users->findByVerificationToken($command->token);

        if (!$user) {
            throw new InvalidTokenException('verification link');
        }

        if ($user->getVerificationTokenExpires() < new \DateTimeImmutable()) {
            throw new TokenExpiredException('verification link');
        }

        $user->setIsVerified(true);
        $user->clearVerificationToken();
        $this->users->save($user);
    }
}
