<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

/**
 * Minimal HS256 JWT implementation using PHP's native hash_hmac.
 * No external library required.
 */
final class JwtService implements JwtServiceInterface
{
    private const TTL = 86400; // 24 hours

    public function __construct(private readonly string $secret) {}

    public function generate(int $userId, string $username): string
    {
        $now = time();

        $header  = $this->b64u(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $payload = $this->b64u(json_encode([
            'sub'      => $userId,
            'username' => $username,
            'iat'      => $now,
            'exp'      => $now + self::TTL,
        ], JSON_THROW_ON_ERROR));

        $sig = $this->b64u(hash_hmac('sha256', "{$header}.{$payload}", $this->secret, true));

        return "{$header}.{$payload}.{$sig}";
    }

    public function decode(string $token): object
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Malformed token.');
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;

        $expected = $this->b64u(hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $this->secret, true));
        if (!hash_equals($expected, $sigB64)) {
            throw new \RuntimeException('Invalid token signature.');
        }

        $payload = json_decode(base64_decode(strtr($payloadB64, '-_', '+/')), false, 512, JSON_THROW_ON_ERROR);

        if (!isset($payload->exp) || $payload->exp < time()) {
            throw new \RuntimeException('Token has expired.');
        }

        return $payload;
    }

    private function b64u(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
