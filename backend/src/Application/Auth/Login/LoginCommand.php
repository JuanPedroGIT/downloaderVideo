<?php

declare(strict_types=1);

namespace App\Application\Auth\Login;

final readonly class LoginCommand
{
    public function __construct(
        public string $username,
        public string $password,
    ) {}
}
