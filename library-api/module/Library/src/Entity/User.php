<?php

declare(strict_types=1);

namespace Library\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Library\Repository\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'idx_users_role', columns: ['role'])]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $name;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: Types::STRING, length: 255)]
    private string $passwordHash;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: Role::class)]
    private Role $role;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    public function __construct(string $name, string $email, string $passwordHash, Role $role, DateTimeImmutable $createdAt)
    {
        $this->name         = $name;
        $this->email        = self::normalizeEmail($email);
        $this->passwordHash = $passwordHash;
        $this->role         = $role;
        $this->createdAt    = $createdAt;
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = self::normalizeEmail($email);
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): void
    {
        $this->role = $role;
    }

    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function markDeleted(DateTimeImmutable $at): void
    {
        $this->deletedAt = $at;
    }
}
