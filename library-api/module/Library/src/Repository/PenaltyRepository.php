<?php

declare(strict_types=1);

namespace Library\Repository;

use Doctrine\ORM\EntityRepository;
use Library\Entity\Penalty;
use Library\Entity\User;

class PenaltyRepository extends EntityRepository
{
    public function countUnpaidByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->join('p.loan', 'l')
            ->where('l.user = :user')
            ->andWhere('p.paid = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findUnpaidByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->addSelect('l', 'b')
            ->join('p.loan', 'l')
            ->join('l.book', 'b')
            ->where('l.user = :user')
            ->andWhere('p.paid = false')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function unpaidTotalForUser(User $user): string
    {
        $sum = $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.amount), 0)')
            ->join('p.loan', 'l')
            ->where('l.user = :user')
            ->andWhere('p.paid = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return number_format((float) $sum, 2, '.', '');
    }

    public function search(array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('l', 'b', 'u')
            ->join('p.loan', 'l')
            ->join('l.book', 'b')
            ->join('l.user', 'u')
            ->orderBy('p.createdAt', 'DESC')
            ->addOrderBy('p.id', 'DESC');

        if (! empty($filters['userId'])) {
            $qb->andWhere('u.id = :userId')->setParameter('userId', $filters['userId']);
        }
        if (isset($filters['paid'])) {
            $qb->andWhere('p.paid = :paid')->setParameter('paid', $filters['paid']);
        }

        return Paginate::run($qb, $page, $limit);
    }
}
