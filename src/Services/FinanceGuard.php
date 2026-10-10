<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Money;

/**
 * Javobga chiqadigan har bir to'y shu yerdan o'tadi. Moliya va mijoz ma'lumoti
 * faqat ruxsat bo'lsa qo'shiladi; a'zoga client_*, internal_notes, to'lov izohlari hech qachon chiqmaydi.
 */
final class FinanceGuard
{
    /** Hamma ko'radigan maydonlar (tafsilot). */
    private const PUBLIC_FIELDS = [
        'id', 'title', 'event_type', 'event_date', 'start_time', 'starts_at', 'nahor_time', 'abet_time', 'evening_time',
        'assembly_time', 'departure_time', 'soundcheck_time', 'duration_min', 'venue_name', 'venue_address',
        'district', 'home_address', 'performance_notes', 'equipment_notes', 'dress_code', 'schedule_notes', 'status', 'updated_at',
    ];

    /** Ro'yxat qatori uchun. */
    private const ROW_FIELDS = ['id', 'title', 'event_type', 'event_date', 'start_time', 'starts_at', 'nahor_time', 'abet_time', 'evening_time', 'assembly_time', 'venue_name', 'venue_address', 'district', 'home_address', 'status'];

    private const TIME_FIELDS = ['start_time', 'nahor_time', 'abet_time', 'evening_time', 'assembly_time', 'departure_time', 'soundcheck_time'];

    public function __construct(private readonly bool $globalVisibleToMembers)
    {
    }

    public static function effectiveVisibility(string $eventVisibility, bool $globalVisible): string
    {
        return match ($eventVisibility) {
            'members' => 'members',
            'private' => 'private',
            default => $globalVisible ? 'members' : 'private',
        };
    }

    /**
     * To'y tafsiloti.
     *
     * @param array<string, mixed>                  $event  events qatori
     * @param array{role: string}                   $user
     * @param int|null                              $paidCents to'langan summa (tiyin)
     * @return array<string, mixed>
     */
    public function present(array $event, array $user, ?int $paidCents = null): array
    {
        $out = self::pick($event, self::PUBLIC_FIELDS);
        $isOwner = $user['role'] === 'owner';
        $verified = (int) ($event['location_verified'] ?? 0) === 1;

        $out['location_verified'] = $verified;
        // Tasdiqlanmagan koordinata a'zoga chiqmaydi — xarita manzil matni bilan qidiradi
        $showCoords = ($verified || $isOwner) && $event['latitude'] !== null && $event['longitude'] !== null;
        $out['latitude'] = $showCoords ? (float) $event['latitude'] : null;
        $out['longitude'] = $showCoords ? (float) $event['longitude'] : null;
        // Uy nuqtasini ham faqat owner xaritada qo'yadi
        $hasHome = ($event['home_latitude'] ?? null) !== null && ($event['home_longitude'] ?? null) !== null;
        $out['home_latitude'] = $hasHome ? (float) $event['home_latitude'] : null;
        $out['home_longitude'] = $hasHome ? (float) $event['home_longitude'] : null;

        $visibility = (string) ($event['financial_visibility'] ?? 'inherit');
        $effective = self::effectiveVisibility($visibility, $this->globalVisibleToMembers);

        if ($isOwner || $effective === 'members') {
            $finance = self::finance($event, $paidCents ?? 0);
            if ($finance !== null || $isOwner) {
                $out['finance'] = $finance ?? ['total_price' => null, 'paid' => Money::fromCents($paidCents ?? 0), 'balance' => null];
            }
        }

        if ($isOwner) {
            $out['finance']['visibility'] = $visibility;
            $out['finance']['effective_visibility'] = $effective;
            $out['financial_visibility'] = $visibility;
            $out['client_name'] = (string) $event['client_name'];
            $out['client_phone'] = (string) $event['client_phone'];
            $out['internal_notes'] = (string) ($event['internal_notes'] ?? '');
        }

        return $out;
    }

    /**
     * Ro'yxat qatori — moliyasiz, mijozsiz.
     *
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    public function presentRow(array $event): array
    {
        return self::pick($event, self::ROW_FIELDS);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    public function presentRows(array $rows): array
    {
        return array_map(fn (array $r): array => $this->presentRow($r), $rows);
    }

    /** @return array{total_price: string, paid: string, balance: string}|null */
    private static function finance(array $event, int $paidCents): ?array
    {
        if ($event['total_price'] === null) {
            return null;
        }
        $total = Money::toCents((string) $event['total_price']);
        return [
            'total_price' => Money::fromCents($total),
            'paid' => Money::fromCents($paidCents),
            'balance' => Money::fromCents($total - $paidCents),
        ];
    }

    /**
     * @param array<string, mixed> $event
     * @param list<string>         $fields
     * @return array<string, mixed>
     */
    private static function pick(array $event, array $fields): array
    {
        $out = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $event)) {
                continue;
            }
            $value = $event[$field];
            if ($field === 'id' || $field === 'duration_min') {
                $value = $value !== null ? (int) $value : null;
            } elseif (in_array($field, self::TIME_FIELDS, true) && is_string($value)) {
                $value = substr($value, 0, 5);
            } elseif (in_array($field, ['performance_notes', 'equipment_notes', 'schedule_notes'], true)) {
                $value = (string) ($value ?? '');
            }
            $out[$field] = $value;
        }
        return $out;
    }
}
