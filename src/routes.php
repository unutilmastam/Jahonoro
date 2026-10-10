<?php

declare(strict_types=1);

/*
 * API marshrutlari (ARCHITECTURE.md 4-bo'lim). Yo'llar /api prefiksisiz.
 */

use App\Controllers\AuthController;
use App\Controllers\CalendarController;
use App\Controllers\DashboardController;
use App\Controllers\EventController;
use App\Controllers\GroupController;
use App\Controllers\HealthController;
use App\Controllers\MemberController;
use App\Controllers\PaymentController;
use App\Controllers\ReminderController;
use App\Controllers\SettingsController;
use App\Core\Router;

return static function (Router $r): void {
    $health = new HealthController();
    $auth = new AuthController();
    $events = new EventController();
    $payments = new PaymentController();
    $calendar = new CalendarController();
    $dashboard = new DashboardController();
    $settings = new SettingsController();
    $members = new MemberController();
    $reminders = new ReminderController();
    $group = new GroupController();

    $r->get('/ping', [$health, 'ping']);
    $r->get('/health', [$health, 'health']);

    $r->post('/auth/telegram', [$auth, 'telegram']);
    $r->get('/me', [$auth, 'me']);

    $r->get('/dashboard', [$dashboard, 'index']);
    $r->get('/calendar', [$calendar, 'month']);

    $r->get('/events', [$events, 'index']);
    $r->post('/events', [$events, 'store']);
    $r->get('/events/{id}', [$events, 'show']);
    $r->put('/events/{id}', [$events, 'update']);
    $r->post('/events/{id}/cancel', [$events, 'cancel']);
    $r->post('/events/{id}/restore', [$events, 'restore']);
    $r->post('/events/{id}/archive', [$events, 'archive']);

    $r->get('/events/{id}/payments', [$payments, 'index']);
    $r->post('/events/{id}/payments', [$payments, 'store']);
    $r->delete('/events/{id}/payments/{payment_id}', [$payments, 'destroy']);

    $r->get('/settings', [$settings, 'show']);
    $r->put('/settings', [$settings, 'update']);

    $r->get('/members', [$members, 'index']);
    $r->post('/members', [$members, 'store']);
    $r->put('/members/{id}', [$members, 'update']);

    $r->post('/events/{id}/remind-now', [$reminders, 'remindNow']);
    $r->get('/reminders/status', [$reminders, 'status']);
    $r->post('/group/publish-button', [$group, 'publishButton']);
};
