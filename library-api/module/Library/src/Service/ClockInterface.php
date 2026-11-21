<?php

declare(strict_types=1);

namespace Library\Service;

use DateTimeImmutable;

interface ClockInterface
{
    public function now(): DateTimeImmutable;

    public function today(): DateTimeImmutable;
}
