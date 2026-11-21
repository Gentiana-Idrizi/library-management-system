<?php

declare(strict_types=1);

namespace Library\Factory;

use Library\Service\SystemClock;
use Psr\Container\ContainerInterface;

final class SystemClockFactory
{
    public function __invoke(ContainerInterface $container): SystemClock
    {
        return new SystemClock((string) ($container->get('config')['library']['timezone'] ?? 'UTC'));
    }
}
