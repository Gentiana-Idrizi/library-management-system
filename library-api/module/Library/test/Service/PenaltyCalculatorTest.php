<?php

declare(strict_types=1);

namespace LibraryTest\Service;

use DateTimeImmutable;
use Library\Service\PenaltyCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PenaltyCalculatorTest extends TestCase
{
    public static function returnDates(): iterable
    {
        yield 'returned early'                        => ['2026-03-15', '2026-03-10 09:00', 0];
        yield 'returned on the due date (boundary)'   => ['2026-03-15', '2026-03-15 23:59', 0];
        yield 'one day late (penalty starts)'         => ['2026-03-15', '2026-03-16 00:01', 1];
        yield 'three days late'                       => ['2026-03-15', '2026-03-18 12:00', 3];
        yield 'across a month end'                    => ['2026-01-30', '2026-02-02 08:00', 3];
        yield 'across the DST change'                 => ['2026-03-28', '2026-03-31 10:00', 3];
    }

    #[DataProvider('returnDates')]
    public function testDaysLate(string $due, string $returned, int $expected): void
    {
        $calculator = new PenaltyCalculator();

        self::assertSame($expected, $calculator->daysLate(new DateTimeImmutable($due), new DateTimeImmutable($returned)));
    }

    public function testAmountIsDaysTimesRate(): void
    {
        $calculator = new PenaltyCalculator();

        self::assertSame(300, $calculator->amountCents(3, 100), '3 days x €1.00 = €3.00');
        self::assertSame(375, $calculator->amountCents(3, 125), '3 days x €1.25 = €3.75');
        self::assertSame(0, $calculator->amountCents(0, 100));
    }
}
