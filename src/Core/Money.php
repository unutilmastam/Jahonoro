<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Pul hisobi tiyinlarda (int), float xatolarisiz. DECIMAL(14,2) ↔ int.
 */
final class Money
{
    public static function toCents(string|int|float|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_int($value)) {
            return $value * 100;
        }
        if (is_float($value)) {
            return (int) round($value * 100);
        }
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-');
        [$int, $frac] = array_pad(explode('.', $value, 2), 2, '');
        $cents = (int) $int * 100 + (int) str_pad(substr($frac, 0, 2), 2, '0');
        return $negative ? -$cents : $cents;
    }

    public static function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        return $sign . intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
