<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Yagona PDO ulanishi. Faqat prepared so'rovlar, xatoda exception.
 */
final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                (string) config('DB_HOST', 'localhost'),
                (int) config('DB_PORT', 3306),
                (string) config('DB_NAME', '')
            );
            self::$pdo = new PDO($dsn, (string) config('DB_USER', ''), (string) config('DB_PASS', ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            // Asia/Tashkent: UTC+5, yozgi vaqt yo'q
            self::$pdo->exec("SET time_zone = '+05:00'");
        }
        return self::$pdo;
    }

    /**
     * Ulanish va oddiy so'rov ishlayaptimi.
     */
    public static function ping(): bool
    {
        try {
            $stmt = self::pdo()->prepare('SELECT 1');
            $stmt->execute();
            return (int) $stmt->fetchColumn() === 1;
        } catch (\Throwable $e) {
            Logger::error('db_ping_failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
