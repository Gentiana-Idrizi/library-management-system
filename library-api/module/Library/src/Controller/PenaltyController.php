<?php

declare(strict_types=1);

namespace Library\Controller;

use Laminas\Http\Response;
use Library\Service\AuthContext;
use Library\Service\PenaltyService;
use Library\Service\Presenter;

class PenaltyController extends AbstractApiController
{
    public function __construct(
        AuthContext $auth,
        private readonly PenaltyService $penalties,
        private readonly Presenter $presenter
    ) {
        parent::__construct($auth);
    }

    public function getList(): Response
    {
        $actor          = $this->currentUser();
        [$page, $limit] = $this->paging();
        $result         = $this->penalties->search($actor, [
            'userId' => $this->queryInt('userId'),
            'paid'   => $this->queryBool('paid'),
        ], $page, $limit);

        $body = $this->presenter->page($result, $this->presenter->penalty(...), $page, $limit);
        if (! $actor->isStaff()) {
            $body['unpaidTotal'] = $this->penalties->unpaidTotalFor($actor);
        }

        return $this->json($body);
    }

    public function get($id): Response
    {
        return $this->json($this->presenter->penalty($this->penalties->get($this->currentUser(), (int) $id)));
    }

    public function payAction(): Response
    {
        $this->allowMethods('POST');
        $penalty = $this->penalties->pay($this->currentUser(), $this->routeId());

        return $this->json([
            'message' => sprintf('Penalty of €%s paid. The loan is now closed.', $penalty->getAmount()),
            'penalty' => $this->presenter->penalty($penalty),
        ]);
    }
}
