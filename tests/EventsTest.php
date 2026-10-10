<?php

declare(strict_types=1);

use App\Core\Services;

function event_payload(array $over = []): array
{
    return $over + [
        'title' => 'Kelin to‘yi — Aziz va Madina', 'event_type' => 'kelin', 'event_date' => '2030-05-10',
        'start_time' => '18:00', 'assembly_time' => '16:30', 'venue_name' => 'Sohil to‘yxonasi',
        'venue_address' => 'Mustaqillik ko‘chasi 12', 'district' => 'Denov',
    ];
}

test('Events: yaratish, o\'qish, tahrirlash', function (): void {
    fresh_db();
    $owner = login_owner();
    $res = api('POST', '/events', event_payload(), $owner);
    assert_same(201, $res->status, $res->body());
    $e = $res->payload['data'];
    assert_same('2030-05-10 18:00:00', $e['starts_at']);
    assert_same('16:30', $e['assembly_time']);

    $res = api('PUT', '/events/' . $e['id'], event_payload(['start_time' => '19:30', 'status' => 'confirmed']), $owner);
    assert_same(200, $res->status, $res->body());
    assert_same('2030-05-10 19:30:00', $res->payload['data']['starts_at']);
    assert_same('confirmed', $res->payload['data']['status']);

    $audit = Services::pdo()->query("SELECT safe_metadata FROM audit_logs WHERE action = 'event.update'")->fetchColumn();
    $meta = json_decode((string) $audit, true);
    assert_true(in_array('start_time', $meta['changed'], true), 'changed ro\'yxatida start_time');
});

test('Events: validatsiya xatolari o\'zbekcha, 422', function (): void {
    fresh_db();
    $owner = login_owner();
    $res = api('POST', '/events', ['event_date' => '2030-02-30', 'start_time' => '25:00', 'total_price' => '-5'], $owner);
    assert_same(422, $res->status);
    $d = $res->payload['error']['details'];
    assert_true(isset($d['title'], $d['event_date'], $d['start_time'], $d['total_price']), json_encode($d));
    assert_same('Kelishilgan narx manfiy bo‘lishi mumkin emas.', $d['total_price']);
});

test('Events: koordinatalar faqat juft, tasdiq koordinatasiz bo\'lmaydi', function (): void {
    fresh_db();
    $owner = login_owner();
    $res = api('POST', '/events', event_payload(['latitude' => '38.2']), $owner);
    assert_same(422, $res->status);
    $res = api('POST', '/events', event_payload(['location_verified' => true]), $owner);
    assert_same(201, $res->status);
    assert_same(false, $res->payload['data']['location_verified']);
});

test('Events: mojaro → 409 + ro\'yxat, confirm_conflict bilan saqlanadi', function (): void {
    fresh_db();
    $owner = login_owner();
    api('POST', '/events', event_payload(), $owner);
    $res = api('POST', '/events', event_payload(['title' => 'Ikkinchi', 'start_time' => '11:00']), $owner);
    assert_same(409, $res->status);
    assert_same('conflict', $res->payload['error']['code']);
    assert_same(1, count($res->payload['error']['details']['conflicts']));
    assert_true(!str_contains($res->body(), 'total_price'));

    $res = api('POST', '/events', event_payload(['title' => 'Ikkinchi', 'start_time' => '11:00', 'confirm_conflict' => true]), $owner);
    assert_same(201, $res->status);
});

test('Events: yarim tundan o\'tadigan vaqt kesishuvi ham mojaro', function (): void {
    fresh_db();
    $owner = login_owner();
    api('POST', '/events', event_payload(['event_date' => '2030-05-10', 'start_time' => '22:00', 'duration_min' => 240]), $owner);
    $res = api('POST', '/events', event_payload(['event_date' => '2030-05-11', 'start_time' => '00:30']), $owner);
    assert_same(409, $res->status);
});

test('Events: bekor qilingan to\'y mojaro emas; bekor/tiklash/arxiv', function (): void {
    fresh_db();
    $owner = login_owner();
    $id = api('POST', '/events', event_payload(), $owner)->payload['data']['id'];
    $res = api('POST', "/events/{$id}/cancel", null, $owner);
    assert_same('cancelled', $res->payload['data']['status']);
    assert_same(409, api('POST', "/events/{$id}/cancel", null, $owner)->status);
    assert_same(201, api('POST', '/events', event_payload(['title' => 'Boshqa']), $owner)->status);

    assert_same('planned', api('POST', "/events/{$id}/restore", null, $owner)->payload['data']['status']);
    assert_same('archived', api('POST', "/events/{$id}/archive", null, $owner)->payload['data']['status']);
});

test('Events: arxiv a\'zoga ko\'rinmaydi (404), ro\'yxatda yo\'q', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $member = login_member($bot);
    $id = api('POST', '/events', event_payload(), $owner)->payload['data']['id'];
    api('POST', "/events/{$id}/archive", null, $owner);
    assert_same(404, api('GET', "/events/{$id}", null, $member)->status);
    assert_same([], api('GET', '/events', null, $member)->payload['data']['items']);
    assert_same([], api('GET', '/events', null, $member, ['status' => 'archived'])->payload['data']['items']);
    assert_same(1, count(api('GET', '/events', null, $owner, ['status' => 'archived'])->payload['data']['items']));
});

test('Events: ro\'yxat filtr, qidiruv, sahifalash (20)', function (): void {
    fresh_db();
    $owner = login_owner();
    for ($i = 1; $i <= 23; $i++) {
        $date = date('Y-m-d', strtotime("2030-01-01 +{$i} days"));
        api('POST', '/events', event_payload(['event_date' => $date, 'title' => "To‘y {$i}", 'event_type' => $i % 2 ? 'kelin' : 'sunnat', 'client_name' => "Mijoz{$i}"]), $owner);
    }
    $p1 = api('GET', '/events', null, $owner, ['scope' => 'upcoming'])->payload['data'];
    assert_same(20, count($p1['items']));
    assert_true($p1['has_more']);
    $p2 = api('GET', '/events', null, $owner, ['scope' => 'upcoming', 'page' => 2])->payload['data'];
    assert_same(3, count($p2['items']));
    assert_true(!$p2['has_more']);

    assert_same(11, count(api('GET', '/events', null, $owner, ['type' => 'sunnat'])->payload['data']['items']));
    assert_same(1, count(api('GET', '/events', null, $owner, ['q' => 'To‘y 17'])->payload['data']['items']));
    assert_same(0, count(api('GET', '/events', null, $owner, ['scope' => 'past'])->payload['data']['items']));
    assert_same(422, api('GET', '/events', null, $owner, ['from' => 'kecha'])->status);
});

test('Events: a\'zo mijoz ismi bo\'yicha qidira olmaydi', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $member = login_member($bot);
    api('POST', '/events', event_payload(['client_name' => 'Karimov']), $owner);
    assert_same(1, count(api('GET', '/events', null, $owner, ['q' => 'Karimov'])->payload['data']['items']));
    assert_same(0, count(api('GET', '/events', null, $member, ['q' => 'Karimov'])->payload['data']['items']));
});

test('Payments: qo\'shish, qoldiq, o\'chirish; summa > 0', function (): void {
    fresh_db();
    $owner = login_owner();
    $id = api('POST', '/events', event_payload(['total_price' => 10000000]), $owner)->payload['data']['id'];
    assert_same(422, api('POST', "/events/{$id}/payments", ['amount' => 0], $owner)->status);
    assert_same(422, api('POST', "/events/{$id}/payments", ['amount' => '-100'], $owner)->status);
    $r = api('POST', "/events/{$id}/payments", ['amount' => '2 500 000', 'paid_on' => '2030-01-02', 'note' => 'avans'], $owner);
    assert_same(201, $r->status, $r->body());
    $r = api('POST', "/events/{$id}/payments", ['amount' => 1500000], $owner)->payload['data'];
    assert_same('4000000.00', $r['paid']);
    assert_same('6000000.00', $r['balance']);
    $pid = array_values(array_filter($r['items'], fn (array $i): bool => $i['amount'] === '2500000.00'))[0]['id'];
    $r = api('DELETE', "/events/{$id}/payments/{$pid}", null, $owner)->payload['data'];
    assert_same('8500000.00', $r['balance']);
    assert_same(404, api('POST', '/events/999/payments', ['amount' => 5], $owner)->status);

    $meta = Services::pdo()->query("SELECT GROUP_CONCAT(safe_metadata) FROM audit_logs WHERE action LIKE 'payment.%'")->fetchColumn();
    assert_true(!str_contains((string) $meta, '2500000') && !str_contains((string) $meta, 'avans'), 'Auditda summa/izoh bor');
});

test('Dashboard: keyingi to\'y, sonlar, owner qarzdorligi', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $member = login_member($bot);
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    api('POST', '/events', event_payload(['event_date' => $tomorrow, 'total_price' => 5000000]), $owner);
    $d = api('GET', '/dashboard', null, $owner)->payload['data'];
    assert_same($tomorrow, $d['next']['event_date']);
    assert_same('5000000.00', $d['debt']['balance']);
    assert_same(1, $d['counts']['upcoming']);
    assert_same(1, count($d['recent']));

    $m = api('GET', '/dashboard', null, $member)->payload['data'];
    assert_true(!isset($m['debt']) && !isset($m['pending_members']) && !isset($m['next']['finance']));
});

test('Calendar: oy bo\'yicha, noto\'g\'ri oy 422', function (): void {
    fresh_db();
    $owner = login_owner();
    api('POST', '/events', event_payload(['event_date' => '2030-05-10']), $owner);
    api('POST', '/events', event_payload(['event_date' => '2030-06-01']), $owner);
    $c = api('GET', '/calendar', null, $owner, ['month' => '2030-05'])->payload['data'];
    assert_same(1, count($c['items']));
    assert_same(422, api('GET', '/calendar', null, $owner, ['month' => '2030-13'])->status);
});

test('Members: tasdiqlash, o\'chirish sessiyani bekor qiladi, owner himoyalangan', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $bot->members[4001] = 'member';
    api('POST', '/auth/telegram', ['init_data' => make_init_data(['id' => 4001, 'first_name' => 'Sardor'])]);
    $list = api('GET', '/members', null, $owner)->payload['data']['items'];
    assert_same('owner', $list[0]['role']);
    assert_same('pending', $list[1]['status']);

    $id = $list[1]['id'];
    assert_same('active', api('PUT', "/members/{$id}", ['status' => 'active'], $owner)->payload['data']['status']);
    $token = api('POST', '/auth/telegram', ['init_data' => make_init_data(['id' => 4001, 'first_name' => 'Sardor'])])->payload['data']['token'];
    assert_same(200, api('GET', '/me', null, $token)->status);
    api('PUT', "/members/{$id}", ['status' => 'disabled'], $owner);
    assert_same(401, api('GET', '/me', null, $token)->status);

    assert_same(409, api('PUT', '/members/' . $list[0]['id'], ['status' => 'disabled'], $owner)->status);
    $r = api('POST', '/members', ['telegram_user_id' => 4555, 'display_name' => 'Jasur'], $owner);
    assert_same(201, $r->status);
    assert_same('active', $r->payload['data']['status']);
});

test('Settings: o\'qish va yangilash, chegaralar', function (): void {
    fresh_db();
    $owner = login_owner();
    $s = api('GET', '/settings', null, $owner)->payload['data'];
    assert_same(false, $s['finance_visible_to_members']);
    assert_same(24, $s['reminder_hours_before']);
    assert_same(422, api('PUT', '/settings', ['reminder_hours_before' => 100], $owner)->status);
    $s = api('PUT', '/settings', ['reminder_hours_before' => 12, 'finance_visible_to_members' => true], $owner)->payload['data'];
    assert_same(12, $s['reminder_hours_before']);
    assert_same(true, $s['finance_visible_to_members']);
});

