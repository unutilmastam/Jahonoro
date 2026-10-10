<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\HttpError;

/**
 * Telegram Mini App initData imzosini tekshiradi.
 * secret = HMAC_SHA256(key="WebAppData", msg=bot_token)
 * hash   = hex(HMAC_SHA256(key=secret, msg=data_check_string))
 */
final class InitDataVerifier
{
    public function __construct(
        private readonly string $botToken,
        private readonly int $maxAgeSeconds = 3600,
    ) {
    }

    /**
     * @return array{user: array<string, mixed>, auth_date: int}
     * @throws HttpError 401
     */
    public function verify(string $initData, ?int $now = null): array
    {
        $now ??= time();
        if ($this->botToken === '' || $initData === '' || strlen($initData) > 8192) {
            throw self::invalid();
        }

        $fields = [];
        foreach (explode('&', $initData) as $pair) {
            if ($pair === '') {
                continue;
            }
            $parts = explode('=', $pair, 2);
            if (count($parts) !== 2) {
                throw self::invalid();
            }
            $key = urldecode($parts[0]);
            if (array_key_exists($key, $fields)) {
                throw self::invalid();
            }
            $fields[$key] = urldecode($parts[1]);
        }

        $hash = $fields['hash'] ?? '';
        unset($fields['hash']);
        if (!is_string($hash) || !preg_match('/^[a-f0-9]{64}$/', $hash)) {
            throw self::invalid();
        }

        ksort($fields, SORT_STRING);
        $lines = [];
        foreach ($fields as $key => $value) {
            $lines[] = $key . '=' . $value;
        }
        $dataCheckString = implode("\n", $lines);

        $secret = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
        $expected = hash_hmac('sha256', $dataCheckString, $secret);
        if (!hash_equals($expected, $hash)) {
            throw self::invalid();
        }

        $authDate = (int) ($fields['auth_date'] ?? 0);
        if ($authDate <= 0 || $now - $authDate > $this->maxAgeSeconds || $authDate - $now > 300) {
            throw new HttpError(401, 'init_data_expired', 'Sessiya eskirgan. Ilovani yopib, guruhdagi tugmadan qayta oching.');
        }

        $user = json_decode((string) ($fields['user'] ?? ''), true);
        if (!is_array($user) || !isset($user['id']) || !is_int($user['id']) || $user['id'] <= 0) {
            throw self::invalid();
        }

        return ['user' => $user, 'auth_date' => $authDate];
    }

    private static function invalid(): HttpError
    {
        return new HttpError(401, 'invalid_init_data', 'Bu ilova faqat JAHONORO guruhidagi tugma orqali ochiladi.');
    }
}
