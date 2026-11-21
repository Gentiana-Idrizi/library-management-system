<?php

declare(strict_types=1);

namespace Library\Repository;

use Doctrine\ORM\EntityRepository;
use Library\Entity\Notification;
use Library\Entity\User;

class NotificationRepository extends EntityRepository
{
    public function existingDedupeKeys(User $user): array
    {
        $rows = $this->createQueryBuilder('n')
            ->select('n.dedupeKey')
            ->where('n.user = :user')
            ->andWhere('n.dedupeKey IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_map('strval', $rows));
    }

    public function findForUser(User $user, bool $unreadOnly, int $limit): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->addOrderBy('n.id', 'DESC')
            ->setMaxResults($limit);
        if ($unreadOnly) {
            $qb->andWhere('n.isRead = false');
        }
        return $qb->getQuery()->getResult();
    }

    public function countUnread(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAllRead(User $user): int
    {
        return (int) $this->getEntityManager()->createQuery(
            'UPDATE ' . Notification::class . ' n SET n.isRead = true WHERE n.user = :user AND n.isRead = false'
        )->setParameter('user', $user)->execute();
    }

    public function markReadByDedupeKey(User $user, string $dedupeKey): int
    {
        return (int) $this->getEntityManager()->createQuery(
            'UPDATE ' . Notification::class . ' n SET n.isRead = true WHERE n.user = :user AND n.dedupeKey = :key'
        )->setParameter('user', $user)->setParameter('key', $dedupeKey)->execute();
    }
}
