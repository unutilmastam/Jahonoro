<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class SettingsRepo
{
    /** @var array<string, string|null>|null */
    private ?array $cache = null;

    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        if ($this->cache === null) {
            $stmt = $this->pdo->prepare('SELECT setting_key, setting_value FROM settings');
            $stmt->execute();
            $this->cache = [];
            foreach ($stmt->fetchAll() as $row) {
                $this->cache[(string) $row['setting_key']] = $row['setting_value'] !== null ? (string) $row['setting_value'] : null;
            }
        }
        return $this->cache;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, string $value): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
        $this->cache = null;
    }

    public function financeVisibleToMembers(): bool
    {
        return $this->get('finance_visible_to_members', '0') === '1';
    }

    public function reminderHoursBefore(): int
    {
        $hours = (int) $this->get('reminder_hours_before', '24');
        return $hours >= 1 && $hours <= 72 ? $hours : 24;
    }

    /**
     * Ulangan guruh ID'si: ilovadan ulangan (settings) yoki zaxira sifatida config.php.
     */
    public function groupChatId(): string
    {
        $linked = (string) $this->get('group_chat_id', '');
        return $linked !== '' ? $linked : (string) config('GROUP_CHAT_ID', '');
    }

    public function groupTitle(): string
    {
        return (string) $this->get('group_title', '');
    }
}
