<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth;

use App\Application\Auth\ResetPassword\ResetPasswordCommand;
use App\Application\Auth\ResetPassword\ResetPasswordHandler;
use App\Domain\Auth\Exception\InvalidTokenException;
use App\Domain\Auth\Exception\TokenExpiredException;
use App\Domain\Auth\Repository\AdminUserRepositoryInterface;
use App\Entity\AdminUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ResetPasswordHandlerTest extends TestCase
{
    private AdminUserRepositoryInterface&MockObject $users;
    private ResetPasswordHandler $handler;

    protected function setUp(): void
    {
        $this->users   = $this->createMock(AdminUserRepositoryInterface::class);
        $this->handler = new ResetPasswordHandler($this->users);
    }

    public function testSuccessfulPasswordReset(): void
    {
        $user = (new AdminUser())
            ->setPasswordHash(password_hash('oldpassword', PASSWORD_BCRYPT))
            ->setResetToken('validtoken')
            ->setResetTokenExpires(new \DateTimeImmutable('+1 hour'));

        $this->users->method('findByResetToken')->with('validtoken')->willReturn($user);
        $this->users->expects(self::once())->method('save');

        $this->handler->handle(new ResetPasswordCommand('validtoken', 'newpassword123'));

        self::assertTrue(password_verify('newpassword123', $user->getPasswordHash()));
        self::assertNull($user->getResetToken());
    }

    public function testThrowsOnInvalidToken(): void
    {
        $this->users->method('findByResetToken')->willReturn(null);
        $this->expectException(InvalidTokenException::class);

        $this->handler->handle(new ResetPasswordCommand('badtoken', 'newpassword123'));
    }

    public function testThrowsOnExpiredToken(): void
    {
        $user = (new AdminUser())
            ->setResetToken('expiredtoken')
            ->setResetTokenExpires(new \DateTimeImmutable('-1 hour'));

        $this->users->method('findByResetToken')->willReturn($user);
        $this->expectException(TokenExpiredException::class);

        $this->handler->handle(new ResetPasswordCommand('expiredtoken', 'newpassword123'));
    }
}
