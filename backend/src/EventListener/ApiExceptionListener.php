<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Domain\Auth\Exception\EmailAlreadyRegisteredException;
use App\Domain\Auth\Exception\EmailNotVerifiedException;
use App\Domain\Auth\Exception\InvalidCredentialsException;
use App\Domain\Auth\Exception\InvalidTokenException;
use App\Domain\Auth\Exception\TokenExpiredException;
use App\Domain\Auth\Exception\UsernameAlreadyTakenException;
use App\Domain\QrCode\Exception\QrCodeAlreadyExistsException;
use App\Domain\QrCode\Exception\QrCodeForbiddenException;
use App\Domain\QrCode\Exception\QrCodeNotFoundException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final class ApiExceptionListener
{
    private const MAP = [
        // Auth
        InvalidCredentialsException::class      => Response::HTTP_UNAUTHORIZED,
        EmailNotVerifiedException::class         => Response::HTTP_FORBIDDEN,
        EmailAlreadyRegisteredException::class   => Response::HTTP_CONFLICT,
        UsernameAlreadyTakenException::class     => Response::HTTP_CONFLICT,
        InvalidTokenException::class             => Response::HTTP_NOT_FOUND,
        TokenExpiredException::class             => Response::HTTP_GONE,
        // QrCode
        QrCodeNotFoundException::class           => Response::HTTP_NOT_FOUND,
        QrCodeAlreadyExistsException::class      => Response::HTTP_CONFLICT,
        QrCodeForbiddenException::class          => Response::HTTP_FORBIDDEN,
        // Generic
        \JsonException::class                    => Response::HTTP_BAD_REQUEST,
    ];

    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $e = $event->getThrowable();

        foreach (self::MAP as $class => $status) {
            if ($e instanceof $class) {
                $event->setResponse(
                    new JsonResponse(['error' => $e->getMessage()], $status)
                );
                return;
            }
        }
    }
}
