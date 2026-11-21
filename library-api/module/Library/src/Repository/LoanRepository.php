<?php

declare(strict_types=1);

namespace Library\Repository;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Library\Entity\Book;
use Library\Entity\Loan;
use Library\Entity\LoanStatus;
use Library\Entity\User;

class LoanRepository extends EntityRepository
{
    private const OPEN = [LoanStatus::Active->value, LoanStatus::Overdue->value];

    public function countOpenByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.user = :user')
            ->andWhere('l.status IN (:open)')
            ->setParameter('user', $user)
            ->setParameter('open', self::OPEN)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOpenByBook(Book $book): int
    {
        return (int) $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.book = :book')
            ->andWhere('l.status IN (:open)')
            ->setParameter('book', $book)
            ->setParameter('open', self::OPEN)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOverdueByUser(User $user, DateTimeImmutable $today): array
    {
        return $this->createQueryBuilder('l')
            ->addSelect('b')
            ->join('l.book', 'b')
            ->where('l.user = :user')
            ->andWhere('l.status IN (:open)')
            ->andWhere('l.dueAt < :today')
            ->setParameter('user', $user)
            ->setParameter('open', self::OPEN)
            ->setParameter('today', $today, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getResult();
    }

    public function markOverdue(DateTimeImmutable $today): int
    {
        return (int) $this->getEntityManager()->createQuery(
            'UPDATE ' . Loan::class . ' l SET l.status = :overdue WHERE l.status = :active AND l.dueAt < :today'
        )
            ->setParameter('overdue', LoanStatus::Overdue->value)
            ->setParameter('active', LoanStatus::Active->value)
            ->setParameter('today', $today, Types::DATE_IMMUTABLE)
            ->execute();
    }

    public function search(array $filters, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('l')
            ->addSelect('b', 'u', 'p')
            ->join('l.book', 'b')
            ->join('l.user', 'u')
            ->leftJoin('l.penalty', 'p')
            ->orderBy('l.borrowedAt', 'DESC')
            ->addOrderBy('l.id', 'DESC');

        if (! empty($filters['userId'])) {
            $qb->andWhere('u.id = :userId')->setParameter('userId', $filters['userId']);
        }
        if (! empty($filters['bookId'])) {
            $qb->andWhere('b.id = :bookId')->setParameter('bookId', $filters['bookId']);
        }
        $status = $filters['status'] ?? null;
        if ($status === 'open') {
            $qb->andWhere('l.status IN (:statuses)')->setParameter('statuses', self::OPEN);
        } elseif ($status !== null && $status !== '') {
            $qb->andWhere('l.status = :status')->setParameter('status', $status);
        }

        return Paginate::run($qb, $page, $limit);
    }
}
