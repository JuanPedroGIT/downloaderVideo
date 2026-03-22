<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\QrCode\Create\CreateQrCodeCommand;
use App\Application\QrCode\Create\CreateQrCodeHandler;
use App\Application\QrCode\Delete\DeleteQrCodeCommand;
use App\Application\QrCode\Delete\DeleteQrCodeHandler;
use App\Application\QrCode\List\ListQrCodesHandler;
use App\Application\QrCode\List\ListQrCodesQuery;
use App\Application\QrCode\Update\UpdateQrCodeCommand;
use App\Application\QrCode\Update\UpdateQrCodeHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
final class AdminController
{
    public function __construct(
        private readonly ListQrCodesHandler $listHandler,
        private readonly CreateQrCodeHandler $createHandler,
        private readonly UpdateQrCodeHandler $updateHandler,
        private readonly DeleteQrCodeHandler $deleteHandler,
    ) {}

    #[Route('/qrcodes', name: 'admin_qr_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $dtos = $this->listHandler->handle(
            new ListQrCodesQuery($this->authUserId($request))
        );

        return new JsonResponse(array_map(
            static fn ($dto) => (array) $dto,
            $dtos
        ));
    }

    #[Route('/qrcodes', name: 'admin_qr_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data      = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $id        = trim((string) ($data['id']        ?? ''));
        $targetUrl = trim((string) ($data['targetUrl'] ?? ''));

        if ($id === '' || $targetUrl === '') {
            return new JsonResponse(['error' => '"id" and "targetUrl" are required.'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($id) > 20) {
            return new JsonResponse(['error' => '"id" must be 20 characters or fewer.'], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            return new JsonResponse(['error' => '"targetUrl" must be a valid URL.'], Response::HTTP_BAD_REQUEST);
        }

        $dto = $this->createHandler->handle(
            new CreateQrCodeCommand($id, $targetUrl, $this->authUserId($request))
        );

        return new JsonResponse((array) $dto, Response::HTTP_CREATED);
    }

    #[Route('/qrcodes/{id}', name: 'admin_qr_update', methods: ['PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $targetUrl = isset($data['targetUrl']) ? trim((string) $data['targetUrl']) : null;
        $isActive  = isset($data['isActive'])  ? (bool) $data['isActive'] : null;

        if ($targetUrl !== null && !filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            return new JsonResponse(['error' => '"targetUrl" must be a valid URL.'], Response::HTTP_BAD_REQUEST);
        }

        $dto = $this->updateHandler->handle(
            new UpdateQrCodeCommand($id, $this->authUserId($request), $targetUrl, $isActive)
        );

        return new JsonResponse((array) $dto);
    }

    #[Route('/qrcodes/{id}', name: 'admin_qr_delete', methods: ['DELETE'])]
    public function delete(string $id, Request $request): JsonResponse
    {
        $this->deleteHandler->handle(
            new DeleteQrCodeCommand($id, $this->authUserId($request))
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function authUserId(Request $request): int
    {
        return (int) $request->attributes->get('_jwt_user_id');
    }
}
