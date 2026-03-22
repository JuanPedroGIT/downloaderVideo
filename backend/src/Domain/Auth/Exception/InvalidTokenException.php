<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class InvalidTokenException extends \DomainException
{
    public function __construct(string $context = 'token')
    {
        parent::__construct(sprintf('Invalid or already used %s.', $context));
    }
}
