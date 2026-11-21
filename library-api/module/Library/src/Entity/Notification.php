<?php

declare(strict_types=1);

namespace Library\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Library\Repository\NotificationRepository;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notifications')]
#[ORM\Index(name: 'idx_notifications_user_read', columns: ['user_id', 'is_read'])]
#[ORM\UniqueConstraint(name: 'uniq_notifications_user_dedupe', columns: ['user_id', 'dedupe_key'])]
class Notification
{
    public const TYPE_OVERDUE         = 'overdue';
    public const TYPE_PENDING_PENALTY = 'pending_penalty';
    public const TYPE_LOW_STOCK       = 'low_stock';
    public const TYPE_OUT_OF_STOCK    = 'out_of_stock';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $type;

    #[ORM\Column(type: Types::STRING, length: 500)]
    private string $message;

    #[ORM\Column(name: 'is_read', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'dedupe_key', type: Types::STRING, length: 100, nullable: true)]
    private ?string $dedupeKey;

    public function __construct(
        User $user,
        string $type,
        string $message,
        DateTimeImmutable $createdAt,
        ?string $dedupeKey = null
    ) {
        $this->user      = $user;
        $this->type      = $type;
        $this->message   = $message;
        $this->createdAt = $createdAt;
        $this->dedupeKey = $dedupeKey;
    }

    public function markRead(): void
    {
        $this->isRead = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDedupeKey(): ?string
    {
        return $this->dedupeKey;
    }
}
