<?php

declare(strict_types=1);

namespace Library\Factory;

use Library\Listener\ErrorListener;
use Psr\Container\ContainerInterface;

final class ErrorListenerFactory
{
    public function __invoke(ContainerInterface $container): ErrorListener
    {
        return new ErrorListener((bool) ($container->get('config')['view_manager']['display_exceptions'] ?? false));
    }
}
