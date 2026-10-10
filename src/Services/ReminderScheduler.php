<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Repositories\EventRepo;
use App\Repositories\ReminderRepo;
use App\Repositories\SettingsRepo;
use App\Telegram\BotApi;
use App\Telegram\MessageBuilder;
use App\Telegram\TelegramException;

/**
 * Eslatmalar: rejalashtirish (to'y saqlanganda, o'sha tranzaksiyada) va yuborish (cron, qo'lda).
 * Takror yuborilmasligi dedupe_key UNIQUE va claim_token bilan kafolatlanadi.
 */
final class ReminderScheduler
{
    public function __construct(
        private readonly ReminderRepo $reminders,
        private readonly EventRepo $events,
        private readonly SettingsRepo $settings,
        private readonly BotApi $bot,
        private readonly MessageBuilder $messages,
        private readonly string $groupChatId,
    ) {
    }

    public static function dedupeKey(int $eventId, string $startsAt): string
    {
        return 'day_before:' . $eventId . ':' . $startsAt;
    }

    /**
     * To'y holati va vaqtiga ko'ra "day_before" eslatmani moslaydi.
     */
    public function sync(int $eventId): void
    {
        $event = $this->events->find($eventId);
        if ($event === null) {
            return;
        }
        $startsAt = (string) $event['starts_at'];
        $active = in_array($event['status'], EventRepo::ACTIVE_STATUSES, true);

        if (!$active || strtotime($startsAt) <= time()) {
            $this->reminders->cancelForEvent($eventId);
            return;
        }

        $key = self::dedupeKey($eventId, $startsAt);
        $scheduledAt = date('Y-m-d H:i:s', strtotime($startsAt) - $this->settings->reminderHoursBefore() * 3600);
        $this->reminders->cancelOthers($eventId, 'day_before', $key);
        $this->reminders->upsert($eventId, 'day_before', $key, $scheduledAt);
    }

    public function cancelForEvent(int $eventId): void
    {
        $this->reminders->cancelForEvent($eventId);
    }

    /**
     * Cron ishchisi: vaqti kelganlarni band qilib yuboradi.
     *
     * @return array{claimed: int, sent: int, failed: int, cancelled: int, released: int}
     */
    public function processDue(int $limit = 10): array
    {
        $stats = ['claimed' => 0, 'sent' => 0, 'failed' => 0, 'cancelled' => 0, 'released' => $this->reminders->releaseStale()];
        $claim = bin2hex(random_bytes(16));
        foreach ($this->reminders->claimDue($claim, $limit) as $row) {
            $stats['claimed']++;
            $stats[$this->deliver($row, $claim)]++;
        }
        return $stats;
    }

    /**
     * "Eslatmani hozir yuborish": yangi manual eslatma yaratib darhol yuboradi.
     *
     * @return array{status: string, error: ?string}
     */
    public function sendNow(int $eventId): array
    {
        $id = $this->reminders->insertManual($eventId, 'manual:' . $eventId . ':' . bin2hex(random_bytes(6)));
        $claim = bin2hex(random_bytes(16));
        $rows = $this->reminders->claimOne($id, $claim);
        if ($rows === []) {
            return ['status' => 'failed', 'error' => 'Eslatmani band qilib boʻlmadi'];
        }
        $result = $this->deliver($rows[0], $claim);
        $row = $this->reminders->find($id);
        return [
            'status' => $result,
            'error' => $result === 'sent' ? null : (string) ($row['last_error'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return 'sent'|'failed'|'cancelled'
     */
    private function deliver(array $row, string $claim): string
    {
        $id = (int) $row['id'];
        $event = $this->events->find((int) $row['event_id']);

        if ($event === null || !in_array($event['status'], EventRepo::ACTIVE_STATUSES, true)) {
            $this->reminders->markCancelled($id, $claim, 'Toʻy faol emas');
            return 'cancelled';
        }
        if (strtotime((string) $event['starts_at']) <= time()) {
            $this->reminders->markCancelled($id, $claim, 'Toʻy boshlanib boʻlgan');
            return 'cancelled';
        }
        if ($row['reminder_type'] === 'day_before' && $row['dedupe_key'] !== self::dedupeKey((int) $event['id'], (string) $event['starts_at'])) {
            $this->reminders->markCancelled($id, $claim, 'Toʻy vaqti oʻzgargan');
            return 'cancelled';
        }
        if ($this->groupChatId === '') {
            $this->reminders->markFailed($id, $claim, 'GROUP_CHAT_ID sozlanmagan', false);
            return 'failed';
        }

        $message = $this->messages->reminder($event);
        try {
            $result = $this->bot->sendMessage($this->groupChatId, $message['text'], $message['reply_markup']);
            $this->reminders->markSent($id, $claim, isset($result['message_id']) ? (int) $result['message_id'] : null);
            return 'sent';
        } catch (TelegramException $e) {
            $final = !$e->isRetryable();
            $this->reminders->markFailed($id, $claim, ($e->errorCode > 0 ? $e->errorCode . ': ' : '') . $e->description, $final);
            Logger::warning('reminder_send_failed', ['reminder_id' => $id, 'code' => $e->errorCode, 'final' => $final]);
            return 'failed';
        }
    }
}
