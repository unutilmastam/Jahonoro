<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ReminderRepo
{
    public const MAX_ATTEMPTS = 5;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Eslatmani yaratadi yoki (kalit bir xil bo'lsa) yangilaydi:
     * kutilayotgan bo'lsa vaqti yangilanadi, bekor qilingan bo'lsa qayta tiklanadi, yuborilgani tegilmaydi.
     */
    public function upsert(int $eventId, string $type, string $dedupeKey, string $scheduledAt): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO event_reminders (event_id, reminder_type, dedupe_key, scheduled_at, status)
             VALUES (?, ?, ?, ?, 'pending')
             ON DUPLICATE KEY UPDATE
               scheduled_at = IF(status IN ('pending','failed','cancelled'), VALUES(scheduled_at), scheduled_at),
               attempts = IF(status = 'cancelled', 0, attempts),
               last_error = IF(status = 'cancelled', NULL, last_error),
               status = IF(status = 'cancelled', 'pending', status)"
        );
        $stmt->execute([$eventId, $type, $dedupeKey, $scheduledAt]);
    }

    public function insertManual(int $eventId, string $dedupeKey): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO event_reminders (event_id, reminder_type, dedupe_key, scheduled_at, status)
             VALUES (?, 'manual', ?, NOW(), 'pending')"
        );
        $stmt->execute([$eventId, $dedupeKey]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Shu to'yning boshqa kalitli kutilayotgan eslatmalarini bekor qiladi. */
    public function cancelOthers(int $eventId, string $type, string $keepKey): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE event_reminders SET status = 'cancelled', claim_token = NULL
             WHERE event_id = ? AND reminder_type = ? AND dedupe_key <> ? AND status IN ('pending','failed')"
        );
        $stmt->execute([$eventId, $type, $keepKey]);
    }

    public function cancelForEvent(int $eventId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE event_reminders SET status = 'cancelled', claim_token = NULL
             WHERE event_id = ? AND status IN ('pending','failed')"
        );
        $stmt->execute([$eventId]);
    }

    /** 10 daqiqadan ortiq "sending" holatida qolganlar → failed. */
    public function releaseStale(): int
    {
        $stmt = $this->pdo->prepare(
            "UPDATE event_reminders SET status = 'failed', claim_token = NULL, last_error = 'Yuborish vaqti tugadi'
             WHERE status = 'sending' AND locked_at < NOW() - INTERVAL 10 MINUTE"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Vaqti kelgan eslatmalarni claim_token bilan band qiladi va qaytaradi.
     *
     * @return list<array<string, mixed>>
     */
    public function claimDue(string $claimToken, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "UPDATE event_reminders
             SET status = 'sending', claim_token = ?, locked_at = NOW(), attempts = attempts + 1
             WHERE status IN ('pending','failed') AND scheduled_at <= NOW() AND attempts < " . self::MAX_ATTEMPTS . "
             ORDER BY scheduled_at ASC, id ASC
             LIMIT " . max(1, min(50, $limit))
        );
        $stmt->execute([$claimToken]);
        return $this->claimed($claimToken);
    }

    /**
     * Bitta eslatmani band qiladi (qo'lda yuborish).
     *
     * @return list<array<string, mixed>>
     */
    public function claimOne(int $id, string $claimToken): array
    {
        $stmt = $this->pdo->prepare(
            "UPDATE event_reminders
             SET status = 'sending', claim_token = ?, locked_at = NOW(), attempts = attempts + 1
             WHERE id = ? AND status IN ('pending','failed') AND attempts < " . self::MAX_ATTEMPTS
        );
        $stmt->execute([$claimToken, $id]);
        return $this->claimed($claimToken);
    }

    /** @return list<array<string, mixed>> */
    private function claimed(string $claimToken): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, event_id, reminder_type, dedupe_key, scheduled_at, attempts
             FROM event_reminders WHERE claim_token = ? AND status = 'sending' ORDER BY scheduled_at ASC, id ASC"
        );
        $stmt->execute([$claimToken]);
        return $stmt->fetchAll();
    }

    public function markSent(int $id, string $claimToken, ?int $messageId): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE event_reminders SET status = 'sent', sent_at = NOW(), tg_message_id = ?, last_error = NULL, claim_token = NULL
             WHERE id = ? AND claim_token = ?"
        );
        $stmt->execute([$messageId, $id, $claimToken]);
    }

    public function markFailed(int $id, string $claimToken, string $error, bool $final): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE event_reminders
             SET status = 'failed', last_error = ?, claim_token = NULL,
                 attempts = IF(?, " . self::MAX_ATTEMPTS . ", attempts)
             WHERE id = ? AND claim_token = ?"
        );
        $stmt->execute([mb_substr($error, 0, 255), $final ? 1 : 0, $id, $claimToken]);
    }

    public function markCancelled(int $id, string $claimToken, string $reason): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE event_reminders SET status = 'cancelled', last_error = ?, claim_token = NULL
             WHERE id = ? AND claim_token = ?"
        );
        $stmt->execute([mb_substr($reason, 0, 255), $id, $claimToken]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM event_reminders WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return array<string, int> holat → soni */
    public function countsByStatus(): array
    {
        $stmt = $this->pdo->prepare('SELECT status, COUNT(*) AS n FROM event_reminders GROUP BY status');
        $stmt->execute();
        $out = ['pending' => 0, 'sending' => 0, 'sent' => 0, 'failed' => 0, 'cancelled' => 0];
        foreach ($stmt->fetchAll() as $row) {
            $out[(string) $row['status']] = (int) $row['n'];
        }
        return $out;
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.id, r.event_id, r.reminder_type, r.scheduled_at, r.status, r.attempts, r.sent_at, r.last_error,
                    e.title, e.event_date, e.start_time
             FROM event_reminders r
             JOIN events e ON e.id = r.event_id
             WHERE r.status <> 'cancelled'
             ORDER BY (r.status IN ('pending','failed','sending')) DESC,
                      CASE WHEN r.status IN ('pending','failed','sending') THEN r.scheduled_at END ASC,
                      r.sent_at DESC, r.id DESC
             LIMIT " . max(1, min(50, $limit))
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
