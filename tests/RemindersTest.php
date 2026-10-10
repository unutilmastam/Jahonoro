<?php

declare(strict_types=1);

use App\Core\Services;
use App\Telegram\TelegramException;

/** Hozirdan N soat keyingi to'y. */
function soon_payload(int $hours, array $over = []): array
{
    $ts = time() + $hours * 3600;
    return event_payload($over + ['event_date' => date('Y-m-d', $ts), 'start_time' => date('H:i', $ts)]);
}

function reminder_rows(): array
{
    return Services::pdo()->query('SELECT * FROM event_reminders ORDER BY id')->fetchAll();
}

test('Reminders: 24 soatdan kam qolgan to\'y — keyingi cron\'da bitta xabar; 3 marta ishga tushirsa ham bitta', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    api('POST', '/events', soon_payload(10, ['total_price' => 9900000, 'client_phone' => '+998 91 000 00 00']), $owner);
    $rows = reminder_rows();
    assert_same(1, count($rows));
    assert_same('pending', $rows[0]['status']);

    for ($i = 0; $i < 3; $i++) {
        Services::reminders()->processDue();
    }
    $sent = $bot->callsOf('sendMessage');
    assert_same(1, count($sent));
    assert_same('-1001234567890', $sent[0]['params']['chat_id']);
    assert_same('HTML', $sent[0]['params']['parse_mode']);
    $text = $sent[0]['params']['text'];
    assert_true(!str_contains($text, '9900000') && !str_contains($text, '000 00 00'), 'Xabarda moliya/telefon');
    assert_same('sent', reminder_rows()[0]['status']);
});

test('Reminders: uzoq to\'y — eslatma 24 soat oldin rejalashtiriladi, hozir yuborilmaydi', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $id = api('POST', '/events', soon_payload(72), $owner)->payload['data']['id'];
    $row = reminder_rows()[0];
    $event = Services::events()->find($id);
    assert_same(date('Y-m-d H:i:s', strtotime($event['starts_at']) - 86400), $row['scheduled_at']);
    Services::reminders()->processDue();
    assert_same(0, count($bot->callsOf('sendMessage')));
});

test('Reminders: takror saqlash — bitta eslatma; vaqt o\'zgarsa eskisi bekor, yangisi', function (): void {
    fresh_db();
    $owner = login_owner();
    $p = soon_payload(72);
    $id = api('POST', '/events', $p, $owner)->payload['data']['id'];
    api('PUT', "/events/{$id}", $p, $owner);
    api('PUT', "/events/{$id}", $p, $owner);
    assert_same(1, count(reminder_rows()));

    api('PUT', "/events/{$id}", soon_payload(80), $owner);
    $rows = reminder_rows();
    assert_same(2, count($rows));
    assert_same('cancelled', $rows[0]['status']);
    assert_same('pending', $rows[1]['status']);
});

test('Reminders: yuborilgandan keyin vaqt o\'zgarsa — yangi eslatma yana bir marta', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $id = api('POST', '/events', soon_payload(10), $owner)->payload['data']['id'];
    Services::reminders()->processDue();
    api('PUT', "/events/{$id}", soon_payload(12), $owner);
    Services::reminders()->processDue();
    Services::reminders()->processDue();
    assert_same(2, count($bot->callsOf('sendMessage')));
});

test('Reminders: bekor qilish → cancelled, tiklash → qayta pending', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $id = api('POST', '/events', soon_payload(10), $owner)->payload['data']['id'];
    api('POST', "/events/{$id}/cancel", null, $owner);
    assert_same('cancelled', reminder_rows()[0]['status']);
    Services::reminders()->processDue();
    assert_same(0, count($bot->callsOf('sendMessage')));

    api('POST', "/events/{$id}/restore", null, $owner);
    assert_same('pending', reminder_rows()[0]['status']);
    Services::reminders()->processDue();
    assert_same(1, count($bot->callsOf('sendMessage')));
});

test('Reminders: band qilingandan keyin to\'y bekor bo\'lsa — yuborilmaydi', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $id = api('POST', '/events', soon_payload(10), $owner)->payload['data']['id'];
    Services::pdo()->exec("UPDATE events SET status = 'cancelled' WHERE id = {$id}");
    $stats = Services::reminders()->processDue();
    assert_same(1, $stats['cancelled']);
    assert_same(0, count($bot->callsOf('sendMessage')));
});

test('Reminders: 429/tarmoq → failed, keyingi safar qayta; 403 → qayta urinilmaydi', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    api('POST', '/events', soon_payload(10), $owner);
    $bot->failWith = new TelegramException(429, 'Too Many Requests', 5);
    Services::reminders()->processDue();
    $row = reminder_rows()[0];
    assert_same('failed', $row['status']);
    assert_same(1, (int) $row['attempts']);

    $bot->failWith = null;
    Services::reminders()->processDue();
    assert_same('sent', reminder_rows()[0]['status']);

    fresh_db();
    $bot = Services::bot();
    $owner = login_owner();
    api('POST', '/events', soon_payload(10), $owner);
    $bot->failWith = new TelegramException(403, 'Forbidden: bot was kicked');
    Services::reminders()->processDue();
    $row = reminder_rows()[0];
    assert_same('failed', $row['status']);
    assert_same(5, (int) $row['attempts']);
    $bot->failWith = null;
    Services::reminders()->processDue();
    assert_same(1, count($bot->callsOf('sendMessage')), 'Faqat birinchi (muvaffaqiyatsiz) urinish');
});

test('Reminders: 10 daqiqadan ortiq "sending" → failed va qayta yuboriladi', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    api('POST', '/events', soon_payload(10), $owner);
    Services::pdo()->exec("UPDATE event_reminders SET status = 'sending', locked_at = NOW() - INTERVAL 11 MINUTE, attempts = 1, claim_token = 'x'");
    $stats = Services::reminders()->processDue();
    assert_same(1, $stats['released']);
    assert_same(1, count($bot->callsOf('sendMessage')));
});

test('Reminders: eslatma soati o\'zgarsa kutilayotganlar qayta hisoblanadi', function (): void {
    fresh_db();
    $owner = login_owner();
    $id = api('POST', '/events', soon_payload(72), $owner)->payload['data']['id'];
    api('PUT', '/settings', ['reminder_hours_before' => 12], $owner);
    $event = Services::events()->find($id);
    assert_same(date('Y-m-d H:i:s', strtotime($event['starts_at']) - 12 * 3600), reminder_rows()[0]['scheduled_at']);
});

test('Remind-now: owner yuboradi, a\'zo 403, bekor to\'yga 409', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $member = login_member($bot);
    $id = api('POST', '/events', soon_payload(72), $owner)->payload['data']['id'];
    assert_same(403, api('POST', "/events/{$id}/remind-now", null, $member)->status);
    $res = api('POST', "/events/{$id}/remind-now", null, $owner);
    assert_same(200, $res->status, $res->body());
    assert_same(1, count($bot->callsOf('sendMessage')));

    $bot->failWith = new TelegramException(400, 'Bad Request: chat not found');
    assert_same(502, api('POST', "/events/{$id}/remind-now", null, $owner)->status);
    $bot->failWith = null;

    api('POST', "/events/{$id}/cancel", null, $owner);
    assert_same(409, api('POST', "/events/{$id}/remind-now", null, $owner)->status);
});

test('Reminders status: owner ko\'radi', function (): void {
    fresh_db();
    $owner = login_owner();
    api('POST', '/events', soon_payload(72), $owner);
    $s = api('GET', '/reminders/status', null, $owner)->payload['data'];
    assert_same(1, $s['counts']['pending']);
    assert_same(1, count($s['items']));
});

test('Publish-button: xabar + pin, settings\'da message_id', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $res = api('POST', '/group/publish-button', null, $owner);
    assert_same(200, $res->status, $res->body());
    assert_same(true, $res->payload['data']['pinned']);
    $send = $bot->callsOf('sendMessage')[0]['params'];
    assert_same('https://t.me/JahonoroTestBot/jadval', $send['reply_markup']['inline_keyboard'][0][0]['url']);
    assert_same('📅 TOʻYLAR JADVALI', $send['reply_markup']['inline_keyboard'][0][0]['text']);
    assert_same(1, count($bot->callsOf('pinChatMessage')));
    assert_same((string) $res->payload['data']['message_id'], Services::settings()->get('group_button_message_id'));
});

test('Health: owner uchun, tokensiz', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $member = login_member($bot);
    assert_same(403, api('GET', '/health', null, $member)->status);
    $res = api('GET', '/health', null, $owner);
    assert_same('ok', $res->payload['data']['telegram_api']);
    assert_true(!str_contains($res->body(), (string) config('BOT_TOKEN')));
});
