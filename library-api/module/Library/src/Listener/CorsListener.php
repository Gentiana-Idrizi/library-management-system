<?php

declare(strict_types=1);

namespace Library\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;

class CorsListener extends AbstractListenerAggregate
{
    public function __construct(private readonly array $allowedOrigins)
    {
    }

    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, [$this, 'onPreflight'], 10000);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_FINISH, [$this, 'addHeaders'], 10000);
    }

    public function onPreflight(MvcEvent $e): ?Response
    {
        $request = $e->getRequest();
        if (! $request instanceof Request || ! $request->isOptions()) {
            return null;
        }
        $response = $e->getResponse();
        if (! $response instanceof Response) {
            return null;
        }
        $response->setStatusCode(204);
        $response->setContent('');
        $e->setResult($response);

        return $response;
    }

    public function addHeaders(MvcEvent $e): void
    {
        $request  = $e->getRequest();
        $response = $e->getResponse();
        if (! $request instanceof Request || ! $response instanceof Response) {
            return;
        }
        $originHeader = $request->getHeaders()->get('Origin');
        $origin       = $originHeader !== false ? $originHeader->getFieldValue() : null;
        if ($origin === null) {
            return;
        }

        $allowed = in_array('*', $this->allowedOrigins, true) ? '*' : (in_array($origin, $this->allowedOrigins, true) ? $origin : null);
        if ($allowed === null) {
            return;
        }

        $headers = $response->getHeaders();
        $headers->addHeaderLine('Access-Control-Allow-Origin', $allowed);
        $headers->addHeaderLine('Vary', 'Origin');
        $headers->addHeaderLine('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $headers->addHeaderLine('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept');
        $headers->addHeaderLine('Access-Control-Max-Age', '600');
    }
}
