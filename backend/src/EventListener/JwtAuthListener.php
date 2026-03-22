<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Infrastructure\Security\JwtServiceInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Validates JWT tokens on all /api/admin/* routes.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
final class JwtAuthListener
{
    public function __construct(private readonly JwtServiceInterface $jwt) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        if (!str_starts_with($path, '/api/admin/')) {
            return;
        }

        $authHeader = $event->getRequest()->headers->get('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            $event->setResponse(new JsonResponse(['error' => 'Unauthorized: missing token.'], 401));
            return;
        }

        try {
            $payload = $this->jwt->decode(substr($authHeader, 7));
            $event->getRequest()->attributes->set('_jwt_user_id', $payload->sub);
            $event->getRequest()->attributes->set('_jwt_username', $payload->username);
        } catch (\Throwable) {
            $event->setResponse(new JsonResponse(['error' => 'Unauthorized: invalid or expired token.'], 401));
        }
    }
}
