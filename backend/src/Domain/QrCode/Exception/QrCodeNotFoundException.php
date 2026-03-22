<?php

declare(strict_types=1);

namespace App\Domain\QrCode\Exception;

final class QrCodeNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('QR code "%s" not found.', $id));
    }
}
