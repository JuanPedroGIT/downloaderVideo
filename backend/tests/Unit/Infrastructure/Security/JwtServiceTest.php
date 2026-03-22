<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Security;

use App\Infrastructure\Security\JwtService;
use PHPUnit\Framework\TestCase;

final class JwtServiceTest extends TestCase
{
    private JwtService $service;

    protected function setUp(): void
    {
        $this->service = new JwtService('test_secret_key');
    }

    public function testGenerateAndDecode(): void
    {
        $token   = $this->service->generate(42, 'alice');
        $payload = $this->service->decode($token);

        self::assertSame(42, $payload->sub);
        self::assertSame('alice', $payload->username);
        self::assertGreaterThan(time(), $payload->exp);
    }

    public function testTokenHasThreeParts(): void
    {
        $token = $this->service->generate(1, 'test');
        self::assertCount(3, explode('.', $token));
    }

    public function testThrowsOnMalformedToken(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Malformed token.');
        $this->service->decode('not.a.valid.token.parts');
    }

    public function testThrowsOnInvalidSignature(): void
    {
        $token  = $this->service->generate(1, 'alice');
        $tampered = substr($token, 0, -5) . 'XXXXX';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid token signature.');
        $this->service->decode($tampered);
    }

    public function testThrowsOnExpiredToken(): void
    {
        // Generate with a service that uses past expiry by mocking time
        // We directly craft an expired payload instead
        $header  = $this->b64u(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = $this->b64u(json_encode(['sub' => 1, 'username' => 'x', 'iat' => 1000, 'exp' => 1001]));
        $sig     = $this->b64u(hash_hmac('sha256', "{$header}.{$payload}", 'test_secret_key', true));
        $expired = "{$header}.{$payload}.{$sig}";

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Token has expired.');
        $this->service->decode($expired);
    }

    public function testDifferentSecretsProduceInvalidSignature(): void
    {
        $otherService = new JwtService('different_secret');
        $token        = $otherService->generate(1, 'alice');

        $this->expectException(\RuntimeException::class);
        $this->service->decode($token);
    }

    private function b64u(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
