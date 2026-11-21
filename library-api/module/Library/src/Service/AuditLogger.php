<?php

declare(strict_types=1);

namespace Library\Service;

use Doctrine\ORM\EntityManagerInterface;
use Library\Entity\SystemLog;
use Library\Entity\User;

class AuditLogger
{
    public const BOOK_BORROWED   = 'BOOK_BORROWED';
    public const BOOK_RETURNED   = 'BOOK_RETURNED';
    public const PENALTY_ISSUED  = 'PENALTY_ISSUED';
    public const PENALTY_PAID    = 'PENALTY_PAID';

    public const BOOK_CREATED    = 'BOOK_CREATED';
    public const BOOK_UPDATED    = 'BOOK_UPDATED';
    public const BOOK_DELETED    = 'BOOK_DELETED';

    public const USER_CREATED    = 'USER_CREATED';
    public const USER_UPDATED    = 'USER_UPDATED';
    public const USER_DELETED    = 'USER_DELETED';

    public const SETTINGS_UPDATED = 'SETTINGS_UPDATED';

    public const ACTION_TYPES = [
        self::BOOK_BORROWED, self::BOOK_RETURNED, self::PENALTY_ISSUED, self::PENALTY_PAID,
        self::BOOK_CREATED, self::BOOK_UPDATED, self::BOOK_DELETED,
        self::USER_CREATED, self::USER_UPDATED, self::USER_DELETED,
        self::SETTINGS_UPDATED,
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $clock
    ) {
    }

    public function log(?User $actor, string $actionType, string $entityType, ?int $entityId, array $details = []): void
    {
        $this->em->persist(new SystemLog($actor, $actionType, $entityType, $entityId, $this->clock->now(), $details));
    }
}
