<?php

declare(strict_types=1);

namespace Library\Repository;

use Doctrine\ORM\EntityRepository;
use Library\Entity\Book;

class BookRepository extends EntityRepository
{
    public function search(?string $q, ?string $category, bool $availableOnly, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('b')
            ->where('b.deletedAt IS NULL')
            ->orderBy('b.title', 'ASC');

        if ($q !== null && $q !== '') {
            $qb->andWhere('LOWER(b.title) LIKE :q OR LOWER(b.author) LIKE :q OR LOWER(b.isbn) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }
        if ($category !== null && $category !== '') {
            $qb->andWhere('b.category = :category')->setParameter('category', $category);
        }
        if ($availableOnly) {
            $qb->andWhere('b.stock > 0');
        }

        return Paginate::run($qb, $page, $limit);
    }

    public function findActive(int $id): ?Book
    {
        $book = $this->find($id);
        return $book !== null && ! $book->isDeleted() ? $book : null;
    }

    public function findByIsbn(string $isbn): ?Book
    {
        return $this->findOneBy(['isbn' => $isbn]);
    }

    public function categories(): array
    {
        $rows = $this->createQueryBuilder('b')
            ->select('DISTINCT b.category')
            ->where('b.deletedAt IS NULL')
            ->orderBy('b.category', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_map('strval', $rows));
    }
}
