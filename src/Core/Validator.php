<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Kiruvchi ma'lumotni tekshiradi va normallashtiradi. Xatolar maydon bo'yicha yig'iladi,
 * oxirida throwIfErrors() 422 qaytaradi.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data)
    {
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->data);
    }

    public function fail(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function throwIfErrors(): void
    {
        if ($this->errors !== []) {
            throw new HttpError(422, 'validation', (string) reset($this->errors), $this->errors);
        }
    }

    private function raw(string $field): mixed
    {
        $value = $this->data[$field] ?? null;
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
        }
        return $value;
    }

    public function string(string $field, int $max, bool $required = false, string $label = 'Maydon'): ?string
    {
        $value = $this->raw($field);
        if ($value === null) {
            if ($required) {
                $this->fail($field, $label . ' kiritilishi shart.');
            }
            return null;
        }
        if (!is_string($value) && !is_int($value)) {
            $this->fail($field, $label . ' notoʻgʻri.');
            return null;
        }
        $value = self::cleanText((string) $value);
        if (mb_strlen($value) > $max) {
            $this->fail($field, $label . ' ' . $max . ' belgidan oshmasin.');
            return null;
        }
        return $value === '' ? null : $value;
    }

    /** @param list<string> $allowed */
    public function enum(string $field, array $allowed, ?string $default = null, string $label = 'Qiymat'): ?string
    {
        $value = $this->raw($field);
        if ($value === null) {
            return $default;
        }
        if (!is_string($value) || !in_array($value, $allowed, true)) {
            $this->fail($field, $label . ' notoʻgʻri tanlangan.');
            return $default;
        }
        return $value;
    }

    public function date(string $field, bool $required = false, string $label = 'Sana'): ?string
    {
        $value = $this->raw($field);
        if ($value === null) {
            if ($required) {
                $this->fail($field, $label . ' kiritilishi shart.');
            }
            return null;
        }
        if (!is_string($value) || !self::isDate($value)) {
            $this->fail($field, $label . ' notoʻgʻri. Masalan: 2026-10-18.');
            return null;
        }
        return $value;
    }

    public function time(string $field, bool $required = false, string $label = 'Vaqt'): ?string
    {
        $value = $this->raw($field);
        if ($value === null) {
            if ($required) {
                $this->fail($field, $label . ' kiritilishi shart.');
            }
            return null;
        }
        if (!is_string($value) || !preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:00)?$/', $value)) {
            $this->fail($field, $label . ' notoʻgʻri. Masalan: 18:00.');
            return null;
        }
        return substr($value, 0, 5);
    }

    public function int(string $field, int $min, int $max, bool $required = false, string $label = 'Son'): ?int
    {
        $value = $this->raw($field);
        if ($value === null) {
            if ($required) {
                $this->fail($field, $label . ' kiritilishi shart.');
            }
            return null;
        }
        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            $value = (int) $value;
        }
        if (!is_int($value) || $value < $min || $value > $max) {
            $this->fail($field, $label . ' ' . $min . ' dan ' . $max . ' gacha boʻlsin.');
            return null;
        }
        return $value;
    }

    /**
     * So'm summasi: "12 500 000", 12500000, "12500000.50" → "12500000.00" (satr, DECIMAL uchun).
     */
    public function money(string $field, bool $required = false, bool $positive = false, string $label = 'Summa'): ?string
    {
        $value = $this->raw($field);
        if ($value === null) {
            if ($required) {
                $this->fail($field, $label . ' kiritilishi shart.');
            }
            return null;
        }
        $normalized = self::normalizeMoney($value);
        if ($normalized === null) {
            if (is_numeric($value) && (float) $value < 0) {
                $this->fail($field, $label . ' manfiy boʻlishi mumkin emas.');
            } else {
                $this->fail($field, $label . ' notoʻgʻri. Masalan: 12 500 000.');
            }
            return null;
        }
        if ($positive && Money::toCents($normalized) <= 0) {
            $this->fail($field, $label . ' 0 dan katta boʻlsin.');
            return null;
        }
        return $normalized;
    }

    public function coordinate(string $field, float $limit, string $label): ?string
    {
        $value = $this->raw($field);
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }
        if (!is_numeric($value) || abs((float) $value) > $limit) {
            $this->fail($field, $label . ' notoʻgʻri.');
            return null;
        }
        return number_format((float) $value, 6, '.', '');
    }

    public function bool(string $field, bool $default = false): bool
    {
        $value = $this->data[$field] ?? null;
        if ($value === null) {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 1 || $value === '1' || $value === 'true') {
            return true;
        }
        if ($value === 0 || $value === '0' || $value === 'false') {
            return false;
        }
        $this->fail($field, 'Qiymat notoʻgʻri.');
        return $default;
    }

    public static function isDate(string $value): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return false;
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) && (int) $m[1] >= 2000 && (int) $m[1] <= 2100;
    }

    public static function normalizeMoney(mixed $value): ?string
    {
        if (is_int($value)) {
            $value = (string) $value;
        } elseif (is_float($value)) {
            $value = number_format($value, 2, '.', '');
        }
        if (!is_string($value)) {
            return null;
        }
        $value = preg_replace('/[\s\x{00A0}\x{202F}]/u', '', $value) ?? '';
        $value = str_replace(',', '.', $value);
        if (!preg_match('/^(\d{1,12})(?:\.(\d{1,2}))?$/', $value, $m)) {
            return null;
        }
        $int = ltrim($m[1], '0');
        $frac = str_pad($m[2] ?? '', 2, '0');
        return ($int === '' ? '0' : $int) . '.' . $frac;
    }

    /**
     * Boshqaruv belgilarini olib tashlaydi (yangi qator va tab qoladi).
     */
    public static function cleanText(string $value): string
    {
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        return trim(str_replace("\r\n", "\n", $value));
    }
}

