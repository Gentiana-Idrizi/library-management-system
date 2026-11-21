<?php

declare(strict_types=1);

namespace Library\Repository;

use Doctrine\ORM\EntityRepository;
use Library\Entity\Role;
use Library\Entity\User;

class UserRepository extends EntityRepository
{
    public function findActiveByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('email', User::normalizeEmail($email))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.email = :email')
            ->setParameter('email', User::normalizeEmail($email));
        if ($exceptId !== null) {
            $qb->andWhere('u.id <> :id')->setParameter('id', $exceptId);
        }
        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function findActiveStaff(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.role IN (:roles)')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('roles', [Role::Operator->value, Role::SuperAdmin->value])
            ->getQuery()
            ->getResult();
    }

    public function search(array $roles, ?string $q, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.deletedAt IS NULL')
            ->andWhere('u.role IN (:roles)')
            ->setParameter('roles', $roles)
            ->orderBy('u.name', 'ASC');

        if ($q !== null && $q !== '') {
            $qb->andWhere('LOWER(u.name) LIKE :q OR LOWER(u.email) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        return Paginate::run($qb, $page, $limit);
    }
}
