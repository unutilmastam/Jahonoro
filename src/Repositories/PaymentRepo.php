<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Money;
use PDO;

final class PaymentRepo
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function listForEvent(int $eventId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, event_id, amount, paid_on, note, created_at FROM payments
             WHERE event_id = ? ORDER BY paid_on ASC, id ASC'
        );
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    public function find(int $eventId, int $paymentId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, event_id, amount, paid_on, note FROM payments WHERE id = ? AND event_id = ?');
        $stmt->execute([$paymentId, $eventId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** To'langan summa tiyinlarda. */
    public function paidCents(int $eventId): int
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE event_id = ?');
        $stmt->execute([$eventId]);
        return Money::toCents((string) $stmt->fetchColumn());
    }

    /**
     * @param list<int> $eventIds
     * @return array<int, int> event_id → tiyin
     */
    public function paidCentsMany(array $eventIds): array
    {
        if ($eventIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($eventIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT event_id, SUM(amount) AS paid FROM payments WHERE event_id IN ($placeholders) GROUP BY event_id"
        );
        $stmt->execute(array_values($eventIds));
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int) $row['event_id']] = Money::toCents((string) $row['paid']);
        }
        return $out;
    }

    public function insert(int $eventId, string $amount, string $paidOn, string $note, int $createdBy): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO payments (event_id, amount, paid_on, note, created_by) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$eventId, $amount, $paidOn, $note, $createdBy]);
        return (int) $this->pdo->lastInsertId();
    }

    public function delete(int $eventId, int $paymentId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM payments WHERE id = ? AND event_id = ?');
        $stmt->execute([$paymentId, $eventId]);
        return $stmt->rowCount() === 1;
    }

    /**
     * Faol to'ylar bo'yicha jami qoldiq (tiyin) va qarzdor to'ylar soni.
     *
     * @return array{balance_cents: int, events: int}
     */
    public function outstanding(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT e.total_price, COALESCE(p.paid, 0) AS paid
             FROM events e
             LEFT JOIN (SELECT event_id, SUM(amount) AS paid FROM payments GROUP BY event_id) p ON p.event_id = e.id
             WHERE e.status IN ('planned','confirmed','completed') AND e.total_price IS NOT NULL"
        );
        $stmt->execute();
        $total = 0;
        $count = 0;
        foreach ($stmt->fetchAll() as $row) {
            $balance = Money::toCents((string) $row['total_price']) - Money::toCents((string) $row['paid']);
            if ($balance > 0) {
                $total += $balance;
                $count++;
            }
        }
        return ['balance_cents' => $total, 'events' => $count];
    }
}
