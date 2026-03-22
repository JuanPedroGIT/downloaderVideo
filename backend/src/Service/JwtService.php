<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Minimal HS256 JWT implementation using PHP's native hash_hmac.
 * No external library required.
 */
final class JwtService
{
    private const TTL = 86400; // 24 hours

    private static string $header;

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

    /**
     * Decodes and validates a JWT. Returns the payload as an object.
     *
     * @throws \RuntimeException on invalid or expired token.
     */
    public function decode(string $token): object
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Malformed token.');
        }

        [$headerB64, $payloadB64, $sigB64] = $parts;

        // Verify signature
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

    /** Base64-URL encodes a string (RFC 4648 §5). */
    private function b64u(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
