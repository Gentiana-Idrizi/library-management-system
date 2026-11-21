<?php

declare(strict_types=1);

namespace Library\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

final class Paginate
{
    public static function run(QueryBuilder $qb, int $page, int $limit): array
    {
        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);
        $paginator = new Paginator($qb->getQuery(), true);

        return [
            'items' => iterator_to_array($paginator->getIterator(), false),
            'total' => count($paginator),
        ];
    }
}
