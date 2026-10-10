<?php

declare(strict_types=1);

use App\Telegram\MessageBuilder;

test('MessageBuilder: o\'zbekcha sana', function (): void {
    assert_same('18-oktabr, yakshanba', MessageBuilder::dateUz('2026-10-18', 2026));
    assert_same('2-yanvar 2027, shanba', MessageBuilder::dateUz('2027-01-02', 2026));
});

test('MessageBuilder: xabarda moliya va mijoz yo\'q, bo\'sh qatorlar tushadi', function (): void {
    $b = new MessageBuilder('https://t.me/JahonoroBot/jadval');
    $event = sample_event(['event_date' => '2026-10-18', 'nahor_time' => null, 'dress_code' => '']);
    $msg = $b->reminder($event, '2026-10-17');
    $t = $msg['text'];
    assert_true(str_starts_with($t, '🎵 TO‘Y ERTAGA!'), $t);
    assert_true(str_contains($t, '📅 Sana: 18-oktabr, yakshanba'));
    assert_true(str_contains($t, '🌙 Kech: 18:00'), $t);
    assert_true(str_contains($t, '🍽 Abet: 13:00'));
    assert_true(str_contains($t, '📍 Manzilda bo‘lish: 16:30'));
    assert_true(str_contains($t, '🏠 To‘y egasining uyi: Denov, Bog‘ishamol 5'));
    assert_true(str_contains($t, '💍 Turi: Kelin to‘yi'));
    assert_true(!str_contains($t, 'Nahor'), 'Bo\'sh Nahor qatori tushishi kerak');
    foreach (['12500000', '12 500 000', 'Karim', '123 45 67', 'maxfiy', 'Avans', 'Narx'] as $bad) {
        assert_true(!str_contains($t, $bad), "Xabarda '{$bad}' bor");
    }
    assert_same('🗺 To‘yxona', $msg['reply_markup']['inline_keyboard'][0][0]['text']);
    assert_same('🏠 Uy', $msg['reply_markup']['inline_keyboard'][0][1]['text']);
    assert_same('https://t.me/JahonoroBot/jadval?startapp=e5', $msg['reply_markup']['inline_keyboard'][1][0]['url']);
});

test('MessageBuilder: HTML escape', function (): void {
    $b = new MessageBuilder('https://t.me/JahonoroBot/jadval');
    $msg = $b->reminder(sample_event(['venue_name' => '<b>Sohil</b> & Co']), '2026-10-18');
    assert_true(str_contains($msg['text'], '&lt;b&gt;Sohil&lt;/b&gt; &amp; Co'));
    assert_true(str_starts_with($msg['text'], '🎵 TO‘Y BUGUN!'));
});

test('MessageBuilder: xarita — tasdiqlangan nuqta yoki manzil matni', function (): void {
    assert_same(
        'https://www.google.com/maps/search/?api=1&query=38.270000%2C67.890000',
        MessageBuilder::mapUrl(sample_event(['location_verified' => '1']))
    );
    assert_true(str_contains((string) MessageBuilder::mapUrl(sample_event()), 'query=Sohil%2C%20Denov'));
    assert_same(null, MessageBuilder::mapUrl(['venue_name' => '', 'venue_address' => '', 'district' => '', 'latitude' => null, 'longitude' => null]));
});

test('MessageBuilder: MINI_APP_LINK noto\'g\'ri bo\'lsa tugma qo\'shilmaydi', function (): void {
    $b = new MessageBuilder('javascript:alert(1)');
    $msg = $b->reminder(sample_event(['home_address' => '']), '2026-10-18');
    assert_same(1, count($msg['reply_markup']['inline_keyboard']));
    assert_same(1, count($msg['reply_markup']['inline_keyboard'][0]));
});
