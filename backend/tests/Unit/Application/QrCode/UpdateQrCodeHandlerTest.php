<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\QrCode;

use App\Application\QrCode\Update\UpdateQrCodeCommand;
use App\Application\QrCode\Update\UpdateQrCodeHandler;
use App\Domain\QrCode\Exception\QrCodeForbiddenException;
use App\Domain\QrCode\Exception\QrCodeNotFoundException;
use App\Domain\QrCode\Repository\QrCodeRepositoryInterface;
use App\Entity\AdminUser;
use App\Entity\QrCode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateQrCodeHandlerTest extends TestCase
{
    private QrCodeRepositoryInterface&MockObject $qrCodes;
    private UpdateQrCodeHandler $handler;

    protected function setUp(): void
    {
        $this->qrCodes = $this->createMock(QrCodeRepositoryInterface::class);
        $this->handler = new UpdateQrCodeHandler($this->qrCodes);
    }

    public function testUpdatesTargetUrl(): void
    {
        $qr = $this->makeQr('slug', userId: 5);
        $this->qrCodes->method('findById')->willReturn($qr);
        $this->qrCodes->expects(self::once())->method('save');

        $dto = $this->handler->handle(new UpdateQrCodeCommand('slug', authUserId: 5, targetUrl: 'https://new.url'));

        self::assertSame('https://new.url', $dto->targetUrl);
    }

    public function testUpdatesIsActive(): void
    {
        $qr = $this->makeQr('slug', userId: 5);
        $this->qrCodes->method('findById')->willReturn($qr);

        $dto = $this->handler->handle(new UpdateQrCodeCommand('slug', authUserId: 5, isActive: false));

        self::assertFalse($dto->isActive);
    }

    public function testThrowsWhenNotFound(): void
    {
        $this->qrCodes->method('findById')->willReturn(null);
        $this->expectException(QrCodeNotFoundException::class);

        $this->handler->handle(new UpdateQrCodeCommand('ghost', authUserId: 1));
    }

    public function testThrowsWhenNotOwner(): void
    {
        $qr = $this->makeQr('slug', userId: 5);
        $this->qrCodes->method('findById')->willReturn($qr);
        $this->expectException(QrCodeForbiddenException::class);

        $this->handler->handle(new UpdateQrCodeCommand('slug', authUserId: 99));
    }

    private function makeQr(string $id, int $userId): QrCode
    {
        $user = new AdminUser();
        $ref  = new \ReflectionProperty(AdminUser::class, 'id');
        $ref->setValue($user, $userId);

        $qr = (new QrCode())->setId($id)->setTargetUrl('https://original.url')->setUser($user);

        return $qr;
    }
}
