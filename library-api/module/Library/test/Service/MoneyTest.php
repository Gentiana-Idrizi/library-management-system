<?php

declare(strict_types=1);

namespace LibraryTest\Service;

use Library\Service\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testConvertsBetweenStringsAndCents(): void
    {
        self::assertSame(200, Money::toCents('2.00'));
        self::assertSame(250, Money::toCents('2.5'));
        self::assertSame(1, Money::toCents('0.01'));
        self::assertSame(300, Money::toCents('3'));
        self::assertSame('2.00', Money::fromCents(200));
        self::assertSame('0.05', Money::fromCents(5));
        self::assertSame('12.34', Money::fromCents(1234));
    }

    public function testValidation(): void
    {
        self::assertTrue(Money::isValid('2.00'));
        self::assertTrue(Money::isValid('0'));
        self::assertFalse(Money::isValid('-1'));
        self::assertFalse(Money::isValid('1.234'));
        self::assertFalse(Money::isValid('abc'));
    }
}
