<?php

declare(strict_types=1);

namespace App\Auth;

use PDO;

/**
 * Bearer token sessiyalari. Bazada faqat SHA-256 hash saqlanadi, muddat 12 soat.
 */
final class SessionService
{
    public const TTL_SECONDS = 43200;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{token: string, expires_at: string} */
    public function create(int $userId, string $ip): array
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + self::TTL_SECONDS);

        $stmt = $this->pdo->prepare('INSERT INTO sessions (user_id, token_hash, expires_at, ip) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, hash('sha256', $token), $expires, substr($ip, 0, 45)]);

        if (random_int(1, 50) === 1) {
            $this->purgeExpired();
        }
        return ['token' => $token, 'expires_at' => date('c', time() + self::TTL_SECONDS)];
    }

    /**
     * Token bo'yicha foydalanuvchi qatori (muddati o'tmagan bo'lsa).
     */
    public function findUser(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.telegram_user_id, u.display_name, u.username, u.role, u.status,
                    u.member_checked_at, u.member_ok, u.last_seen_at
             FROM sessions s
             JOIN users u ON u.id = s.user_id
             WHERE s.token_hash = ? AND s.expires_at > NOW()'
        );
        $stmt->execute([hash('sha256', $token)]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function revokeUser(int $userId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE user_id = ?');
        $stmt->execute([$userId]);
    }

    public function purgeExpired(): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM sessions WHERE expires_at < NOW()');
        $stmt->execute();
    }
}
