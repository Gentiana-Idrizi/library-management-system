<?php

declare(strict_types=1);

namespace Library\Factory;

use Library\Listener\CorsListener;
use Psr\Container\ContainerInterface;

final class CorsListenerFactory
{
    public function __invoke(ContainerInterface $container): CorsListener
    {
        return new CorsListener(array_values((array) ($container->get('config')['library']['cors']['allowed_origins'] ?? [])));
    }
}
