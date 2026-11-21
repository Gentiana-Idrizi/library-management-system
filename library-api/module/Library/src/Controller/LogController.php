<?php

declare(strict_types=1);

namespace Library\Controller;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Laminas\Http\Response;
use Library\Entity\Role;
use Library\Entity\SystemLog;
use Library\Exception\ValidationException;
use Library\Repository\SystemLogRepository;
use Library\Service\AuditLogger;
use Library\Service\AuthContext;
use Library\Service\Presenter;

class LogController extends AbstractApiController
{
    private SystemLogRepository $logs;

    public function __construct(
        AuthContext $auth,
        EntityManagerInterface $em,
        private readonly Presenter $presenter
    ) {
        parent::__construct($auth);
        $this->logs = $em->getRepository(SystemLog::class);
    }

    public function getList(): Response
    {
        $this->requireRole(Role::SuperAdmin);
        [$page, $limit] = $this->paging(50);

        $result = $this->logs->search([
            'actionType' => $this->query('actionType'),
            'entityType' => $this->query('entityType'),
            'actorId'    => $this->queryInt('actorId'),
            'from'       => $this->date('from'),
            'to'         => $this->date('to')?->modify('+1 day'),
        ], $page, $limit);

        return $this->json($this->presenter->page($result, $this->presenter->log(...), $page, $limit) + [
            'actionTypes' => AuditLogger::ACTION_TYPES,
        ]);
    }

    private function date(string $name): ?DateTimeImmutable
    {
        $value = $this->query($name);
        if ($value === null) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false) {
            throw new ValidationException([$name => 'Must be a date in the format YYYY-MM-DD.']);
        }

        return $date;
    }
}
