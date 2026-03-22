<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth;

use App\Application\Auth\VerifyEmail\VerifyEmailCommand;
use App\Application\Auth\VerifyEmail\VerifyEmailHandler;
use App\Domain\Auth\Exception\InvalidTokenException;
use App\Domain\Auth\Exception\TokenExpiredException;
use App\Domain\Auth\Repository\AdminUserRepositoryInterface;
use App\Entity\AdminUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class VerifyEmailHandlerTest extends TestCase
{
    private AdminUserRepositoryInterface&MockObject $users;
    private VerifyEmailHandler $handler;

    protected function setUp(): void
    {
        $this->users   = $this->createMock(AdminUserRepositoryInterface::class);
        $this->handler = new VerifyEmailHandler($this->users);
    }

    public function testSuccessfulVerification(): void
    {
        $user = (new AdminUser())
            ->setVerificationToken('validtoken')
            ->setVerificationTokenExpires(new \DateTimeImmutable('+1 hour'));

        $this->users->method('findByVerificationToken')->with('validtoken')->willReturn($user);
        $this->users->expects(self::once())->method('save');

        $this->handler->handle(new VerifyEmailCommand('validtoken'));

        self::assertTrue($user->isVerified());
        self::assertNull($user->getVerificationToken());
    }

    public function testThrowsOnInvalidToken(): void
    {
        $this->users->method('findByVerificationToken')->willReturn(null);
        $this->expectException(InvalidTokenException::class);

        $this->handler->handle(new VerifyEmailCommand('badtoken'));
    }

    public function testThrowsOnExpiredToken(): void
    {
        $user = (new AdminUser())
            ->setVerificationToken('expiredtoken')
            ->setVerificationTokenExpires(new \DateTimeImmutable('-1 hour'));

        $this->users->method('findByVerificationToken')->willReturn($user);
        $this->expectException(TokenExpiredException::class);

        $this->handler->handle(new VerifyEmailCommand('expiredtoken'));
    }
}
