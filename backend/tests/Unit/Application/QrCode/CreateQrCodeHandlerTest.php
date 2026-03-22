<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\QrCode;

use App\Application\QrCode\Create\CreateQrCodeCommand;
use App\Application\QrCode\Create\CreateQrCodeHandler;
use App\Application\QrCode\QrCodeDto;
use App\Domain\Auth\Repository\AdminUserRepositoryInterface;
use App\Domain\QrCode\Exception\QrCodeAlreadyExistsException;
use App\Domain\QrCode\Repository\QrCodeRepositoryInterface;
use App\Entity\AdminUser;
use App\Entity\QrCode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CreateQrCodeHandlerTest extends TestCase
{
    private QrCodeRepositoryInterface&MockObject $qrCodes;
    private AdminUserRepositoryInterface&MockObject $users;
    private CreateQrCodeHandler $handler;

    protected function setUp(): void
    {
        $this->qrCodes = $this->createMock(QrCodeRepositoryInterface::class);
        $this->users   = $this->createMock(AdminUserRepositoryInterface::class);
        $this->handler = new CreateQrCodeHandler($this->qrCodes, $this->users);
    }

    public function testCreatesQrCode(): void
    {
        $this->qrCodes->method('findById')->willReturn(null);
        $this->users->method('findById')->willReturn(new AdminUser());
        $this->qrCodes->expects(self::once())->method('save');

        $dto = $this->handler->handle(new CreateQrCodeCommand('my-slug', 'https://example.com', 1));

        self::assertInstanceOf(QrCodeDto::class, $dto);
        self::assertSame('my-slug', $dto->id);
        self::assertSame('https://example.com', $dto->targetUrl);
        self::assertSame(0, $dto->clicks);
        self::assertTrue($dto->isActive);
    }

    public function testThrowsWhenIdAlreadyExists(): void
    {
        $this->qrCodes->method('findById')->willReturn(new QrCode());
        $this->expectException(QrCodeAlreadyExistsException::class);

        $this->handler->handle(new CreateQrCodeCommand('existing', 'https://example.com', 1));
    }
}
