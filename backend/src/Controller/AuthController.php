<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Auth\Login\LoginCommand;
use App\Application\Auth\Login\LoginHandler;
use App\Application\Auth\Register\RegisterUserCommand;
use App\Application\Auth\Register\RegisterUserHandler;
use App\Application\Auth\RequestPasswordReset\RequestPasswordResetCommand;
use App\Application\Auth\RequestPasswordReset\RequestPasswordResetHandler;
use App\Application\Auth\ResetPassword\ResetPasswordCommand;
use App\Application\Auth\ResetPassword\ResetPasswordHandler;
use App\Application\Auth\VerifyEmail\VerifyEmailCommand;
use App\Application\Auth\VerifyEmail\VerifyEmailHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth')]
final class AuthController
{
    public function __construct(
        private readonly LoginHandler $loginHandler,
        private readonly RegisterUserHandler $registerHandler,
        private readonly VerifyEmailHandler $verifyEmailHandler,
        private readonly RequestPasswordResetHandler $requestResetHandler,
        private readonly ResetPasswordHandler $resetPasswordHandler,
    ) {}

    #[Route('/login', name: 'auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $password === '') {
            return new JsonResponse(['error' => 'Username and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->loginHandler->handle(new LoginCommand($username, $password));

        return new JsonResponse([
            'token'     => $result->token,
            'expiresIn' => $result->expiresIn,
            'username'  => $result->username,
        ]);
    }

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

        $this->registerHandler->handle(new RegisterUserCommand($email, $username, $password));

        return new JsonResponse(
            ['message' => 'Registration successful. Please check your email to verify your account.'],
            Response::HTTP_CREATED
        );
    }

    #[Route('/verify-email', name: 'auth_verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = trim((string) $request->query->get('token', ''));

        if ($token === '') {
            return new JsonResponse(['error' => 'Token is required.'], Response::HTTP_BAD_REQUEST);
        }

        $this->verifyEmailHandler->handle(new VerifyEmailCommand($token));

        return new JsonResponse(['message' => 'Email verified successfully. You can now log in.']);
    }

    #[Route('/request-reset', name: 'auth_request_reset', methods: ['POST'])]
    public function requestReset(Request $request): JsonResponse
    {
        $data  = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        // Always 200 — never reveals whether email exists
        $this->requestResetHandler->handle(new RequestPasswordResetCommand($email));

        return new JsonResponse(['message' => 'If that email is registered, a reset link has been sent.']);
    }

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

        $this->resetPasswordHandler->handle(new ResetPasswordCommand($token, $password));

        return new JsonResponse(['message' => 'Password updated successfully. You can now log in.']);
    }
}
