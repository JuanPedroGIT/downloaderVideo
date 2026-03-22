<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth;

use App\Application\Auth\Register\RegisterUserCommand;
use App\Application\Auth\Register\RegisterUserHandler;
use App\Domain\Auth\Exception\EmailAlreadyRegisteredException;
use App\Domain\Auth\Exception\UsernameAlreadyTakenException;
use App\Domain\Auth\Repository\AdminUserRepositoryInterface;
use App\Entity\AdminUser;
use App\Infrastructure\Email\MailerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RegisterUserHandlerTest extends TestCase
{
    private AdminUserRepositoryInterface&MockObject $users;
    private MailerInterface&MockObject $mailer;
    private RegisterUserHandler $handler;

    protected function setUp(): void
    {
        $this->users   = $this->createMock(AdminUserRepositoryInterface::class);
        $this->mailer  = $this->createMock(MailerInterface::class);
        $this->handler = new RegisterUserHandler($this->users, $this->mailer, 'http://localhost:5173');
    }

    public function testSuccessfulRegistration(): void
    {
        $this->users->method('findByEmail')->willReturn(null);
        $this->users->method('findByUsername')->willReturn(null);
        $this->users->expects(self::once())->method('save');
        $this->mailer->expects(self::once())->method('sendVerificationEmail');

        $this->handler->handle(new RegisterUserCommand('bob@example.com', 'bob', 'password123'));
    }

    public function testThrowsWhenEmailTaken(): void
    {
        $this->users->method('findByEmail')->willReturn(new AdminUser());
        $this->expectException(EmailAlreadyRegisteredException::class);

        $this->handler->handle(new RegisterUserCommand('taken@example.com', 'bob', 'password123'));
    }

    public function testThrowsWhenUsernameTaken(): void
    {
        $this->users->method('findByEmail')->willReturn(null);
        $this->users->method('findByUsername')->willReturn(new AdminUser());
        $this->expectException(UsernameAlreadyTakenException::class);

        $this->handler->handle(new RegisterUserCommand('new@example.com', 'takenuser', 'password123'));
    }

    public function testEmailFailureIsNonFatal(): void
    {
        $this->users->method('findByEmail')->willReturn(null);
        $this->users->method('findByUsername')->willReturn(null);
        $this->users->expects(self::once())->method('save');
        $this->mailer->method('sendVerificationEmail')->willThrowException(new \RuntimeException('SMTP error'));

        // Should not throw despite email failure
        $this->handler->handle(new RegisterUserCommand('bob@example.com', 'bob', 'password123'));
    }

    public function testVerificationUrlContainsDefaultUri(): void
    {
        $this->users->method('findByEmail')->willReturn(null);
        $this->users->method('findByUsername')->willReturn(null);
        $this->users->method('save');

        $this->mailer->expects(self::once())
            ->method('sendVerificationEmail')
            ->with(
                'bob@example.com',
                'bob',
                self::stringContains('http://localhost:5173/verify-email?token=')
            );

        $this->handler->handle(new RegisterUserCommand('bob@example.com', 'bob', 'password123'));
    }
}
