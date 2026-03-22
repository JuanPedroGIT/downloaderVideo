<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\JwtService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Validates JWT tokens on all /api/admin/* routes except /api/admin/login.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
final class JwtAuthListener
{
    public function __construct(private readonly JwtService $jwtService) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        if (!str_starts_with($path, '/api/admin/') || $path === '/api/admin/login') {
            return;
        }

        $authHeader = $event->getRequest()->headers->get('Authorization', '');

        if (!str_starts_with($authHeader, 'Bearer ')) {
            $event->setResponse(new JsonResponse(['error' => 'Unauthorized: missing token.'], 401));
            return;
        }

        try {
            $this->jwtService->decode(substr($authHeader, 7));
        } catch (\Throwable) {
            $event->setResponse(new JsonResponse(['error' => 'Unauthorized: invalid or expired token.'], 401));
        }
    }
}
