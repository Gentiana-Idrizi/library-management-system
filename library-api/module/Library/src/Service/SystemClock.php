<?php

declare(strict_types=1);

namespace Library\Service;

use DateTimeImmutable;
use DateTimeZone;

final class SystemClock implements ClockInterface
{
    private DateTimeZone $timezone;

    public function __construct(string $timezone = 'UTC')
    {
        $this->timezone = new DateTimeZone($timezone);
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timezone);
    }

    public function today(): DateTimeImmutable
    {
        return $this->now()->setTime(0, 0);
    }
}
