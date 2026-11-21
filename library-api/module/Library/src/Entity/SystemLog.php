<?php

declare(strict_types=1);

namespace Library\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Library\Repository\SystemLogRepository;

#[ORM\Entity(repositoryClass: SystemLogRepository::class)]
#[ORM\Table(name: 'system_logs')]
#[ORM\Index(name: 'idx_logs_action', columns: ['action_type'])]
#[ORM\Index(name: 'idx_logs_entity', columns: ['entity_type', 'entity_id'])]
#[ORM\Index(name: 'idx_logs_created', columns: ['created_at'])]
class SystemLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'actor_id', nullable: true)]
    private ?User $actor;

    #[ORM\Column(name: 'action_type', type: Types::STRING, length: 50)]
    private string $actionType;

    #[ORM\Column(name: 'entity_type', type: Types::STRING, length: 50)]
    private string $entityType;

    #[ORM\Column(name: 'entity_id', type: Types::INTEGER, nullable: true)]
    private ?int $entityId;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $details;

    public function __construct(
        ?User $actor,
        string $actionType,
        string $entityType,
        ?int $entityId,
        DateTimeImmutable $createdAt,
        array $details = []
    ) {
        $this->actor      = $actor;
        $this->actionType = $actionType;
        $this->entityType = $entityType;
        $this->entityId   = $entityId;
        $this->createdAt  = $createdAt;
        $this->details    = $details === [] ? null : $details;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDetails(): array
    {
        return $this->details ?? [];
    }
}
