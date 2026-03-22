<?php

declare(strict_types=1);

namespace App\Domain\QrCode\Exception;

final class QrCodeAlreadyExistsException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('QR code with id "%s" already exists.', $id));
    }
}
