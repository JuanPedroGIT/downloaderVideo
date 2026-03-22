<?php

declare(strict_types=1);

namespace App\Application\Auth\Register;

use App\Domain\Auth\Exception\EmailAlreadyRegisteredException;
use App\Domain\Auth\Exception\UsernameAlreadyTakenException;
use App\Domain\Auth\Repository\AdminUserRepositoryInterface;
use App\Entity\AdminUser;
use App\Infrastructure\Email\MailerInterface;

final class RegisterUserHandler
{
    public function __construct(
        private readonly AdminUserRepositoryInterface $users,
        private readonly MailerInterface $mailer,
        private readonly string $defaultUri,
    ) {}

    public function handle(RegisterUserCommand $command): void
    {
        if ($this->users->findByEmail($command->email)) {
            throw new EmailAlreadyRegisteredException($command->email);
        }

        if ($this->users->findByUsername($command->username)) {
            throw new UsernameAlreadyTakenException($command->username);
        }

        $token  = AdminUser::generateToken();
        $expiry = new \DateTimeImmutable('+24 hours');

        $user = (new AdminUser())
            ->setEmail($command->email)
            ->setUsername($command->username)
            ->setPasswordHash(password_hash($command->password, PASSWORD_BCRYPT))
            ->setVerificationToken($token)
            ->setVerificationTokenExpires($expiry);

        $this->users->save($user);

        $verificationUrl = rtrim($this->defaultUri, '/') . '/verify-email?token=' . $token;

        try {
            $this->mailer->sendVerificationEmail($command->email, $command->username, $verificationUrl);
        } catch (\Throwable) {
            // Account created — email failure is non-fatal
        }
    }
}
