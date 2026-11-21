<?php

declare(strict_types=1);

namespace Library\Factory;

use Library\Service\ClockInterface;
use Library\Service\JwtService;
use Psr\Container\ContainerInterface;

final class JwtServiceFactory
{
    public function __invoke(ContainerInterface $container): JwtService
    {
        $jwt = $container->get('config')['library']['jwt'] ?? [];

        return new JwtService(
            (string) ($jwt['secret'] ?? ''),
            (int) ($jwt['ttl'] ?? 28800),
            (string) ($jwt['issuer'] ?? 'library-api'),
            $container->get(ClockInterface::class)
        );
    }
}
