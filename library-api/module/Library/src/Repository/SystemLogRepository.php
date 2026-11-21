<?php

declare(strict_types=1);

namespace Library\Repository;

use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;
use Library\Entity\SystemLog;

class SystemLogRepository extends EntityRepository
{
    public function search(array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('s')
            ->addSelect('a')
            ->leftJoin('s.actor', 'a')
            ->orderBy('s.createdAt', 'DESC')
            ->addOrderBy('s.id', 'DESC');

        if (! empty($filters['actionType'])) {
            $qb->andWhere('s.actionType = :action')->setParameter('action', $filters['actionType']);
        }
        if (! empty($filters['entityType'])) {
            $qb->andWhere('s.entityType = :entity')->setParameter('entity', $filters['entityType']);
        }
        if (! empty($filters['actorId'])) {
            $qb->andWhere('a.id = :actor')->setParameter('actor', $filters['actorId']);
        }
        if (! empty($filters['from'])) {
            $qb->andWhere('s.createdAt >= :from')->setParameter('from', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $qb->andWhere('s.createdAt < :to')->setParameter('to', $filters['to']);
        }

        return Paginate::run($qb, $page, $limit);
    }
}
