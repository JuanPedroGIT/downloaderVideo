<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AdminUser;
use App\Service\BrevoMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
final class AuthController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BrevoMailer $mailer,
        private readonly string $defaultUri,
    ) {}

    // ── POST /api/auth/register ───────────────────────────────────────────────

    #[Route('/register', name: 'auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $email    = strtolower(trim((string) ($data['email']    ?? '')));
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($email === '' || $username === '' || $password === '') {
            return new JsonResponse(['error' => 'email, username and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'Invalid email address.'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($username) < 3 || strlen($username) > 64) {
            return new JsonResponse(['error' => 'Username must be between 3 and 64 characters.'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($password) < 8) {
            return new JsonResponse(['error' => 'Password must be at least 8 characters.'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->em->getRepository(AdminUser::class)->findOneBy(['email' => $email])) {
            return new JsonResponse(['error' => 'This email is already registered.'], Response::HTTP_CONFLICT);
        }

        if ($this->em->getRepository(AdminUser::class)->findOneBy(['username' => $username])) {
            return new JsonResponse(['error' => 'This username is already taken.'], Response::HTTP_CONFLICT);
        }

        $token  = AdminUser::generateToken();
        $expiry = new \DateTimeImmutable('+24 hours');

        $user = (new AdminUser())
            ->setEmail($email)
            ->setUsername($username)
            ->setPasswordHash(password_hash($password, PASSWORD_BCRYPT))
            ->setVerificationToken($token)
            ->setVerificationTokenExpires($expiry);

        $this->em->persist($user);
        $this->em->flush();

        $verificationUrl = rtrim($this->defaultUri, '/') . '/verify-email?token=' . $token;

        try {
            $this->mailer->sendVerificationEmail($email, $username, $verificationUrl);
        } catch (\Throwable) {
            // Account created — email failure is non-fatal
        }

        return new JsonResponse(
            ['message' => 'Registration successful. Please check your email to verify your account.'],
            Response::HTTP_CREATED
        );
    }

    // ── GET /api/auth/verify-email?token=xxx ──────────────────────────────────

    #[Route('/verify-email', name: 'auth_verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = trim((string) $request->query->get('token', ''));

        if ($token === '') {
            return new JsonResponse(['error' => 'Token is required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(AdminUser::class)->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return new JsonResponse(['error' => 'Invalid or already used verification link.'], Response::HTTP_NOT_FOUND);
        }

        if ($user->getVerificationTokenExpires() < new \DateTimeImmutable()) {
            return new JsonResponse(['error' => 'Verification link has expired. Please register again.'], Response::HTTP_GONE);
        }

        $user->setIsVerified(true);
        $user->clearVerificationToken();
        $this->em->flush();

        return new JsonResponse(['message' => 'Email verified successfully. You can now log in.']);
    }

    // ── POST /api/auth/request-reset ─────────────────────────────────────────

    #[Route('/request-reset', name: 'auth_request_reset', methods: ['POST'])]
    public function requestReset(Request $request): JsonResponse
    {
        $data  = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        // Always return 200 to prevent email enumeration
        $ok = new JsonResponse(['message' => 'If that email is registered, a reset link has been sent.']);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $ok;
        }

        $user = $this->em->getRepository(AdminUser::class)->findOneBy(['email' => $email]);

        if (!$user || !$user->isVerified()) {
            return $ok;
        }

        $token  = AdminUser::generateToken();
        $expiry = new \DateTimeImmutable('+1 hour');

        $user->setResetToken($token);
        $user->setResetTokenExpires($expiry);
        $this->em->flush();

        $resetUrl = rtrim($this->defaultUri, '/') . '/reset-password?token=' . $token;

        try {
            $this->mailer->sendPasswordResetEmail($email, $user->getUsername(), $resetUrl);
        } catch (\Throwable) {
            // Silently swallow
        }

        return $ok;
    }

    // ── POST /api/auth/reset-password ─────────────────────────────────────────

    #[Route('/reset-password', name: 'auth_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $token    = trim((string) ($data['token']    ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($token === '' || $password === '') {
            return new JsonResponse(['error' => 'token and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($password) < 8) {
            return new JsonResponse(['error' => 'Password must be at least 8 characters.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(AdminUser::class)->findOneBy(['resetToken' => $token]);

        if (!$user) {
            return new JsonResponse(['error' => 'Invalid or already used reset link.'], Response::HTTP_NOT_FOUND);
        }

        if ($user->getResetTokenExpires() < new \DateTimeImmutable()) {
            return new JsonResponse(['error' => 'Reset link has expired. Please request a new one.'], Response::HTTP_GONE);
        }

        $user->setPasswordHash(password_hash($password, PASSWORD_BCRYPT));
        $user->clearResetToken();
        $this->em->flush();

        return new JsonResponse(['message' => 'Password updated successfully. You can now log in.']);
    }
}
