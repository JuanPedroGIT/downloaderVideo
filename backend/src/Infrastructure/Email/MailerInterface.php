<?php

declare(strict_types=1);

namespace App\Infrastructure\Email;

interface MailerInterface
{
    public function sendVerificationEmail(string $email, string $username, string $verificationUrl): void;

    public function sendPasswordResetEmail(string $email, string $username, string $resetUrl): void;
}
