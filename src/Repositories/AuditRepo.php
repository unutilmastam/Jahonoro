<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Audit jurnali. Metadata oq ro'yxat bilan — narx, summa, telefon, izoh hech qachon yozilmaydi.
 */
final class AuditRepo
{
    private const SAFE_KEYS = ['title', 'event_type', 'event_date', 'start_time', 'status', 'from_status', 'to_status', 'display_name', 'changed', 'reminder_type'];

    /** Bu kalitlar ichida "changed" ro'yxatida ham moliya maydonlari nomi chiqmaydi. */
    private const HIDDEN_FIELDS = ['total_price', 'client_name', 'client_phone', 'internal_notes', 'financial_visibility'];

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @param array<string, mixed> $meta */
    public function log(?int $actorUserId, string $action, string $entityType, ?int $entityId, array $meta = []): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, safe_metadata) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $actorUserId,
            $action,
            $entityType,
            $entityId,
            json_encode(self::filter($meta), JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @param array<string, mixed> $meta
     * @return array<string, mixed>
     */
    public static function filter(array $meta): array
    {
        $safe = [];
        foreach (self::SAFE_KEYS as $key) {
            if (!array_key_exists($key, $meta)) {
                continue;
            }
            $value = $meta[$key];
            if ($key === 'changed' && is_array($value)) {
                $value = array_values(array_diff(array_map('strval', $value), self::HIDDEN_FIELDS));
            } elseif (!is_scalar($value) && $value !== null) {
                continue;
            }
            $safe[$key] = $value;
        }
        return $safe;
    }

    /**
     * So'nggi to'y o'zgarishlari (a'zolarga ham ko'rinadi — faqat event.* amallari).
     *
     * @return list<array<string, mixed>>
     */
    public function recentEventChanges(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.id, a.action, a.entity_id, a.safe_metadata, a.created_at, u.display_name AS actor_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.actor_user_id
             WHERE a.entity_type = 'event' AND a.action IN ('event.create','event.update','event.cancel','event.restore')
             ORDER BY a.id DESC
             LIMIT " . max(1, min(20, $limit))
        );
        $stmt->execute();
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $meta = json_decode((string) $row['safe_metadata'], true);
            $rows[] = [
                'id' => (int) $row['id'],
                'action' => (string) $row['action'],
                'event_id' => $row['entity_id'] !== null ? (int) $row['entity_id'] : null,
                'title' => is_array($meta) ? (string) ($meta['title'] ?? '') : '',
                'event_date' => is_array($meta) ? ($meta['event_date'] ?? null) : null,
                'actor_name' => (string) ($row['actor_name'] ?? ''),
                'created_at' => (string) $row['created_at'],
            ];
        }
        return $rows;
    }
}
