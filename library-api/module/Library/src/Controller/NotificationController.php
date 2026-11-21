<?php

declare(strict_types=1);

namespace Library\Controller;

use Laminas\Http\Response;
use Library\Service\AuthContext;
use Library\Service\NotificationService;
use Library\Service\Presenter;

class NotificationController extends AbstractApiController
{
    public function __construct(
        AuthContext $auth,
        private readonly NotificationService $notifications,
        private readonly Presenter $presenter
    ) {
        parent::__construct($auth);
    }

    public function getList(): Response
    {
        $result = $this->notifications->listFor($this->currentUser(), $this->queryBool('unread') ?? false);

        return $this->json([
            'items'       => array_map($this->presenter->notification(...), $result['items']),
            'unreadCount' => $result['unreadCount'],
        ]);
    }

    public function readAction(): Response
    {
        $this->allowMethods('POST');
        $notification = $this->notifications->markRead($this->currentUser(), $this->routeId());

        return $this->json($this->presenter->notification($notification));
    }

    public function readAllAction(): Response
    {
        $this->allowMethods('POST');

        return $this->json(['updated' => $this->notifications->markAllRead($this->currentUser())]);
    }
}
