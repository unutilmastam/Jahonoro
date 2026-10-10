<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\HttpError;
use App\Core\Request;
use App\Core\Services;

/**
 * Har endpoint boshida: requireUser() — a'zo yoki owner; requireOwner() — faqat owner.
 * Rol bazadan emas, config'dagi OWNER_TELEGRAM_ID dan aniqlanadi.
 */
final class Access
{
    public const WRITE_LIMIT_PER_MINUTE = 60;

    /**
     * @return array{id: int, telegram_user_id: int, display_name: string, role: string, status: string}
     */
    public static function requireUser(Request $request): array
    {
        $token = $request->bearerToken();
        if ($token === null) {
            throw HttpError::unauthorized();
        }
        $row = Services::sessions()->findUser($token);
        if ($row === null) {
            throw HttpError::unauthorized();
        }

        $isOwner = self::isOwnerTelegramId((int) $row['telegram_user_id']);
        if (!$isOwner) {
            if ($row['status'] === 'pending') {
                throw HttpError::pending();
            }
            if ($row['status'] !== 'active' || !Services::membership()->check($row)) {
                throw HttpError::forbidden();
            }
        }

        Services::users()->touch((int) $row['id']);

        return [
            'id' => (int) $row['id'],
            'telegram_user_id' => (int) $row['telegram_user_id'],
            'display_name' => (string) $row['display_name'],
            'role' => $isOwner ? 'owner' : 'member',
            'status' => (string) $row['status'],
        ];
    }

    /**
     * Faqat owner. Yozish so'rovlariga foydalanuvchi bo'yicha 60/daqiqa chegara.
     *
     * @return array{id: int, telegram_user_id: int, display_name: string, role: string, status: string}
     */
    public static function requireOwner(Request $request): array
    {
        $user = self::requireUser($request);
        if ($user['role'] !== 'owner') {
            throw HttpError::ownerOnly();
        }
        if ($request->method !== 'GET') {
            Services::rateLimiter()->enforce('write:user:' . $user['id'], self::WRITE_LIMIT_PER_MINUTE, 60);
        }
        return $user;
    }

    public static function isOwnerTelegramId(int $telegramUserId): bool
    {
        $owner = (int) config('OWNER_TELEGRAM_ID', 0);
        return $owner > 0 && $telegramUserId === $owner;
    }

    /** @param array{role: string} $user */
    public static function isOwner(array $user): bool
    {
        return $user['role'] === 'owner';
    }
}
