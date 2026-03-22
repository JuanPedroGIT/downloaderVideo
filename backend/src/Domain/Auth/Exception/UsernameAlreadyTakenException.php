<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class UsernameAlreadyTakenException extends \DomainException
{
    public function __construct(string $username)
    {
        parent::__construct(sprintf('Username "%s" is already taken.', $username));
    }
}
