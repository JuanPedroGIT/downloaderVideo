<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class AdminUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true)]
    private string $username;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }

    public function getPasswordHash(): string { return $this->passwordHash; }
    public function setPasswordHash(string $hash): self { $this->passwordHash = $hash; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
