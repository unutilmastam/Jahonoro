<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

function make_request(string $method, string $path): Request
{
    return new Request($method, $path);
}

test('Router: GET /ping topiladi', function (): void {
    $router = new Router();
    $router->get('/ping', fn (Request $r): Response => Response::ok('pong'));
    $res = $router->dispatch(make_request('GET', '/ping'));
    assert_same(200, $res->status);
    assert_same(['ok' => true, 'data' => 'pong'], $res->payload);
});

test('Router: noma\'lum yo\'l 404', function (): void {
    $router = new Router();
    $router->get('/ping', fn (): Response => Response::ok());
    $res = $router->dispatch(make_request('GET', '/nope'));
    assert_same(404, $res->status);
    assert_same('not_found', $res->payload['error']['code']);
});

test('Router: noto\'g\'ri metod 405 + Allow', function (): void {
    $router = new Router();
    $router->get('/ping', fn (): Response => Response::ok());
    $res = $router->dispatch(make_request('POST', '/ping'));
    assert_same(405, $res->status);
    assert_same('GET', $res->headers['Allow']);
});

test('Router: {id} parametri faqat musbat raqam', function (): void {
    $router = new Router();
    $router->get('/events/{id}', fn (Request $r): Response => Response::ok($r->param('id')));
    assert_same('42', $router->dispatch(make_request('GET', '/events/42'))->payload['data']);
    assert_same(404, $router->dispatch(make_request('GET', '/events/abc'))->status);
    assert_same(404, $router->dispatch(make_request('GET', '/events/0'))->status);
    assert_same(404, $router->dispatch(make_request('GET', '/events/-1'))->status);
});

test('Router: handler massiv qaytarsa ok bilan o\'raladi', function (): void {
    $router = new Router();
    $router->get('/x', fn (): array => ['a' => 1]);
    assert_same(['ok' => true, 'data' => ['a' => 1]], $router->dispatch(make_request('GET', '/x'))->payload);
});
