<?php

declare(strict_types=1);

namespace App\Application\Auth\RequestPasswordReset;

use App\Domain\Auth\Repository\AdminUserRepositoryInterface;
use App\Entity\AdminUser;
use App\Infrastructure\Email\MailerInterface;

final class RequestPasswordResetHandler
{
    public function __construct(
        private readonly AdminUserRepositoryInterface $users,
        private readonly MailerInterface $mailer,
        private readonly string $defaultUri,
    ) {}

    /**
     * Always succeeds — never reveals whether the email exists (anti-enumeration).
     */
    public function handle(RequestPasswordResetCommand $command): void
    {
        $user = $this->users->findByEmail($command->email);

        if (!$user || !$user->isVerified()) {
            return;
        }

        $token  = AdminUser::generateToken();
        $expiry = new \DateTimeImmutable('+1 hour');

        $user->setResetToken($token);
        $user->setResetTokenExpires($expiry);
        $this->users->save($user);

        $resetUrl = rtrim($this->defaultUri, '/') . '/reset-password?token=' . $token;

        try {
            $this->mailer->sendPasswordResetEmail($command->email, $user->getUsername(), $resetUrl);
        } catch (\Throwable) {
            // Silently swallow
        }
    }
}
