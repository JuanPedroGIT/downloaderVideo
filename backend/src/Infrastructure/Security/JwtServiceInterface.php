<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

interface JwtServiceInterface
{
    public function generate(int $userId, string $username): string;

    /**
     * Decodes and validates a JWT. Returns the payload as an object.
     *
     * @throws \RuntimeException on invalid or expired token.
     */
    public function decode(string $token): object;
}
