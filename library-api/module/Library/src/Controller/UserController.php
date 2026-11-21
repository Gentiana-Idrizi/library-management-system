<?php

declare(strict_types=1);

namespace Library\Controller;

use Laminas\Http\Response;
use Library\Entity\Role;
use Library\Service\AuthContext;
use Library\Service\LoanService;
use Library\Service\PenaltyService;
use Library\Service\Presenter;
use Library\Service\UserService;

class UserController extends AbstractApiController
{
    public function __construct(
        AuthContext $auth,
        private readonly UserService $users,
        private readonly LoanService $loans,
        private readonly PenaltyService $penalties,
        private readonly Presenter $presenter
    ) {
        parent::__construct($auth);
    }

    public function getList(): Response
    {
        $actor          = $this->requireStaff();
        [$page, $limit] = $this->paging();
        $result         = $this->users->search($actor, $this->query('role'), $this->query('q'), $page, $limit);

        return $this->json($this->presenter->page($result, $this->presenter->user(...), $page, $limit));
    }

    public function get($id): Response
    {
        return $this->json($this->presenter->user($this->users->get($this->requireStaff(), (int) $id)));
    }

    public function historyAction(): Response
    {
        $this->allowMethods('GET');
        $actor          = $this->requireStaff();
        $student        = $this->users->get($actor, $this->routeId());
        [$page, $limit] = $this->paging(50);

        $loans   = $this->loans->search($actor, ['userId' => $student->getId()], $page, $limit);
        $unpaid  = $this->penalties->search($actor, ['userId' => $student->getId(), 'paid' => false], 1, 100);

        return $this->json([
            'user'               => $this->presenter->user($student),
            'unpaidPenaltyTotal' => $this->penalties->unpaidTotalFor($student),
            'unpaidPenalties'    => array_map($this->presenter->penalty(...), $unpaid['items']),
            'loans'              => $this->presenter->page($loans, $this->presenter->loan(...), $page, $limit),
        ]);
    }

    public function create($data): Response
    {
        $actor = $this->requireRole(Role::SuperAdmin);

        return $this->json($this->presenter->user($this->users->create($actor, (array) $data)), 201);
    }

    public function update($id, $data): Response
    {
        $actor = $this->requireRole(Role::SuperAdmin);

        return $this->json($this->presenter->user($this->users->update($actor, (int) $id, (array) $data, partial: false)));
    }

    public function patch($id, $data): Response
    {
        $actor = $this->requireRole(Role::SuperAdmin);

        return $this->json($this->presenter->user($this->users->update($actor, (int) $id, (array) $data, partial: true)));
    }

    public function delete($id): Response
    {
        $actor = $this->requireRole(Role::SuperAdmin);
        $this->users->delete($actor, (int) $id);

        return $this->noContent();
    }
}
