<?php

declare(strict_types=1);

namespace App\Domain\QrCode\Exception;

final class QrCodeForbiddenException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('You do not have permission to modify this QR code.');
    }
}
