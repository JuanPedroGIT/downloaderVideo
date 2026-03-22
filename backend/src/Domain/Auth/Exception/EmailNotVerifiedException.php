<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

final class EmailNotVerifiedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Please verify your email before logging in.');
    }
}
