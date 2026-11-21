<?php

declare(strict_types=1);

namespace Library;

use Laminas\Mvc\MvcEvent;
use Library\Listener\AuthListener;
use Library\Listener\CorsListener;
use Library\Listener\ErrorListener;

class Module
{
    public function getConfig(): array
    {
        $config = include __DIR__ . '/../config/module.config.php';

        return $config;
    }

    public function onBootstrap(MvcEvent $e): void
    {
        $services = $e->getApplication()->getServiceManager();
        $events   = $e->getApplication()->getEventManager();

        date_default_timezone_set((string) ($services->get('config')['library']['timezone'] ?? 'UTC'));

        $services->get(CorsListener::class)->attach($events);
        $services->get(AuthListener::class)->attach($events);
        $services->get(ErrorListener::class)->attach($events);
    }
}
