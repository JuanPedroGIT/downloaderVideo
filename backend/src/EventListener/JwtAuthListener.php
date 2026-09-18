<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Infrastructure\Security\JwtServiceInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Validates JWT tokens on all /api/admin/* routes and on POST /download.
 *
 * POST /download is restricted to the usernames listed in
 * DOWNLOAD_ALLOWED_USERNAMES: the worker performs the download with the
 * server-side YouTube cookies, so this endpoint must not be public.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
final class JwtAuthListener
{
    /** @param string[] $downloadAllowedUsernames */
    public function __construct(
        private readonly JwtServiceInterface $jwt,
        private readonly array $downloadAllowedUsernames = [],
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path    = $request->getPathInfo();

        $requiresAuth = str_starts_with($path, '/api/admin/')
            || ($path === '/download' && $request->isMethod('POST'));

        if (!$requiresAuth) {
            return;
        }

        $authHeader = $request->headers->get('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            $event->setResponse(new JsonResponse(['error' => 'Unauthorized: missing token.'], 401));
            return;
        }

        try {
            $payload = $this->jwt->decode(substr($authHeader, 7));
            $request->attributes->set('_jwt_user_id', $payload->sub);
            $request->attributes->set('_jwt_username', $payload->username);

            if ($path === '/download' && !in_array($payload->username, $this->downloadAllowedUsernames, true)) {
                $event->setResponse(new JsonResponse(['error' => 'This tool is restricted.'], 403));
            }
        } catch (\Throwable) {
            $event->setResponse(new JsonResponse(['error' => 'Unauthorized: invalid or expired token.'], 401));
        }
    }
}
