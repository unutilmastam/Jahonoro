<?php

declare(strict_types=1);

namespace App\Telegram;

/**
 * Guruhga boradigan xabarlar. Faqat oq ro'yxatdagi maydonlar — narx, avans, mijoz,
 * telefon, ichki izoh bu yerga umuman kirmaydi.
 */
final class MessageBuilder
{
    public const TYPE_LABELS = [
        'kelin' => 'Kelin to‘yi',
        'nikoh' => 'Nikoh to‘yi',
        'sunnat' => 'Sunnat to‘yi',
        'beshik' => 'Beshik to‘yi',
        'fotiha' => 'Fotiha marosimi',
        'korporativ' => 'Korporativ tadbir',
        'boshqa' => 'Boshqa tadbir',
    ];

    private const MONTHS = [1 => 'yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr'];
    private const WEEKDAYS = [1 => 'dushanba', 'seshanba', 'chorshanba', 'payshanba', 'juma', 'shanba', 'yakshanba'];

    public function __construct(private readonly string $miniAppLink)
    {
    }

    /**
     * To'y eslatmasi.
     *
     * @param array<string, mixed> $event events qatori
     * @return array{text: string, reply_markup: array<string, mixed>|null}
     */
    public function reminder(array $event, ?string $today = null): array
    {
        $today ??= date('Y-m-d');
        $date = (string) $event['event_date'];
        $days = (int) round((strtotime($date) - strtotime($today)) / 86400);
        $header = match ($days) {
            0 => '🎵 TO‘Y BUGUN!',
            1 => '🎵 TO‘Y ERTAGA!',
            default => '🎵 TO‘Y ESLATMASI',
        };

        $venue = implode(', ', array_filter([
            trim((string) ($event['venue_name'] ?? '')),
            trim((string) ($event['venue_address'] ?? '')),
            trim((string) ($event['district'] ?? '')),
        ], static fn (string $s): bool => $s !== ''));
        $home = trim((string) ($event['home_address'] ?? ''));

        $lines = [
            ['📅', 'Sana', self::dateUz($date, (int) substr($today, 0, 4))],
            ['📍', 'Manzilda bo‘lish', self::time($event['assembly_time'] ?? null)],
            ['🍽', 'Nahor', self::time($event['nahor_time'] ?? null)],
            ['🍽', 'Abet', self::time($event['abet_time'] ?? null)],
            ['🌙', 'Kech', self::time($event['evening_time'] ?? null)],
            ['💍', 'Turi', self::TYPE_LABELS[(string) ($event['event_type'] ?? '')] ?? ''],
            ['🏛', 'To‘yxona', $venue],
            ['🏠', 'To‘y egasining uyi', $home],
            ['👔', 'Dress code', trim((string) ($event['dress_code'] ?? ''))],
        ];

        $text = $header . "\n";
        foreach ($lines as [$emoji, $label, $value]) {
            if ($value === '') {
                continue;
            }
            $text .= "\n" . $emoji . ' ' . $label . ': ' . self::e($value);
        }
        $text .= "\n\nIltimos, belgilangan vaqtda tayyor bo‘ling!";

        $buttons = [];
        $venueUrl = self::mapUrl($event);
        $homeUrl = self::homeMapUrl($event);
        $mapRow = [];
        if ($venueUrl !== null) {
            $mapRow[] = ['text' => $homeUrl !== null ? '🗺 To‘yxona' : '🗺 Xaritada ochish', 'url' => $venueUrl];
        }
        if ($homeUrl !== null) {
            $mapRow[] = ['text' => '🏠 Uy', 'url' => $homeUrl];
        }
        if ($mapRow !== []) {
            $buttons[] = $mapRow;
        }
        $appUrl = $this->appUrl('e' . (int) $event['id']);
        if ($appUrl !== null) {
            $buttons[] = [['text' => '📅 TO‘YLAR JADVALI', 'url' => $appUrl]];
        }

        return ['text' => $text, 'reply_markup' => $buttons !== [] ? ['inline_keyboard' => $buttons] : null];
    }

    /**
     * Guruhga pin qilinadigan "jadvalni ochish" xabari.
     *
     * @return array{text: string, reply_markup: array<string, mixed>}
     */
    public function groupButton(): array
    {
        $url = $this->appUrl(null);
        if ($url === null) {
            throw new \InvalidArgumentException('MINI_APP_LINK sozlanmagan');
        }
        return [
            'text' => "📅 <b>JAHONORO</b> — to‘ylar jadvali\n\nJadvalni ochish uchun pastdagi tugmani bosing.",
            'reply_markup' => ['inline_keyboard' => [[['text' => '📅 TO‘YLAR JADVALI', 'url' => $url]]]],
        ];
    }

    /** 18-oktabr, shanba; yil joriydan farq qilsa: 3-yanvar 2027, shanba */
    public static function dateUz(string $date, ?int $currentYear = null): string
    {
        $ts = strtotime($date);
        if ($ts === false) {
            return '';
        }
        $currentYear ??= (int) date('Y');
        $year = (int) date('Y', $ts);
        return (int) date('j', $ts) . '-' . self::MONTHS[(int) date('n', $ts)]
            . ($year !== $currentYear ? ' ' . $year : '')
            . ', ' . self::WEEKDAYS[(int) date('N', $ts)];
    }

    /**
     * Xarita havolasi: tasdiqlangan koordinata bo'lsa nuqta, aks holda manzil matni bo'yicha qidiruv.
     *
     * @param array<string, mixed> $event
     */
    public static function mapUrl(array $event): ?string
    {
        if ((int) ($event['location_verified'] ?? 0) === 1 && $event['latitude'] !== null && $event['longitude'] !== null) {
            $query = number_format((float) $event['latitude'], 6, '.', '') . ',' . number_format((float) $event['longitude'], 6, '.', '');
        } else {
            $query = implode(', ', array_filter([
                trim((string) ($event['venue_name'] ?? '')),
                trim((string) ($event['venue_address'] ?? '')),
                trim((string) ($event['district'] ?? '')),
            ], static fn (string $s): bool => $s !== ''));
            if ($query === '') {
                return null;
            }
        }
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query);
    }

    /**
     * To'y egasining uyi: xaritadagi nuqta yoki manzil matni.
     *
     * @param array<string, mixed> $event
     */
    public static function homeMapUrl(array $event): ?string
    {
        if (($event['home_latitude'] ?? null) !== null && ($event['home_longitude'] ?? null) !== null) {
            $query = number_format((float) $event['home_latitude'], 6, '.', '') . ',' . number_format((float) $event['home_longitude'], 6, '.', '');
        } else {
            $query = trim((string) ($event['home_address'] ?? ''));
            if ($query === '') {
                return null;
            }
        }
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query);
    }

    private function appUrl(?string $startParam): ?string
    {
        $link = trim($this->miniAppLink);
        if (!preg_match('#^https://t\.me/[A-Za-z0-9_]+/[A-Za-z0-9_]+$#', $link)) {
            return null;
        }
        return $startParam !== null ? $link . '?startapp=' . rawurlencode($startParam) : $link;
    }

    private static function time(mixed $value): string
    {
        return is_string($value) && $value !== '' ? substr($value, 0, 5) : '';
    }

    private static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
