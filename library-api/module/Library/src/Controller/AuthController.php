<?php

declare(strict_types=1);

namespace Library\Controller;

use Laminas\Http\Response;
use Library\Service\AuthContext;
use Library\Service\AuthService;
use Library\Service\PenaltyService;
use Library\Service\Presenter;

class AuthController extends AbstractApiController
{
    public function __construct(
        AuthContext $auth,
        private readonly AuthService $authService,
        private readonly PenaltyService $penalties,
        private readonly Presenter $presenter
    ) {
        parent::__construct($auth);
    }

    public function loginAction(): Response
    {
        $this->allowMethods('POST');
        $result = $this->authService->login($this->jsonBody());

        return $this->json([
            'token'     => $result['token'],
            'tokenType' => 'Bearer',
            'expiresAt' => $result['expiresAt'],
            'user'      => $this->presenter->user($result['user']),
        ]);
    }

    public function meAction(): Response
    {
        $this->allowMethods('GET');
        $user = $this->currentUser();
        $data = $this->presenter->user($user);
        if (! $user->isStaff()) {
            $data['unpaidPenaltyTotal'] = $this->penalties->unpaidTotalFor($user);
        }

        return $this->json($data);
    }
}
