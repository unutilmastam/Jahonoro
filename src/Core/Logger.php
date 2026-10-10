<?php

declare(strict_types=1);

namespace App\Core;

/**
 * storage/logs/app-YYYY-MM-DD.log ga JSON qatorlar yozadi.
 * Token, parol va telefon raqamlari yozishdan oldin yashiriladi.
 */
final class Logger
{
    private const SENSITIVE_KEYS = ['token', 'bot_token', 'password', 'pass', 'db_pass', 'authorization', 'init_data', 'phone', 'client_phone', 'price', 'total_price', 'amount'];

    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    public static function redact(string $text): string
    {
        // Telegram bot token: 123456789:AA...
        $text = (string) preg_replace('/\d{6,}:[A-Za-z0-9_-]{30,}/', '[bot-token]', $text);
        // Bearer token
        $text = (string) preg_replace('/Bearer\s+[A-Za-z0-9._~+\/=-]+/i', 'Bearer [token]', $text);
        // O'zbekiston telefon raqamlari: +998 90 123 45 67
        $text = (string) preg_replace('/\+?998[\s()-]*\d{2}[\s()-]*\d{3}[\s-]*\d{2}[\s-]*\d{2}/', '[phone]', $text);
        return $text;
    }

    private static function write(string $level, string $message, array $context): void
    {
        $line = json_encode([
            'time' => date('c'),
            'level' => $level,
            'message' => self::redact($message),
            'context' => self::clean($context),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        $dir = APP_ROOT . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $file = $dir . '/app-' . date('Y-m-d') . '.log';
        if (@file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            error_log('[jahonoro] ' . $line);
        }
    }

    private static function clean(array $context): array
    {
        $out = [];
        foreach ($context as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SENSITIVE_KEYS, true)) {
                $out[$key] = '[hidden]';
            } elseif (is_array($value)) {
                $out[$key] = self::clean($value);
            } elseif (is_string($value)) {
                $out[$key] = self::redact($value);
            } elseif (is_scalar($value) || $value === null) {
                $out[$key] = $value;
            } else {
                $out[$key] = get_debug_type($value);
            }
        }
        return $out;
    }
}
