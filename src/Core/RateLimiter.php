<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Qat'iy oynali so'rov chegarasi (rate_limits jadvali).
 */
final class RateLimiter
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Hisoblagichni oshiradi. Chegara oshgan bo'lsa false.
     */
    public function hit(string $bucket, int $limit, int $windowSeconds, ?int $now = null): bool
    {
        $now ??= time();
        $window = intdiv($now, $windowSeconds) * $windowSeconds;
        $bucket = substr($bucket, 0, 100);

        $stmt = $this->pdo->prepare(
            'INSERT INTO rate_limits (bucket, window_start, hits) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE hits = hits + 1'
        );
        $stmt->execute([$bucket, $window]);

        $stmt = $this->pdo->prepare('SELECT hits FROM rate_limits WHERE bucket = ? AND window_start = ?');
        $stmt->execute([$bucket, $window]);
        $hits = (int) $stmt->fetchColumn();

        if (random_int(1, 100) === 1) {
            $clean = $this->pdo->prepare('DELETE FROM rate_limits WHERE window_start < ?');
            $clean->execute([$now - 86400]);
        }
        return $hits <= $limit;
    }

    public function enforce(string $bucket, int $limit, int $windowSeconds): void
    {
        if (!$this->hit($bucket, $limit, $windowSeconds)) {
            throw HttpError::tooMany();
        }
    }
}
