<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class TokenExpiredException extends \DomainException
{
    public function __construct(string $context = 'token')
    {
        parent::__construct(sprintf('The %s has expired.', $context));
    }
}
