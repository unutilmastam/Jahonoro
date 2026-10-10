<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpError;
use App\Core\Logger;
use App\Repositories\AuditRepo;
use App\Repositories\SettingsRepo;
use App\Repositories\UserRepo;
use App\Telegram\BotApi;
use App\Telegram\MessageBuilder;
use App\Telegram\TelegramException;

/**
 * Guruhni ilovaning o'zidan ulash.
 *
 * 1. Owner "Guruhni ulash" ni bosadi → bir martalik kod va t.me/<bot>?startgroup=<kod>&admin=pin_messages havola.
 * 2. Telegram guruh tanlash oynasini ochadi, owner o'z guruhini tanlaydi, bot admin bo'lib qo'shiladi.
 * 3. getUpdates orqali (webhooksiz) "/start <kod>" xabari yoki botning qo'shilgani haqidagi hodisa topiladi.
 *    Faqat owner qo'shgan va kod muddati o'tmagan bo'lsa guruh ulanadi.
 * 4. Begonalar botni boshqa guruhga qo'shsa — bot o'sha guruhdan chiqib ketadi.
 */
final class GroupLinker
{
    public const CODE_TTL = 1800;

    private const GROUP_TYPES = ['group', 'supergroup'];
    private const IN_STATUSES = ['member', 'administrator', 'creator', 'restricted'];

    public function __construct(
        private readonly BotApi $bot,
        private readonly SettingsRepo $settings,
        private readonly UserRepo $users,
        private readonly AuditRepo $audit,
        private readonly MessageBuilder $messages,
    ) {
    }

    /**
     * @return array{url: string, expires_at: string, bot_username: string}
     */
    public function start(int $actorId): array
    {
        $username = $this->botUsername();
        $code = bin2hex(random_bytes(8));
        $expires = time() + self::CODE_TTL;
        $this->settings->set('group_link_code', $code);
        $this->settings->set('group_link_expires', (string) $expires);
        $this->audit->log($actorId, 'group.link_start', 'settings', null);

        return [
            'url' => 'https://t.me/' . $username . '?startgroup=' . $code . '&admin=pin_messages',
            'expires_at' => date('c', $expires),
            'bot_username' => $username,
        ];
    }

    /**
     * Kelgan yangilanishlarni ko'rib chiqadi (ulash so'rovi va cron chaqiradi).
     *
     * @return array{linked: bool, left: int}
     */
    public function processUpdates(): array
    {
        $offset = (int) $this->settings->get('tg_update_offset', '0');
        $updates = $this->bot->getUpdates($offset);
        if ($updates === []) {
            return ['linked' => false, 'left' => 0];
        }

        $code = $this->activeCode();
        $ownerId = (int) config('OWNER_TELEGRAM_ID', 0);
        $current = $this->settings->groupChatId();
        $candidate = null;
        $migrations = [];
        $toLeave = [];
        $maxId = $offset - 1;

        foreach ($updates as $u) {
            $maxId = max($maxId, (int) ($u['update_id'] ?? 0));

            if (isset($u['message']) && is_array($u['message'])) {
                $m = $u['message'];
                $chat = (array) ($m['chat'] ?? []);
                if (!in_array($chat['type'] ?? '', self::GROUP_TYPES, true)) {
                    continue;
                }
                if (isset($m['migrate_to_chat_id'])) {
                    $migrations[(string) $chat['id']] = (string) $m['migrate_to_chat_id'];
                }
                $text = (string) ($m['text'] ?? '');
                $fromId = (int) ($m['from']['id'] ?? 0);
                if ($code !== null && $fromId === $ownerId
                    && preg_match('/^\/start(?:@\w+)?\s+([A-Za-z0-9_-]{1,64})$/', trim($text), $mm)
                    && hash_equals($code, $mm[1])) {
                    $candidate = ['id' => (string) $chat['id'], 'title' => (string) ($chat['title'] ?? '')];
                }
            }

            if (isset($u['my_chat_member']) && is_array($u['my_chat_member'])) {
                $cm = $u['my_chat_member'];
                $chat = (array) ($cm['chat'] ?? []);
                if (!in_array($chat['type'] ?? '', self::GROUP_TYPES, true)) {
                    continue;
                }
                $chatId = (string) $chat['id'];
                $newStatus = (string) ($cm['new_chat_member']['status'] ?? '');
                $oldStatus = (string) ($cm['old_chat_member']['status'] ?? '');
                $fromId = (int) ($cm['from']['id'] ?? 0);
                $added = in_array($newStatus, self::IN_STATUSES, true) && !in_array($oldStatus, self::IN_STATUSES, true);

                if ($added && $fromId === $ownerId && $code !== null && $candidate === null) {
                    $candidate = ['id' => $chatId, 'title' => (string) ($chat['title'] ?? '')];
                } elseif ($added && $fromId !== $ownerId && $chatId !== $current) {
                    $toLeave[$chatId] = true;
                }
            }
        }

        $this->settings->set('tg_update_offset', (string) ($maxId + 1));

        // Oddiy guruh supergroup'ga aylansa ID o'zgaradi
        if ($current !== '' && isset($migrations[$current]) && $candidate === null) {
            $this->settings->set('group_chat_id', $migrations[$current]);
            Logger::info('group_migrated');
        }

        $left = 0;
        foreach (array_keys($toLeave) as $chatId) {
            if ($candidate !== null && $chatId === $candidate['id']) {
                continue;
            }
            try {
                $this->bot->leaveChat($chatId);
                $left++;
                Logger::info('left_foreign_group');
            } catch (TelegramException $e) {
                Logger::warning('leave_foreign_group_failed', ['code' => $e->errorCode]);
            }
        }

        if ($candidate === null) {
            return ['linked' => false, 'left' => $left];
        }
        $chatId = $migrations[$candidate['id']] ?? $candidate['id'];
        $this->link($chatId, $candidate['title'], $current);
        return ['linked' => true, 'left' => $left];
    }

    /**
     * @return array{linked: bool, title: string, pending: bool, expires_at: ?string, bot_username: ?string, source: string}
     */
    public function status(): array
    {
        $linked = (string) $this->settings->get('group_chat_id', '');
        $fallback = (string) config('GROUP_CHAT_ID', '');
        $expires = (int) $this->settings->get('group_link_expires', '0');
        $username = $this->settings->get('bot_username', '');
        return [
            'linked' => $linked !== '' || $fallback !== '',
            'title' => $linked !== '' ? $this->settings->groupTitle() : '',
            'pending' => $this->activeCode() !== null,
            'expires_at' => $expires > time() ? date('c', $expires) : null,
            'bot_username' => $username !== '' ? $username : null,
            'source' => $linked !== '' ? 'app' : ($fallback !== '' ? 'config' : 'none'),
        ];
    }

    /**
     * Guruhga "TO‘YLAR JADVALI" tugmali xabar yuboradi va pin qiladi.
     *
     * @return array{message_id: int, pinned: bool}
     */
    public function publishButton(): array
    {
        $chatId = $this->settings->groupChatId();
        if ($chatId === '') {
            throw new HttpError(409, 'not_configured', 'Avval guruhni ulang: Sozlamalar → Guruh → Guruhni ulash.');
        }
        try {
            $message = $this->messages->groupButton();
        } catch (\InvalidArgumentException $e) {
            throw new HttpError(409, 'not_configured', 'Mini App havolasi sozlanmagan (config.php → MINI_APP_LINK).');
        }

        try {
            $sent = $this->bot->sendMessage($chatId, $message['text'], $message['reply_markup']);
        } catch (TelegramException $e) {
            if ($e->migrateToChatId !== null) {
                $this->settings->set('group_chat_id', (string) $e->migrateToChatId);
                return $this->publishButton();
            }
            throw new HttpError(502, 'telegram_error', 'Xabar yuborilmadi. Bot guruhda borligini va admin ekanini tekshiring.');
        }

        $messageId = (int) ($sent['message_id'] ?? 0);
        $pinned = false;
        try {
            $this->bot->pinChatMessage($chatId, $messageId);
            $pinned = true;
        } catch (TelegramException $e) {
            Logger::warning('group_pin_failed', ['code' => $e->errorCode]);
        }
        $this->settings->set('group_button_message_id', (string) $messageId);
        return ['message_id' => $messageId, 'pinned' => $pinned];
    }

    private function link(string $chatId, string $title, string $previous): void
    {
        $this->settings->set('group_chat_id', $chatId);
        $this->settings->set('group_title', mb_substr($title, 0, 128));
        $this->settings->set('group_link_code', '');
        $this->settings->set('group_link_expires', '0');
        if ($previous !== $chatId) {
            // Eski guruh a'zolari yangi guruhda bo'lmasa, keyingi so'rovda kira olmaydi
            $this->users->resetMembershipCache();
        }

        $owner = $this->users->findByTelegramId((int) config('OWNER_TELEGRAM_ID', 0));
        $this->audit->log($owner !== null ? (int) $owner['id'] : null, 'group.link', 'settings', null, ['title' => $title]);

        try {
            $this->publishButton();
        } catch (\Throwable $e) {
            Logger::warning('group_link_publish_failed', ['type' => get_class($e)]);
        }
    }

    private function activeCode(): ?string
    {
        $code = (string) $this->settings->get('group_link_code', '');
        $expires = (int) $this->settings->get('group_link_expires', '0');
        return $code !== '' && $expires > time() ? $code : null;
    }

    private function botUsername(): string
    {
        $cached = (string) $this->settings->get('bot_username', '');
        if ($cached !== '') {
            return $cached;
        }
        try {
            $me = $this->bot->getMe();
        } catch (TelegramException $e) {
            throw new HttpError(502, 'telegram_error', 'Telegram bilan aloqa yo‘q. Bot token va internetni tekshiring.');
        }
        $username = (string) ($me['username'] ?? '');
        if ($username === '') {
            throw new HttpError(502, 'telegram_error', 'Bot ma’lumotini olib bo‘lmadi.');
        }
        $this->settings->set('bot_username', $username);
        return $username;
    }
}
