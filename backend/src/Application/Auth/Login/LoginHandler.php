<?php

declare(strict_types=1);

namespace App\Application\Auth\Login;

use App\Domain\Auth\Exception\EmailNotVerifiedException;
use App\Domain\Auth\Exception\InvalidCredentialsException;
use App\Domain\Auth\Repository\AdminUserRepositoryInterface;
use App\Infrastructure\Security\JwtServiceInterface;

final class LoginHandler
{
    public function __construct(
        private readonly AdminUserRepositoryInterface $users,
        private readonly JwtServiceInterface $jwt,
    ) {}

    public function handle(LoginCommand $command): LoginResult
    {
        $user = $this->users->findByUsername($command->username);

        if (!$user || !password_verify($command->password, $user->getPasswordHash())) {
            throw new InvalidCredentialsException();
        }

        if (!$user->isVerified() && $user->getEmail() !== null) {
            throw new EmailNotVerifiedException();
        }

        return new LoginResult(
            token:     $this->jwt->generate($user->getId(), $user->getUsername()),
            expiresIn: 86400,
            username:  $user->getUsername(),
        );
    }
}
