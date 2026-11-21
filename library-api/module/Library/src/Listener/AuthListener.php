<?php

declare(strict_types=1);

namespace Library\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Library\Controller\JsonResponder;
use Library\Exception\UnauthorizedException;
use Library\Service\AuthContext;
use Library\Service\AuthService;

class AuthListener extends AbstractListenerAggregate
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly AuthContext $context
    ) {
    }

    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'onRoute'], -100);
    }

    public function onRoute(MvcEvent $e): mixed
    {
        $request = $e->getRequest();
        $match   = $e->getRouteMatch();
        if (! $request instanceof Request || $match === null) {
            return null;
        }
        if (! str_starts_with((string) $match->getMatchedRouteName(), 'api/') || $match->getParam('public', false)) {
            return null;
        }

        try {
            $header = $request->getHeaders()->get('Authorization');
            $value  = $header !== false ? trim($header->getFieldValue()) : '';
            if (! preg_match('/^Bearer\s+(\S+)$/i', $value, $m)) {
                throw new UnauthorizedException('Missing bearer token.');
            }
            $this->context->setUser($this->auth->userFromToken($m[1]));

            return null;
        } catch (UnauthorizedException $ex) {
            $response = JsonResponder::error($e->getResponse(), $ex);
            $e->setResult($response);

            return $response;
        }
    }
}
