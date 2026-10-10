<?php

declare(strict_types=1);

use App\Core\Request;

test('Request: /api/ping → /ping (subdomen docroot)', function (): void {
    assert_same('/ping', Request::normalizePath('/api/ping', '/api/index.php'));
    assert_same('/ping', Request::normalizePath('/api/ping/?x=1', '/api/index.php'));
    assert_same('/', Request::normalizePath('/api', '/api/index.php'));
    assert_same('/ping', Request::normalizePath('/api/index.php/ping', '/api/index.php'));
});

test('Request: papka ichida (public_html/jahonoro) ham ishlaydi', function (): void {
    assert_same('/events/5', Request::normalizePath('/jahonoro/api/events/5', '/jahonoro/api/index.php'));
});

test('Request: Bearer token ajratiladi', function (): void {
    $r = new Request('GET', '/me', [], ['authorization' => 'Bearer abc123_-']);
    assert_same('abc123_-', $r->bearerToken());
    $r2 = new Request('GET', '/me', [], ['authorization' => 'Basic xyz']);
    assert_same(null, $r2->bearerToken());
});

test('Request: JSON tanasi, noto\'g\'ri JSON bo\'sh massiv', function (): void {
    $r = new Request('POST', '/x', [], [], '{"a":5}');
    assert_same(5, $r->input('a'));
    $bad = new Request('POST', '/x', [], [], '{oops');
    assert_same([], $bad->json());
});
