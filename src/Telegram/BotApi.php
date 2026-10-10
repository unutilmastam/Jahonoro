<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Core\Logger;

/**
 * Telegram Bot API mijozi (cURL, timeout 10 s). Token faqat shu klass ichida.
 * Testlarda call() qayta yoziladi.
 */
class BotApi
{
    public function __construct(
        private readonly string $token,
        private readonly int $timeout = 10,
    ) {
    }

    /**
     * @param array<string, mixed> $params
     * @throws TelegramException
     */
    public function call(string $method, array $params = []): mixed
    {
        if ($this->token === '') {
            throw new TelegramException(0, 'Bot token sozlanmagan');
        }
        if (!function_exists('curl_init')) {
            throw new TelegramException(0, 'cURL mavjud emas');
        }

        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/' . $method);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($errno !== 0 || !is_string($raw)) {
            // curl_error matnida URL (token) bo'lishi mumkin — faqat raqam
            Logger::warning('telegram_network_error', ['method' => $method, 'curl_errno' => $errno]);
            throw new TelegramException(0, 'Telegram bilan aloqa yoʻq (cURL ' . $errno . ')');
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new TelegramException($status >= 500 ? $status : 502, 'Telegram javobi notoʻgʻri');
        }
        if (($data['ok'] ?? false) !== true) {
            $code = (int) ($data['error_code'] ?? $status);
            $description = substr((string) ($data['description'] ?? 'Nomaʼlum xato'), 0, 200);
            $retryAfter = isset($data['parameters']['retry_after']) ? (int) $data['parameters']['retry_after'] : null;
            Logger::warning('telegram_api_error', ['method' => $method, 'code' => $code, 'description' => $description]);
            throw new TelegramException($code, $description, $retryAfter);
        }
        return $data['result'] ?? null;
    }

    /** @return array<string, mixed> */
    public function getMe(): array
    {
        return (array) $this->call('getMe');
    }

    /** @return array<string, mixed> */
    public function getChatMember(string|int $chatId, int $userId): array
    {
        return (array) $this->call('getChatMember', ['chat_id' => $chatId, 'user_id' => $userId]);
    }

    /**
     * @param array<string, mixed>|null $replyMarkup
     * @return array<string, mixed>
     */
    public function sendMessage(string|int $chatId, string $html, ?array $replyMarkup = null): array
    {
        $params = [
            'chat_id' => $chatId,
            'text' => $html,
            'parse_mode' => 'HTML',
            'link_preview_options' => ['is_disabled' => true],
        ];
        if ($replyMarkup !== null) {
            $params['reply_markup'] = $replyMarkup;
        }
        return (array) $this->call('sendMessage', $params);
    }

    public function pinChatMessage(string|int $chatId, int $messageId): void
    {
        $this->call('pinChatMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'disable_notification' => true,
        ]);
    }
}
