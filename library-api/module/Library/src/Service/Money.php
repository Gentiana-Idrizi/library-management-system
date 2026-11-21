<?php

declare(strict_types=1);

namespace Library\Service;

final class Money
{
    public static function toCents(string $amount): int
    {
        if (! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', trim($amount), $m)) {
            throw new \InvalidArgumentException(sprintf('Invalid money amount "%s".', $amount));
        }
        $fraction = str_pad($m[2] ?? '', 2, '0');
        return (int) $m[1] * 100 + (int) $fraction;
    }

    public static function fromCents(int $cents): string
    {
        $sign  = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        return sprintf('%s%d.%02d', $sign, intdiv($cents, 100), $cents % 100);
    }

    public static function isValid(string $amount): bool
    {
        return (bool) preg_match('/^\d{1,8}(\.\d{1,2})?$/', trim($amount));
    }
}
