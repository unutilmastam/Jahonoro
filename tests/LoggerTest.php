<?php

declare(strict_types=1);

use App\Core\Logger;

test('Logger: bot token yashiriladi', function (): void {
    $out = Logger::redact('url https://api.telegram.org/bot123456789:AAHdqTcvCH1vGWJxfSeofSAs0K5PALDsaw/getMe');
    assert_true(!str_contains($out, 'AAHdqTcv'), 'Token log matnida qoldi');
    assert_true(str_contains($out, '[bot-token]'));
});

test('Logger: Bearer va telefon yashiriladi, sana qoladi', function (): void {
    $out = Logger::redact('Bearer abcdef.123 tel +998 90 123 45 67 sana 2026-10-18 18:00');
    assert_true(!str_contains($out, 'abcdef'), 'Bearer token qoldi');
    assert_true(!str_contains($out, '123 45 67'), 'Telefon qoldi');
    assert_true(str_contains($out, '2026-10-18 18:00'), 'Sana buzildi');
});
