<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Db;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;

/**
 * Tizim holati. 0-bosqichda faqat /ping — hosting sinovi uchun.
 * Javobda bot token yoki boshqa maxfiy ma'lumot hech qachon chiqmaydi.
 */
final class HealthController
{
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
     * Bot API getMe: hosting api.telegram.org ga chiqa oladimi va token to'g'rimi.
     */
    private function telegramReachable(): bool
    {
        $token = (string) config('BOT_TOKEN', '');
        if ($token === '' || !function_exists('curl_init')) {
            return false;
        }

        $ch = curl_init('https://api.telegram.org/bot' . $token . '/getMe');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($errno !== 0 || !is_string($raw)) {
            // URL'da token bor — shuning uchun faqat xato raqami yoziladi
            Logger::warning('telegram_getme_network', ['curl_errno' => $errno]);
            return false;
        }
        $data = json_decode($raw, true);
        $ok = $status === 200 && is_array($data) && ($data['ok'] ?? false) === true;
        if (!$ok) {
            Logger::warning('telegram_getme_failed', ['http_status' => $status]);
        }
        return $ok;
    }
}
