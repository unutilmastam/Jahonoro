<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Access;
use App\Core\HttpError;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;
use App\Telegram\TelegramException;

final class GroupController
{
    /**
     * POST /group/publish-button — guruhga "TOʻYLAR JADVALI" URL tugmali xabar yuboradi va pin qiladi.
     */
    public function publishButton(Request $request): Response
    {
        $user = Access::requireOwner($request);
        $chatId = (string) config('GROUP_CHAT_ID', '');
        if ($chatId === '') {
            throw new HttpError(409, 'not_configured', 'Guruh ID sozlanmagan (config.php → GROUP_CHAT_ID).');
        }
        try {
            $message = Services::messages()->groupButton();
        } catch (\InvalidArgumentException $e) {
            throw new HttpError(409, 'not_configured', 'Mini App havolasi sozlanmagan (config.php → MINI_APP_LINK).');
        }

        $bot = Services::bot();
        try {
            $sent = $bot->sendMessage($chatId, $message['text'], $message['reply_markup']);
        } catch (TelegramException $e) {
            throw new HttpError(502, 'telegram_error', 'Xabar yuborilmadi. Bot guruhda borligini va admin ekanini tekshiring.');
        }

        $messageId = (int) ($sent['message_id'] ?? 0);
        $pinned = false;
        try {
            $bot->pinChatMessage($chatId, $messageId);
            $pinned = true;
        } catch (TelegramException $e) {
            Logger::warning('group_pin_failed', ['code' => $e->errorCode]);
        }

        Services::settings()->set('group_button_message_id', (string) $messageId);
        Services::audit()->log($user['id'], 'group.publish_button', 'settings', null, ['status' => $pinned ? 'pinned' : 'sent']);

        return Response::ok(['message_id' => $messageId, 'pinned' => $pinned]);
    }
}
