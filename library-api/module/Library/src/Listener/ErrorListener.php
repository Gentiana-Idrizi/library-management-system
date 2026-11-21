<?php

declare(strict_types=1);

namespace Library\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Library\Controller\JsonResponder;
use Library\Exception\ApiException;
use Throwable;

class ErrorListener extends AbstractListenerAggregate
{
    public function __construct(private readonly bool $displayExceptions)
    {
    }

    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, [$this, 'onError'], -5000);
        $this->listeners[] = $events->attach(MvcEvent::EVENT_RENDER_ERROR, [$this, 'onError'], -5000);
    }

    public function onError(MvcEvent $e): ?Response
    {
        $request = $e->getRequest();
        if (! $request instanceof Request) {
            return null;
        }

        $exception = $e->getParam('exception');
        $response  = $e->getResponse();

        if ($exception instanceof ApiException) {
            $result = JsonResponder::error($response, $exception);
        } elseif ($exception instanceof Throwable) {
            error_log((string) $exception);
            $result = JsonResponder::errorBody(
                $response,
                500,
                'INTERNAL_ERROR',
                $this->displayExceptions ? $exception->getMessage() : 'Something went wrong.',
                $this->displayExceptions ? ['exception' => $exception::class, 'at' => $exception->getFile() . ':' . $exception->getLine()] : []
            );
        } elseif (in_array($e->getError(), [Application::ERROR_ROUTER_NO_MATCH, Application::ERROR_CONTROLLER_NOT_FOUND, Application::ERROR_CONTROLLER_INVALID], true)) {
            $result = JsonResponder::errorBody($response, 404, 'ROUTE_NOT_FOUND', 'Nothing found at this URL.');
        } else {
            $result = JsonResponder::errorBody($response, 500, 'INTERNAL_ERROR', 'Something went wrong.');
        }

        $e->setResult($result);
        $e->setResponse($result);

        return $result;
    }
}
