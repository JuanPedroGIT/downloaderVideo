<?php

declare(strict_types=1);

namespace App\Infrastructure\Email;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class BrevoMailer implements MailerInterface
{
    private const API_URL    = 'https://api.brevo.com/v3/smtp/email';
    private const FROM_EMAIL = 'salumvi@gmail.com';
    private const FROM_NAME  = 'Media Tools';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $brevoApiKey,
    ) {}

    public function sendVerificationEmail(string $email, string $username, string $verificationUrl): void
    {
        $this->send($email, $username, 'Verify your email address', $this->verificationHtml($username, $verificationUrl));
    }

    public function sendPasswordResetEmail(string $email, string $username, string $resetUrl): void
    {
        $this->send($email, $username, 'Reset your password', $this->resetHtml($username, $resetUrl));
    }

    private function send(string $toEmail, string $toName, string $subject, string $htmlContent): void
    {
        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => [
                'api-key'      => $this->brevoApiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'json' => [
                'sender'      => ['name' => self::FROM_NAME, 'email' => self::FROM_EMAIL],
                'to'          => [['email' => $toEmail, 'name' => $toName]],
                'subject'     => $subject,
                'htmlContent' => $htmlContent,
            ],
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException(
                sprintf('Brevo API error %d: %s', $statusCode, $response->getContent(false))
            );
        }
    }

    private function verificationHtml(string $username, string $url): string
    {
        $u = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $l = htmlspecialchars($url,      ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <p>Hi {$u},</p>
        <p>Thank you for registering on <strong>Media Tools</strong>. Please click the link below to verify your email address. The link expires in 24 hours.</p>
        <p><a href="{$l}" style="background:#6366f1;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Verify Email</a></p>
        <p>Or copy this URL: {$l}</p>
        <p>If you did not register, you can safely ignore this email.</p>
        HTML;
    }

    private function resetHtml(string $username, string $url): string
    {
        $u = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $l = htmlspecialchars($url,      ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <p>Hi {$u},</p>
        <p>We received a request to reset your <strong>Media Tools</strong> password. Click the link below — it expires in 1 hour.</p>
        <p><a href="{$l}" style="background:#6366f1;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none;display:inline-block;">Reset Password</a></p>
        <p>Or copy this URL: {$l}</p>
        <p>If you did not request a password reset, you can safely ignore this email.</p>
        HTML;
    }
}
