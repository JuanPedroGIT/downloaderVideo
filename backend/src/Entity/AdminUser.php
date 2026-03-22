<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: QrCode::class)]
    private Collection $qrCodes;

    // ── Email & verification ──────────────────────────────────────────────────

    #[ORM\Column(type: Types::STRING, length: 180, unique: true, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isVerified = false;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $verificationTokenExpires = null;

    // ── Password reset ────────────────────────────────────────────────────────

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $resetTokenExpires = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->qrCodes   = new ArrayCollection();
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getId(): ?int { return $this->id; }

    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }

    public function getPasswordHash(): string { return $this->passwordHash; }
    public function setPasswordHash(string $hash): self { $this->passwordHash = $hash; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getQrCodes(): Collection { return $this->qrCodes; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function isVerified(): bool { return $this->isVerified; }
    public function setIsVerified(bool $verified): self { $this->isVerified = $verified; return $this; }

    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(?string $token): self { $this->verificationToken = $token; return $this; }

    public function getVerificationTokenExpires(): ?\DateTimeImmutable { return $this->verificationTokenExpires; }
    public function setVerificationTokenExpires(?\DateTimeImmutable $dt): self { $this->verificationTokenExpires = $dt; return $this; }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $token): self { $this->resetToken = $token; return $this; }

    public function getResetTokenExpires(): ?\DateTimeImmutable { return $this->resetTokenExpires; }
    public function setResetTokenExpires(?\DateTimeImmutable $dt): self { $this->resetTokenExpires = $dt; return $this; }

    // ── Token helpers ─────────────────────────────────────────────────────────

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function clearVerificationToken(): void
    {
        $this->verificationToken        = null;
        $this->verificationTokenExpires = null;
    }

    public function clearResetToken(): void
    {
        $this->resetToken        = null;
        $this->resetTokenExpires = null;
    }
}
