<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth;

use App\Application\Auth\Login\LoginCommand;
use App\Application\Auth\Login\LoginHandler;
use App\Application\Auth\Login\LoginResult;
use App\Domain\Auth\Exception\EmailNotVerifiedException;
use App\Domain\Auth\Exception\InvalidCredentialsException;
use App\Domain\Auth\Repository\AdminUserRepositoryInterface;
use App\Entity\AdminUser;
use App\Infrastructure\Security\JwtServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LoginHandlerTest extends TestCase
{
    private AdminUserRepositoryInterface&MockObject $users;
    private JwtServiceInterface&MockObject $jwt;
    private LoginHandler $handler;

    protected function setUp(): void
    {
        $this->users   = $this->createMock(AdminUserRepositoryInterface::class);
        $this->jwt     = $this->createMock(JwtServiceInterface::class);
        $this->handler = new LoginHandler($this->users, $this->jwt);
    }

    public function testSuccessfulLogin(): void
    {
        $user = $this->makeUser('alice', password_hash('secret123', PASSWORD_BCRYPT));

        $this->users->method('findByUsername')->with('alice')->willReturn($user);
        $this->jwt->method('generate')->with(1, 'alice')->willReturn('jwt.token.here');

        $result = $this->handler->handle(new LoginCommand('alice', 'secret123'));

        self::assertInstanceOf(LoginResult::class, $result);
        self::assertSame('jwt.token.here', $result->token);
        self::assertSame('alice', $result->username);
        self::assertSame(86400, $result->expiresIn);
    }

    public function testThrowsOnUnknownUsername(): void
    {
        $this->users->method('findByUsername')->willReturn(null);
        $this->expectException(InvalidCredentialsException::class);

        $this->handler->handle(new LoginCommand('ghost', 'password'));
    }

    public function testThrowsOnWrongPassword(): void
    {
        $user = $this->makeUser('alice', password_hash('correct', PASSWORD_BCRYPT));
        $this->users->method('findByUsername')->willReturn($user);
        $this->expectException(InvalidCredentialsException::class);

        $this->handler->handle(new LoginCommand('alice', 'wrong'));
    }

    public function testThrowsWhenEmailNotVerified(): void
    {
        $user = $this->makeUser('alice', password_hash('pass1234', PASSWORD_BCRYPT), email: 'alice@example.com', verified: false);
        $this->users->method('findByUsername')->willReturn($user);
        $this->expectException(EmailNotVerifiedException::class);

        $this->handler->handle(new LoginCommand('alice', 'pass1234'));
    }

    public function testAllowsLoginWhenEmailIsNull(): void
    {
        // Legacy users created via CLI have no email — should not be blocked
        $user = $this->makeUser('admin', password_hash('admin1234', PASSWORD_BCRYPT), email: null, verified: false);
        $this->users->method('findByUsername')->willReturn($user);
        $this->jwt->method('generate')->willReturn('token');

        $result = $this->handler->handle(new LoginCommand('admin', 'admin1234'));

        self::assertSame('token', $result->token);
    }

    private function makeUser(string $username, string $hash, ?string $email = null, bool $verified = true): AdminUser
    {
        $user = new AdminUser();
        $user->setUsername($username)->setPasswordHash($hash);

        if ($email !== null) {
            $user->setEmail($email);
        }

        $user->setIsVerified($verified);

        // Force id via reflection
        $ref = new \ReflectionProperty(AdminUser::class, 'id');
        $ref->setValue($user, 1);

        return $user;
    }
}
