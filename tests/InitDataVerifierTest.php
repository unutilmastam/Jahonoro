<?php

declare(strict_types=1);

use App\Auth\InitDataVerifier;
use App\Core\HttpError;

function expect_http_error(callable $fn, int $status, ?string $code = null): void
{
    try {
        $fn();
    } catch (HttpError $e) {
        assert_same($status, $e->status, 'HTTP status');
        if ($code !== null) {
            assert_same($code, $e->errorCode, 'Xato kodi');
        }
        return;
    }
    throw new RuntimeException("HttpError {$status} kutilgan edi, lekin xato bo'lmadi");
}

test('InitData: to\'g\'ri imzo qabul qilinadi', function (): void {
    $v = new InitDataVerifier((string) config('BOT_TOKEN'));
    $res = $v->verify(make_init_data(['id' => 42, 'first_name' => 'Ali', 'last_name' => 'Valiyev']));
    assert_same(42, $res['user']['id']);
    assert_same('Ali', $res['user']['first_name']);
});

test('InitData: o\'zbekcha va maxsus belgilar (‘, &, =, +) bilan ishlaydi', function (): void {
    $v = new InitDataVerifier((string) config('BOT_TOKEN'));
    $res = $v->verify(make_init_data(['id' => 7, 'first_name' => 'G‘ayrat & O‘lmas = 1+1']));
    assert_same('G‘ayrat & O‘lmas = 1+1', $res['user']['first_name']);
});

test('InitData: boshqa token bilan imzolangan → 401', function (): void {
    $v = new InitDataVerifier((string) config('BOT_TOKEN'));
    $fake = make_init_data(['id' => 42], null, '999:OTHER_TOKEN');
    expect_http_error(fn () => $v->verify($fake), 401, 'invalid_init_data');
});

test('InitData: o\'zgartirilgan user ID → 401', function (): void {
    $v = new InitDataVerifier((string) config('BOT_TOKEN'));
    $data = make_init_data(['id' => 42, 'first_name' => 'Ali']);
    $tampered = str_replace(rawurlencode('"id":42'), rawurlencode('"id":1000'), $data);
    assert_true($tampered !== $data, 'Almashtirish ishlamadi');
    expect_http_error(fn () => $v->verify($tampered), 401, 'invalid_init_data');
});

test('InitData: hash yo\'q yoki bo\'sh → 401', function (): void {
    $v = new InitDataVerifier((string) config('BOT_TOKEN'));
    expect_http_error(fn () => $v->verify('auth_date=1&user=%7B%22id%22%3A1%7D'), 401);
    expect_http_error(fn () => $v->verify(''), 401);
    expect_http_error(fn () => $v->verify('garbage'), 401);
});

test('InitData: 1 soatdan eski → 401 init_data_expired', function (): void {
    $v = new InitDataVerifier((string) config('BOT_TOKEN'));
    $old = make_init_data(['id' => 42], time() - 3700);
    expect_http_error(fn () => $v->verify($old), 401, 'init_data_expired');
    $fresh = make_init_data(['id' => 42], time() - 3500);
    assert_same(42, $v->verify($fresh)['user']['id']);
});

test('InitData: bot token bo\'sh bo\'lsa hech narsa qabul qilinmaydi', function (): void {
    $v = new InitDataVerifier('');
    expect_http_error(fn () => $v->verify(make_init_data(['id' => 42], null, '')), 401);
});
