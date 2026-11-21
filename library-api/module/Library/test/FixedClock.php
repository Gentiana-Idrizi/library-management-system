<?php

declare(strict_types=1);

namespace LibraryTest;

use DateTimeImmutable;
use Library\Service\ClockInterface;

final class FixedClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function today(): DateTimeImmutable
    {
        return $this->now->setTime(0, 0);
    }

    public function set(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }

    public function advanceDays(int $days): void
    {
        $this->now = $this->now->modify(sprintf('%+d days', $days));
    }
}
