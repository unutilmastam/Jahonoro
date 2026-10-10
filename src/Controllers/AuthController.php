<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Access;
use App\Core\HttpError;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;

final class AuthController
{
    /**
     * POST /auth/telegram  { init_data } → { token, expires_at, user }
     */
    public function telegram(Request $request): Response
    {
        Services::rateLimiter()->enforce('auth:ip:' . $request->ip, 20, 600);

        $initData = $request->input('init_data');
        if (!is_string($initData) || $initData === '') {
            throw new HttpError(401, 'invalid_init_data', 'Bu ilova faqat JAHONORO guruhidagi tugma orqali ochiladi.');
        }

        $verified = Services::initData()->verify($initData);
        $tg = $verified['user'];
        $telegramId = (int) $tg['id'];
        $name = self::displayName($tg);
        $username = isset($tg['username']) && is_string($tg['username']) ? mb_substr($tg['username'], 0, 64) : null;

        $users = Services::users();

        if (Access::isOwnerTelegramId($telegramId)) {
            $user = $users->upsertOwner($telegramId, $name, $username);
        } else {
            $user = $users->findByTelegramId($telegramId);
            if ($user === null) {
                if (!Services::membership()->isGroupMember($telegramId)) {
                    throw HttpError::forbidden();
                }
                $user = $users->create($telegramId, $name, $username, 'member', 'pending');
                $users->setMembership((int) $user['id'], true);
                Services::audit()->log((int) $user['id'], 'member.request', 'user', (int) $user['id'], ['display_name' => $name]);
                throw HttpError::pending();
            }

            $users->updateProfile((int) $user['id'], $name, $username);
            if ($user['status'] === 'pending') {
                throw HttpError::pending();
            }
            if ($user['status'] !== 'active' || !Services::membership()->check($user)) {
                throw HttpError::forbidden();
            }
        }

        $session = Services::sessions()->create((int) $user['id'], $request->ip);
        Services::users()->touch((int) $user['id']);

        return Response::ok([
            'token' => $session['token'],
            'expires_at' => $session['expires_at'],
            'user' => [
                'id' => (int) $user['id'],
                'name' => $name,
                'role' => Access::isOwnerTelegramId($telegramId) ? 'owner' : 'member',
            ],
        ]);
    }

    /**
     * GET /me
     */
    public function me(Request $request): Response
    {
        $user = Access::requireUser($request);
        return Response::ok([
            'id' => $user['id'],
            'name' => $user['display_name'],
            'role' => $user['role'],
        ]);
    }

    /** @param array<string, mixed> $tg */
    private static function displayName(array $tg): string
    {
        $first = is_string($tg['first_name'] ?? null) ? $tg['first_name'] : '';
        $last = is_string($tg['last_name'] ?? null) ? $tg['last_name'] : '';
        $name = trim(preg_replace('/\s+/u', ' ', $first . ' ' . $last) ?? '');
        if ($name === '' && is_string($tg['username'] ?? null)) {
            $name = $tg['username'];
        }
        return mb_substr($name !== '' ? $name : 'Foydalanuvchi', 0, 128);
    }
}
