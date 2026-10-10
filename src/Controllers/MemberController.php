<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Access;
use App\Core\HttpError;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;
use App\Core\Validator;

/**
 * A'zolar ro'yxati va tasdiqlash — faqat owner.
 */
final class MemberController
{
    /**
     * GET /members
     */
    public function index(Request $request): Response
    {
        Access::requireOwner($request);
        return Response::ok(['items' => array_map([$this, 'present'], Services::users()->listAll())]);
    }

    /**
     * POST /members  { telegram_user_id, display_name } — a'zoni qo'lda qo'shish (darhol faol).
     */
    public function store(Request $request): Response
    {
        $owner = Access::requireOwner($request);
        $v = new Validator($request->json());
        $telegramId = $v->int('telegram_user_id', 1, PHP_INT_MAX, true, 'Telegram ID');
        $name = $v->string('display_name', 128, true, 'Ism');
        $v->throwIfErrors();

        if (Access::isOwnerTelegramId((int) $telegramId)) {
            throw new HttpError(422, 'validation', 'Bu rahbarning o‘z ID raqami.', ['telegram_user_id' => 'Bu rahbarning o‘z ID raqami.']);
        }

        $users = Services::users();
        $user = $users->findByTelegramId((int) $telegramId);
        if ($user === null) {
            $user = $users->create((int) $telegramId, (string) $name, null, 'member', 'active');
        } else {
            $users->setDisplayName((int) $user['id'], (string) $name);
            $users->setStatus((int) $user['id'], 'active');
        }
        Services::audit()->log($owner['id'], 'member.add', 'user', (int) $user['id'], ['display_name' => $name]);

        return Response::ok($this->present((array) $users->findById((int) $user['id'])), 201);
    }

    /**
     * PUT /members/{id}  { status?: active|disabled, display_name? }
     */
    public function update(Request $request): Response
    {
        $owner = Access::requireOwner($request);
        $users = Services::users();
        $user = $users->findById((int) $request->param('id')) ?? throw HttpError::notFound('A’zo topilmadi.');
        if (Access::isOwnerTelegramId((int) $user['telegram_user_id'])) {
            throw new HttpError(409, 'owner_locked', 'Rahbar hisobini o‘zgartirib bo‘lmaydi.');
        }

        $v = new Validator($request->json());
        $status = $v->enum('status', ['active', 'disabled'], null, 'Holat');
        $name = $v->string('display_name', 128, false, 'Ism');
        $v->throwIfErrors();

        if ($name !== null) {
            $users->setDisplayName((int) $user['id'], $name);
        }
        if ($status !== null && $status !== $user['status']) {
            $users->setStatus((int) $user['id'], $status);
            if ($status === 'disabled') {
                Services::sessions()->revokeUser((int) $user['id']);
            }
            Services::audit()->log($owner['id'], 'member.' . ($status === 'active' ? 'approve' : 'disable'), 'user', (int) $user['id'], [
                'display_name' => $user['display_name'],
                'from_status' => $user['status'],
                'to_status' => $status,
            ]);
        }

        return Response::ok($this->present((array) $users->findById((int) $user['id'])));
    }

    /**
     * @param array<string, mixed> $u
     * @return array<string, mixed>
     */
    private function present(array $u): array
    {
        return [
            'id' => (int) $u['id'],
            'telegram_user_id' => (int) $u['telegram_user_id'],
            'name' => (string) $u['display_name'],
            'username' => $u['username'] !== null ? (string) $u['username'] : null,
            'role' => Access::isOwnerTelegramId((int) $u['telegram_user_id']) ? 'owner' : 'member',
            'status' => (string) $u['status'],
            'in_group' => (int) $u['member_ok'] === 1,
            'last_seen_at' => $u['last_seen_at'] !== null ? (string) $u['last_seen_at'] : null,
            'created_at' => (string) $u['created_at'],
        ];
    }
}
