<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AdminUser;
use App\Entity\QrCode;
use App\Service\JwtService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
final class AdminController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly JwtService $jwtService,
    ) {}

    // ── Auth ─────────────────────────────────────────────────────────────────

    #[Route('/login', name: 'admin_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $password === '') {
            return new JsonResponse(['error' => 'Username and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->em->getRepository(AdminUser::class)->findOneBy(['username' => $username]);

        if (!$user || !password_verify($password, $user->getPasswordHash())) {
            return new JsonResponse(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->isVerified() && $user->getEmail() !== null) {
            return new JsonResponse(['error' => 'Please verify your email before logging in.'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'token'     => $this->jwtService->generate($user->getId(), $user->getUsername()),
            'expiresIn' => 86400,
            'username'  => $user->getUsername(),
        ]);
    }

    // ── QR codes ─────────────────────────────────────────────────────────────

    #[Route('/qrcodes', name: 'admin_qr_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user    = $this->getAuthUser($request);
        $qrCodes = $this->em->getRepository(QrCode::class)->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return new JsonResponse(array_map($this->serialize(...), $qrCodes));
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

        if ($this->em->getRepository(QrCode::class)->find($id)) {
            return new JsonResponse(['error' => "QR code with id \"{$id}\" already exists."], Response::HTTP_CONFLICT);
        }

        $user = $this->getAuthUser($request);
        $qr   = (new QrCode())->setId($id)->setTargetUrl($targetUrl)->setUser($user);
        $this->em->persist($qr);
        $this->em->flush();

        return new JsonResponse($this->serialize($qr), Response::HTTP_CREATED);
    }

    #[Route('/qrcodes/{id}', name: 'admin_qr_update', methods: ['PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $qr = $this->em->getRepository(QrCode::class)->find($id);

        if (!$qr) {
            return new JsonResponse(['error' => 'QR code not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($qr->getUser()?->getId() !== $this->getAuthUserId($request)) {
            return new JsonResponse(['error' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        if (isset($data['targetUrl'])) {
            $url = trim((string) $data['targetUrl']);
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return new JsonResponse(['error' => '"targetUrl" must be a valid URL.'], Response::HTTP_BAD_REQUEST);
            }
            $qr->setTargetUrl($url);
        }

        if (isset($data['isActive'])) {
            $qr->setIsActive((bool) $data['isActive']);
        }

        $this->em->flush();

        return new JsonResponse($this->serialize($qr));
    }

    #[Route('/qrcodes/{id}', name: 'admin_qr_delete', methods: ['DELETE'])]
    public function delete(string $id, Request $request): JsonResponse
    {
        $qr = $this->em->getRepository(QrCode::class)->find($id);

        if (!$qr) {
            return new JsonResponse(['error' => 'QR code not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($qr->getUser()?->getId() !== $this->getAuthUserId($request)) {
            return new JsonResponse(['error' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
        }

        $this->em->remove($qr);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getAuthUserId(Request $request): int
    {
        return (int) $request->attributes->get('_jwt_user_id');
    }

    private function getAuthUser(Request $request): AdminUser
    {
        return $this->em->getRepository(AdminUser::class)->find($this->getAuthUserId($request));
    }

    private function serialize(QrCode $qr): array
    {
        return [
            'id'        => $qr->getId(),
            'targetUrl' => $qr->getTargetUrl(),
            'clicks'    => $qr->getClicks(),
            'isActive'  => $qr->isActive(),
            'createdAt' => $qr->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
