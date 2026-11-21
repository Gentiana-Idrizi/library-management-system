<?php

declare(strict_types=1);

namespace Library\Service;

use DateTimeImmutable;
use DateTimeZone;

final class PenaltyCalculator
{
    public function daysLate(DateTimeImmutable $dueDate, DateTimeImmutable $returnDate): int
    {
        $utc    = new DateTimeZone('UTC');
        $due    = new DateTimeImmutable($dueDate->format('Y-m-d'), $utc);
        $actual = new DateTimeImmutable($returnDate->format('Y-m-d'), $utc);

        if ($actual <= $due) {
            return 0;
        }

        return (int) $due->diff($actual)->days;
    }

    public function amountCents(int $daysLate, int $ratePerDayCents): int
    {
        return max(0, $daysLate) * max(0, $ratePerDayCents);
    }
}
