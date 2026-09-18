<?php

declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\Security\JwtServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class MeController
{
    /** @param string[] $downloadAllowedUsernames */
    public function __construct(
        private readonly JwtServiceInterface $jwt,
        private readonly array $downloadAllowedUsernames,
    ) {}

    #[Route('/api/auth/me', name: 'auth_me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $authHeader = $request->headers->get('Authorization', '');

        if (str_starts_with($authHeader, 'Bearer ')) {
            try {
                $payload = $this->jwt->decode(substr($authHeader, 7));

                return new JsonResponse([
                    'username'    => $payload->username,
                    'canDownload' => in_array($payload->username, $this->downloadAllowedUsernames, true),
                ]);
            } catch (\Throwable) {
                // Invalid token → treated as anonymous below
            }
        }

        return new JsonResponse(['username' => null, 'canDownload' => false]);
    }
}
