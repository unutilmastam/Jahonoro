<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Access;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;
use App\Core\Validator;

final class SettingsController
{
    /**
     * GET /settings
     */
    public function show(Request $request): Response
    {
        Access::requireOwner($request);
        return Response::ok($this->present());
    }

    /**
     * PUT /settings  { finance_visible_to_members?, reminder_hours_before? }
     */
    public function update(Request $request): Response
    {
        $user = Access::requireOwner($request);
        $settings = Services::settings();
        $v = new Validator($request->json());

        $changed = [];
        if ($v->has('finance_visible_to_members')) {
            $visible = $v->bool('finance_visible_to_members');
            $v->throwIfErrors();
            $settings->set('finance_visible_to_members', $visible ? '1' : '0');
            $changed[] = 'finance_visible_to_members';
        }
        if ($v->has('reminder_hours_before')) {
            $hours = $v->int('reminder_hours_before', 1, 72, true, 'Necha soat oldin');
            $v->throwIfErrors();
            if ($hours !== $settings->reminderHoursBefore()) {
                $settings->set('reminder_hours_before', (string) $hours);
                $changed[] = 'reminder_hours_before';
                $this->resyncReminders();
            }
        }

        if ($changed !== []) {
            Services::audit()->log($user['id'], 'settings.update', 'settings', null, ['changed' => $changed]);
        }
        return Response::ok($this->present());
    }

    /** Eslatma soati o'zgarsa kelgusi to'ylar eslatmalari qayta hisoblanadi (4-bosqich). */
    private function resyncReminders(): void
    {
        if (!method_exists(Services::class, 'reminders')) {
            return;
        }
        $scheduler = Services::reminders();
        foreach (Services::events()->upcomingActiveIds() as $id) {
            $scheduler->sync($id);
        }
    }

    /** @return array<string, mixed> */
    private function present(): array
    {
        $settings = Services::settings();
        $cron = $settings->get('cron_last_run', '');
        return [
            'finance_visible_to_members' => $settings->financeVisibleToMembers(),
            'reminder_hours_before' => $settings->reminderHoursBefore(),
            'cron_last_run' => $cron !== '' && $cron !== null ? $cron : null,
            'timezone' => (string) config('TIMEZONE', 'Asia/Tashkent'),
        ];
    }
}
