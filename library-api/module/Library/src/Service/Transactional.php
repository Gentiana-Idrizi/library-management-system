<?php

declare(strict_types=1);

namespace Library\Service;

use Doctrine\ORM\EntityManagerInterface;
use Library\Exception\ApiException;
use Throwable;

trait Transactional
{
    abstract protected function entityManager(): EntityManagerInterface;

    protected function transactional(callable $work): mixed
    {
        $em   = $this->entityManager();
        $conn = $em->getConnection();
        $conn->beginTransaction();
        try {
            $result = $work();
            $em->flush();
            $conn->commit();
            return $result;
        } catch (Throwable $e) {
            if ($conn->isTransactionActive()) {
                $conn->rollBack();
            }

            if (! $e instanceof ApiException) {
                $em->clear();
            }
            throw $e;
        }
    }
}
