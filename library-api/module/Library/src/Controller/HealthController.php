<?php

declare(strict_types=1);

namespace Library\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Laminas\Http\Response;
use Library\Service\AuthContext;

class HealthController extends AbstractApiController
{
    public function __construct(AuthContext $auth, private readonly EntityManagerInterface $em)
    {
        parent::__construct($auth);
    }

    public function getList(): Response
    {
        $this->em->getConnection()->executeQuery('SELECT 1');

        return $this->json(['status' => 'ok', 'database' => 'ok']);
    }
}
