<?php

declare(strict_types=1);

use App\Core\Services;
use App\Services\FinanceGuard;

function sample_event(array $over = []): array
{
    return $over + [
        'id' => '5', 'title' => 'Kelin to‘yi — Aziz va Madina', 'event_type' => 'kelin',
        'event_date' => '2026-10-18', 'start_time' => '18:00:00', 'starts_at' => '2026-10-18 18:00:00',
        'nahor_time' => null, 'abet_time' => '13:00:00', 'assembly_time' => '16:30:00', 'departure_time' => null,
        'soundcheck_time' => null, 'duration_min' => null, 'venue_name' => 'Sohil', 'venue_address' => 'Denov',
        'district' => 'Denov', 'latitude' => '38.270000', 'longitude' => '67.890000', 'location_verified' => '0',
        'total_price' => '12500000.00', 'financial_visibility' => 'inherit', 'client_name' => 'Karim aka',
        'client_phone' => '+998 90 123 45 67', 'performance_notes' => null, 'equipment_notes' => null,
        'dress_code' => '', 'schedule_notes' => null, 'internal_notes' => 'maxfiy', 'status' => 'planned',
        'updated_at' => '2026-10-10 10:00:00',
    ];
}

const SECRET_KEYS = ['client_name', 'client_phone', 'internal_notes', 'financial_visibility'];

function assert_no_secrets(array $out): void
{
    foreach (SECRET_KEYS as $key) {
        assert_true(!array_key_exists($key, $out), "A'zo javobida '{$key}' bor");
    }
    $json = json_encode($out, JSON_UNESCAPED_UNICODE);
    assert_true(!str_contains($json, 'Karim'), 'Mijoz ismi sizdi');
    assert_true(!str_contains($json, '123 45 67'), 'Telefon sizdi');
    assert_true(!str_contains($json, 'maxfiy'), 'Ichki izoh sizdi');
}

test('FinanceGuard: a\'zo, global yashirin → moliya umuman yo\'q', function (): void {
    $out = (new FinanceGuard(false))->present(sample_event(), ['role' => 'member'], 300000000);
    assert_true(!array_key_exists('finance', $out), 'finance kaliti bo\'lmasligi kerak');
    assert_true(!str_contains(json_encode($out), '12500000'), 'Narx sizdi');
    assert_no_secrets($out);
});

test('FinanceGuard: a\'zo, global ochiq → narx/to\'langan/qoldiq, lekin mijoz yo\'q', function (): void {
    $out = (new FinanceGuard(true))->present(sample_event(), ['role' => 'member'], 300000000);
    assert_same(['total_price' => '12500000.00', 'paid' => '3000000.00', 'balance' => '9500000.00'], $out['finance']);
    assert_no_secrets($out);
});

test('FinanceGuard: to\'y "private" global ochiqni bekor qiladi', function (): void {
    $out = (new FinanceGuard(true))->present(sample_event(['financial_visibility' => 'private']), ['role' => 'member'], 0);
    assert_true(!array_key_exists('finance', $out));
});

test('FinanceGuard: to\'y "members" global yashirinni bekor qiladi', function (): void {
    $out = (new FinanceGuard(false))->present(sample_event(['financial_visibility' => 'members']), ['role' => 'member'], 0);
    assert_same('12500000.00', $out['finance']['total_price']);
    assert_no_secrets($out);
});

test('FinanceGuard: owner hammasini ko\'radi', function (): void {
    $out = (new FinanceGuard(false))->present(sample_event(), ['role' => 'owner'], 100);
    assert_same('12500000.00', $out['finance']['total_price']);
    assert_same('private', $out['finance']['effective_visibility']);
    assert_same('Karim aka', $out['client_name']);
    assert_same('maxfiy', $out['internal_notes']);
});

test('FinanceGuard: tasdiqlanmagan koordinata a\'zoga chiqmaydi, owner\'ga chiqadi', function (): void {
    $g = new FinanceGuard(false);
    assert_same(null, $g->present(sample_event(), ['role' => 'member'])['latitude']);
    assert_same(38.27, $g->present(sample_event(), ['role' => 'owner'])['latitude']);
    assert_same(38.27, $g->present(sample_event(['location_verified' => '1']), ['role' => 'member'])['latitude']);
});

test('FinanceGuard: ro\'yxat qatorida moliya va mijoz yo\'q (owner uchun ham)', function (): void {
    $row = (new FinanceGuard(true))->presentRow(sample_event());
    assert_true(!array_key_exists('total_price', $row));
    assert_no_secrets($row);
    assert_same('18:00', $row['start_time']);
});

test('FinanceGuard API: a\'zo tokeni bilan JSON\'da narx yo\'q, owner ko\'radi', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $member = login_member($bot);
    $res = api('POST', '/events', [
        'title' => 'Kelin to‘yi', 'event_type' => 'kelin', 'event_date' => '2030-05-10', 'start_time' => '18:00',
        'total_price' => '12 500 000', 'client_name' => 'Karim aka', 'client_phone' => '+998 90 123 45 67',
        'internal_notes' => 'maxfiy',
    ], $owner);
    assert_same(201, $res->status, $res->body());
    $id = $res->payload['data']['id'];
    api('POST', "/events/{$id}/payments", ['amount' => 3000000, 'note' => 'naqd, Karim aka'], $owner);

    $endpoints = ["/events/{$id}", '/events', '/dashboard', '/calendar?month=2030-05'];
    foreach ($endpoints as $path) {
        [$p, $q] = array_pad(explode('?', $path, 2), 2, '');
        parse_str($q, $query);
        $body = api('GET', $p, null, $member, $query)->body();
        assert_true(!str_contains($body, '12500000'), "Narx sizdi: {$path}");
        assert_true(!str_contains($body, '3000000'), "Avans sizdi: {$path}");
        assert_true(!str_contains($body, 'Karim'), "Mijoz sizdi: {$path}");
        assert_true(!str_contains($body, 'maxfiy'), "Ichki izoh sizdi: {$path}");
        assert_true(!str_contains($body, 'naqd'), "To'lov izohi sizdi: {$path}");
    }
    $ownerView = api('GET', "/events/{$id}", null, $owner)->payload['data'];
    assert_same('9500000.00', $ownerView['finance']['balance']);
    assert_same(403, api('GET', "/events/{$id}/payments", null, $member)->status);

    // Global ochiq qilinsa a'zo narxni ko'radi, lekin izoh va mijozni emas
    api('PUT', '/settings', ['finance_visible_to_members' => true], $owner);
    $body = api('GET', "/events/{$id}", null, $member)->body();
    assert_true(str_contains($body, '12500000'), 'Ochiq bo\'lganda narx ko\'rinishi kerak');
    assert_true(!str_contains($body, 'naqd') && !str_contains($body, 'Karim'), 'Izoh/mijoz sizdi');
});

test('FinanceGuard API: a\'zo yozish endpointlariga kira olmaydi', function (): void {
    $bot = fresh_db();
    $member = login_member($bot);
    $writes = [
        ['POST', '/events', []], ['PUT', '/events/1', []], ['POST', '/events/1/cancel', []],
        ['POST', '/events/1/payments', []], ['PUT', '/settings', []], ['GET', '/settings', null],
        ['GET', '/members', null], ['POST', '/members', []], ['DELETE', '/events/1/payments/1', null],
    ];
    foreach ($writes as [$m, $p, $b]) {
        $res = api($m, $p, $b, $member);
        assert_same(403, $res->status, "{$m} {$p}");
        assert_same('owner_only', $res->payload['error']['code']);
    }
});
