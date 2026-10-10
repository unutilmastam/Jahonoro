<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Access;
use App\Core\HttpError;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;
use App\Repositories\EventRepo;

final class ReminderController
{
    /**
     * POST /events/{id}/remind-now — guruhga darhol eslatma.
     */
    public function remindNow(Request $request): Response
    {
        $user = Access::requireOwner($request);
        $id = (int) $request->param('id');
        $event = Services::events()->find($id) ?? throw HttpError::notFound();
        if (!in_array($event['status'], EventRepo::ACTIVE_STATUSES, true)) {
            throw new HttpError(409, 'invalid_status', 'Bekor qilingan yoki arxivdagi to‘y uchun eslatma yuborilmaydi.');
        }
        if (strtotime((string) $event['starts_at']) <= time()) {
            throw new HttpError(409, 'event_started', 'To‘y boshlanib bo‘lgan — eslatma yuborilmaydi.');
        }
        // Guruhni spam qilmaslik uchun: bitta to'yga 10 daqiqada 3 tadan ko'p emas
        Services::rateLimiter()->enforce('remind:event:' . $id, 3, 600);

        $result = Services::reminders()->sendNow($id);
        Services::audit()->log($user['id'], 'reminder.manual', 'event', $id, ['title' => $event['title'], 'status' => $result['status']]);

        if ($result['status'] !== 'sent') {
            throw new HttpError(502, 'telegram_error', 'Eslatma yuborilmadi. Botning guruhdagi huquqlarini tekshiring va qayta urinib ko‘ring.');
        }
        return Response::ok(['status' => 'sent']);
    }

    /**
     * GET /reminders/status
     */
    public function status(Request $request): Response
    {
        Access::requireOwner($request);
        $items = [];
        foreach (Services::reminderRepo()->recent(10) as $row) {
            $items[] = [
                'id' => (int) $row['id'],
                'event_id' => (int) $row['event_id'],
                'title' => (string) $row['title'],
                'event_date' => (string) $row['event_date'],
                'start_time' => substr((string) $row['start_time'], 0, 5),
                'type' => (string) $row['reminder_type'],
                'status' => (string) $row['status'],
                'scheduled_at' => (string) $row['scheduled_at'],
                'sent_at' => $row['sent_at'] !== null ? (string) $row['sent_at'] : null,
                'attempts' => (int) $row['attempts'],
                'last_error' => $row['last_error'] !== null ? (string) $row['last_error'] : null,
            ];
        }
        $cron = Services::settings()->get('cron_last_run', '');
        return Response::ok([
            'counts' => Services::reminderRepo()->countsByStatus(),
            'items' => $items,
            'cron_last_run' => $cron !== '' ? $cron : null,
            'hours_before' => Services::settings()->reminderHoursBefore(),
        ]);
    }
}
