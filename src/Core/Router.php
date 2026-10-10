<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Oddiy marshrutlagich. Namuna: /events/{id}. {id}, {payment_id} — faqat musbat raqam.
 */
final class Router
{
    /** @var list<array{method: string, regex: string, handler: callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->add('PUT', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    public function add(string $method, string $pattern, callable $handler): void
    {
        $regex = preg_replace_callback(
            '/\{([a-z_]+)\}/',
            static fn (array $m): string => '(?P<' . $m[1] . '>' . (str_ends_with($m[1], 'id') ? '[1-9][0-9]{0,18}' : '[^/]+') . ')',
            str_replace('.', '\.', $pattern)
        );
        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }
            if ($route['method'] !== $request->method) {
                $allowed[] = $route['method'];
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $result = ($route['handler'])($request->withParams($params));
            return $result instanceof Response ? $result : Response::ok($result);
        }

        if ($allowed !== []) {
            return new Response(
                405,
                ['ok' => false, 'error' => ['code' => 'method_not_allowed', 'message' => 'Bu amal qoʻllab-quvvatlanmaydi.']],
                ['Allow' => implode(', ', array_unique($allowed))]
            );
        }
        return Response::error('not_found', 'Soʻralgan manzil topilmadi.', 404);
    }
}
