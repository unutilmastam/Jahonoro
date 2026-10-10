<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Access;
use App\Core\Db;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;
use App\Telegram\TelegramException;

/**
 * Tizim holati. Javobda bot token yoki boshqa maxfiy ma'lumot hech qachon chiqmaydi.
 */
final class HealthController
{
    /**
     * GET /ping — hosting sinovi (hamma uchun).
     */
    public function ping(Request $request): Response
    {
        return Response::ok([
            'php_version' => PHP_VERSION,
            'config' => config_loaded() ? 'ok' : 'missing',
            'db' => (config_loaded() && Db::ping()) ? 'ok' : 'fail',
            'telegram_api' => $this->telegramReachable() ? 'ok' : 'fail',
            'time' => date('c'),
        ]);
    }

    /**
     * GET /health — owner uchun to'liq holat.
     */
    public function health(Request $request): Response
    {
        Access::requireOwner($request);

        $botUsername = null;
        try {
            $me = Services::bot()->getMe();
            $botUsername = isset($me['username']) ? (string) $me['username'] : null;
            $telegram = 'ok';
        } catch (TelegramException $e) {
            $telegram = 'fail';
        }

        $cron = Services::settings()->get('cron_last_run', '');
        $cronAge = $cron !== '' && $cron !== null ? time() - (int) strtotime($cron) : null;

        return Response::ok([
            'php_version' => PHP_VERSION,
            'db' => 'ok',
            'telegram_api' => $telegram,
            'bot_username' => $botUsername,
            'group_configured' => (string) config('GROUP_CHAT_ID', '') !== '',
            'mini_app_configured' => (string) config('MINI_APP_LINK', '') !== '',
            'cron_last_run' => $cron !== '' ? $cron : null,
            'cron_ok' => $cronAge !== null && $cronAge < 900,
            'reminders' => Services::reminderRepo()->countsByStatus(),
            'time' => date('c'),
        ]);
    }

    /**
     * Bot API getMe: hosting api.telegram.org ga chiqa oladimi va token to'g'rimi.
     */
    private function telegramReachable(): bool
    {
        if ((string) config('BOT_TOKEN', '') === '') {
            return false;
        }
        try {
            Services::bot()->getMe();
            return true;
        } catch (TelegramException $e) {
            Logger::warning('telegram_getme_failed', ['code' => $e->errorCode]);
            return false;
        }
    }
}
