<?php

declare(strict_types=1);

namespace Library\Controller;

use Laminas\Http\Response;
use Library\Entity\Role;
use Library\Service\AuthContext;
use Library\Service\SettingsService;

class SettingsController extends AbstractApiController
{
    public function __construct(AuthContext $auth, private readonly SettingsService $settings)
    {
        parent::__construct($auth);
    }

    public function getList(): Response
    {
        $this->currentUser();

        return $this->json($this->settings->toArray());
    }

    public function replaceList($data): Response
    {
        return $this->save((array) $data);
    }

    public function patchList($data): Response
    {
        return $this->save((array) $data);
    }

    private function save(array $data): Response
    {
        $actor   = $this->requireRole(Role::SuperAdmin);
        $changes = $this->settings->update($actor, $data);

        return $this->json($this->settings->toArray() + ['changed' => array_keys($changes)]);
    }
}
