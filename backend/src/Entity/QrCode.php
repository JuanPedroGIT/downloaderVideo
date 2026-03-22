<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class QrCode
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 20)]
    private ?string $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $targetUrl = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $clicks = 0;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(targetEntity: AdminUser::class, inversedBy: 'qrCodes')]
    #[ORM\JoinColumn(name: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?AdminUser $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }

    public function setTargetUrl(string $targetUrl): self
    {
        $this->targetUrl = $targetUrl;

        return $this;
    }

    public function getClicks(): int
    {
        return $this->clicks;
    }

    public function incrementClicks(): self
    {
        $this->clicks++;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUser(): ?AdminUser { return $this->user; }
    public function setUser(?AdminUser $user): self { $this->user = $user; return $this; }
}
