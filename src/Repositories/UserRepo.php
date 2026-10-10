<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepo
{
    private const COLUMNS = 'id, telegram_user_id, display_name, username, role, status, member_checked_at, member_ok, last_seen_at, created_at';

    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findByTelegramId(int $telegramUserId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE telegram_user_id = ?');
        $stmt->execute([$telegramUserId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function create(int $telegramUserId, string $displayName, ?string $username, string $role, string $status): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (telegram_user_id, display_name, username, role, status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$telegramUserId, $displayName, $username, $role, $status]);
        return (array) $this->findById((int) $this->pdo->lastInsertId());
    }

    /**
     * Owner config'dagi ID bo'yicha: bo'lmasa yaratiladi, bo'lsa owner/active qilinadi.
     */
    public function upsertOwner(int $telegramUserId, string $displayName, ?string $username): array
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (telegram_user_id, display_name, username, role, status, member_ok)
             VALUES (?, ?, ?, 'owner', 'active', 1)
             ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), username = VALUES(username),
                                     role = 'owner', status = 'active'"
        );
        $stmt->execute([$telegramUserId, $displayName, $username]);
        return (array) $this->findByTelegramId($telegramUserId);
    }

    public function updateProfile(int $id, string $displayName, ?string $username): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET display_name = ?, username = ? WHERE id = ?');
        $stmt->execute([$displayName, $username, $id]);
    }

    public function setDisplayName(int $id, string $displayName): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET display_name = ? WHERE id = ?');
        $stmt->execute([$displayName, $id]);
    }

    public function setStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    public function setMembership(int $id, bool $ok): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET member_ok = ?, member_checked_at = NOW() WHERE id = ?');
        $stmt->execute([$ok ? 1 : 0, $id]);
    }

    /** last_seen_at ni 5 daqiqada bir martadan ko'p yangilamaydi. */
    public function touch(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET last_seen_at = NOW()
             WHERE id = ? AND (last_seen_at IS NULL OR last_seen_at < NOW() - INTERVAL 5 MINUTE)'
        );
        $stmt->execute([$id]);
    }

    /** @return list<array<string, mixed>> */
    public function listAll(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT " . self::COLUMNS . " FROM users
             ORDER BY role = 'owner' DESC, status = 'pending' DESC, status = 'active' DESC, display_name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countPending(): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE status = 'pending'");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
