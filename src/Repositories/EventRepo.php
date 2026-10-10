<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class EventRepo
{
    public const TYPES = ['kelin', 'nikoh', 'sunnat', 'beshik', 'fotiha', 'korporativ', 'boshqa'];
    public const STATUSES = ['planned', 'confirmed', 'completed', 'cancelled', 'archived'];
    public const ACTIVE_STATUSES = ['planned', 'confirmed'];
    public const DEFAULT_DURATION_MIN = 240;

    /** Bazaga yoziladigan ustunlar (EventService tayyorlaydi). */
    public const WRITABLE = [
        'title', 'event_type', 'event_date', 'start_time', 'starts_at', 'nahor_time', 'abet_time',
        'assembly_time', 'departure_time', 'soundcheck_time', 'duration_min', 'venue_name', 'venue_address',
        'district', 'latitude', 'longitude', 'location_verified', 'total_price', 'financial_visibility',
        'client_name', 'client_phone', 'performance_notes', 'equipment_notes', 'dress_code', 'schedule_notes',
        'internal_notes', 'status',
    ];

    private const ROW_COLUMNS = 'id, title, event_type, event_date, start_time, starts_at, duration_min, venue_name, venue_address, district, status, financial_visibility, total_price';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function find(int $id, bool $forUpdate = false): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM events WHERE id = ?' . ($forUpdate ? ' FOR UPDATE' : ''));
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @param array<string, mixed> $data */
    public function insert(array $data, int $createdBy): int
    {
        $data = array_intersect_key($data, array_flip(self::WRITABLE));
        $columns = array_keys($data);
        $columns[] = 'created_by';
        $values = array_values($data);
        $values[] = $createdBy;
        $sql = 'INSERT INTO events (' . implode(', ', $columns) . ') VALUES (' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        $this->pdo->prepare($sql)->execute($values);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        $data = array_intersect_key($data, array_flip(self::WRITABLE));
        if ($data === []) {
            return;
        }
        $sets = implode(', ', array_map(static fn (string $c): string => $c . ' = ?', array_keys($data)));
        $values = array_values($data);
        $values[] = $id;
        $this->pdo->prepare('UPDATE events SET ' . $sets . ' WHERE id = ?')->execute($values);
    }

    public function setStatus(int $id, string $status): void
    {
        $sql = $status === 'cancelled'
            ? 'UPDATE events SET status = ?, cancelled_at = NOW() WHERE id = ?'
            : 'UPDATE events SET status = ?, cancelled_at = NULL WHERE id = ?';
        $this->pdo->prepare($sql)->execute([$status, $id]);
    }

    /**
     * Ro'yxat: filtr va sahifalash.
     *
     * @param array{from?: ?string, to?: ?string, type?: ?string, status?: ?string, q?: ?string, scope?: string} $f
     * @return array{rows: list<array<string, mixed>>, has_more: bool}
     */
    public function search(array $f, int $page, int $perPage, bool $isOwner): array
    {
        $where = [];
        $params = [];

        $status = $f['status'] ?? null;
        if ($status !== null && ($status !== 'archived' || $isOwner)) {
            $where[] = 'status = ?';
            $params[] = $status;
        } else {
            $where[] = "status <> 'archived'";
        }
        if (!empty($f['type'])) {
            $where[] = 'event_type = ?';
            $params[] = $f['type'];
        }
        if (!empty($f['from'])) {
            $where[] = 'event_date >= ?';
            $params[] = $f['from'];
        }
        if (!empty($f['to'])) {
            $where[] = 'event_date <= ?';
            $params[] = $f['to'];
        }
        $scope = $f['scope'] ?? 'all';
        if ($scope === 'upcoming') {
            $where[] = 'event_date >= CURDATE()';
        } elseif ($scope === 'past') {
            $where[] = 'event_date < CURDATE()';
        }
        if (!empty($f['q'])) {
            $like = '%' . addcslashes((string) $f['q'], '%_\\') . '%';
            // Mijoz ismi va telefoni bo'yicha qidiruv faqat owner uchun (a'zoga sizib chiqmasin)
            $fields = ['title', 'venue_name', 'venue_address', 'district'];
            if ($isOwner) {
                $fields[] = 'client_name';
                $fields[] = 'client_phone';
            }
            $where[] = '(' . implode(' OR ', array_map(static fn (string $c): string => $c . ' LIKE ?', $fields)) . ')';
            foreach ($fields as $_) {
                $params[] = $like;
            }
        }

        $order = $scope === 'past' ? 'starts_at DESC, id DESC' : 'starts_at ASC, id ASC';
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT ' . self::ROW_COLUMNS . ' FROM events WHERE ' . implode(' AND ', $where)
            . ' ORDER BY ' . $order . ' LIMIT ' . ($perPage + 1) . ' OFFSET ' . $offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $hasMore = count($rows) > $perPage;
        return ['rows' => array_slice($rows, 0, $perPage), 'has_more' => $hasMore];
    }

    /**
     * Sana oralig'idagi to'ylar (arxivsiz) — kalendar va hafta ro'yxati uchun.
     *
     * @param list<string>|null $statuses
     * @return list<array<string, mixed>>
     */
    public function between(string $fromDate, string $toDate, ?array $statuses = null): array
    {
        $params = [$fromDate, $toDate];
        $statusSql = "status <> 'archived'";
        if ($statuses !== null) {
            $statusSql = 'status IN (' . implode(',', array_fill(0, count($statuses), '?')) . ')';
            array_push($params, ...$statuses);
        }
        $stmt = $this->pdo->prepare(
            'SELECT ' . self::ROW_COLUMNS . ' FROM events
             WHERE event_date BETWEEN ? AND ? AND ' . $statusSql . '
             ORDER BY starts_at ASC, id ASC'
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Shu kuni yoki vaqti kesishadigan faol to'ylar.
     *
     * @return list<array<string, mixed>>
     */
    public function conflicts(string $eventDate, string $startsAt, int $durationMin, ?int $excludeId): array
    {
        $endsAt = date('Y-m-d H:i:s', strtotime($startsAt) + $durationMin * 60);
        $stmt = $this->pdo->prepare(
            "SELECT " . self::ROW_COLUMNS . " FROM events
             WHERE status IN ('planned','confirmed')
               AND id <> ?
               AND (event_date = ?
                    OR (starts_at < ? AND DATE_ADD(starts_at, INTERVAL COALESCE(duration_min, " . self::DEFAULT_DURATION_MIN . ") MINUTE) > ?))
             ORDER BY starts_at ASC
             LIMIT 10"
        );
        $stmt->execute([$excludeId ?? 0, $eventDate, $endsAt, $startsAt]);
        return $stmt->fetchAll();
    }

    /** Hali tugamagan eng yaqin faol to'y. */
    public function next(): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM events
             WHERE status IN ('planned','confirmed')
               AND DATE_ADD(starts_at, INTERVAL COALESCE(duration_min, " . self::DEFAULT_DURATION_MIN . ") MINUTE) > NOW()
             ORDER BY starts_at ASC, id ASC
             LIMIT 1"
        );
        $stmt->execute();
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function countBetween(string $fromDate, string $toDate): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM events WHERE event_date BETWEEN ? AND ? AND status IN ('planned','confirmed','completed')"
        );
        $stmt->execute([$fromDate, $toDate]);
        return (int) $stmt->fetchColumn();
    }

    public function countUpcoming(): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM events WHERE starts_at >= NOW() AND status IN ('planned','confirmed')"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Kelajakdagi faol to'ylar ID'lari (eslatmalarni qayta hisoblash uchun).
     *
     * @return list<int>
     */
    public function upcomingActiveIds(): array
    {
        $stmt = $this->pdo->prepare("SELECT id FROM events WHERE starts_at > NOW() AND status IN ('planned','confirmed')");
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
