<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Logger;
use App\Repositories\UserRepo;
use App\Telegram\BotApi;
use App\Telegram\TelegramException;

/**
 * Guruh a'zoligi getChatMember orqali. Natija 10 daqiqa keshlanadi.
 * Telegram vaqtincha javob bermasa — oxirgi 24 soat ichidagi ijobiy natijaga ishoniladi.
 */
final class MembershipService
{
    public const CACHE_SECONDS = 600;
    public const GRACE_SECONDS = 86400;

    private const OK_STATUSES = ['creator', 'administrator', 'member'];

    public function __construct(
        private readonly BotApi $bot,
        private readonly UserRepo $users,
        private readonly string $groupChatId,
    ) {
    }

    /**
     * Bazadagi foydalanuvchi hali guruhdami (kesh bilan).
     *
     * @param array<string, mixed> $user users qatori
     */
    public function check(array $user, bool $force = false): bool
    {
        $checkedAt = $user['member_checked_at'] !== null ? strtotime((string) $user['member_checked_at']) : false;
        $age = $checkedAt !== false ? time() - $checkedAt : PHP_INT_MAX;

        if (!$force && $age < self::CACHE_SECONDS) {
            return (int) $user['member_ok'] === 1;
        }

        try {
            $ok = $this->askTelegram((int) $user['telegram_user_id']);
        } catch (TelegramException $e) {
            if ($e->isRetryable()) {
                $grace = (int) $user['member_ok'] === 1 && $age < self::GRACE_SECONDS;
                Logger::warning('membership_check_unavailable', ['user_id' => (int) $user['id'], 'grace' => $grace]);
                return $grace;
            }
            $ok = false;
        }
        $this->users->setMembership((int) $user['id'], $ok);
        return $ok;
    }

    /**
     * Bazada yo'q odam uchun to'g'ridan-to'g'ri tekshiruv (keshsiz).
     */
    public function isGroupMember(int $telegramUserId): bool
    {
        try {
            return $this->askTelegram($telegramUserId);
        } catch (TelegramException $e) {
            return false;
        }
    }

    private function askTelegram(int $telegramUserId): bool
    {
        if ($this->groupChatId === '') {
            throw new TelegramException(400, 'GROUP_CHAT_ID sozlanmagan');
        }
        $member = $this->bot->getChatMember($this->groupChatId, $telegramUserId);
        $status = (string) ($member['status'] ?? '');
        if (in_array($status, self::OK_STATUSES, true)) {
            return true;
        }
        return $status === 'restricted' && ($member['is_member'] ?? false) === true;
    }
}
