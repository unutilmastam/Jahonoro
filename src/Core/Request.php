<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Kiruvchi HTTP so'rov. Yo'l /api prefiksisiz saqlanadi: /api/ping → /ping.
 */
final class Request
{
    private ?array $jsonCache = null;

    /** @var array<string, string> */
    private array $params = [];

    /**
     * @param array<string, mixed>  $query
     * @param array<string, string> $headers kalitlar kichik harfda
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $headers = [],
        public readonly string $body = '',
        public readonly string $ip = '',
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '/api/index.php');

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (is_string($key) && str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = (string) $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }
        // cPanel/CGI: Authorization ba'zan faqat REDIRECT_ orqali keladi
        if (!isset($headers['authorization']) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        $body = (string) file_get_contents('php://input');

        return new self(
            $method,
            self::normalizePath($uri, $script),
            $_GET,
            $headers,
            $body,
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        );
    }

    /**
     * REQUEST_URI dan API ichki yo'lini ajratadi.
     * SCRIPT_NAME = /api/index.php yoki /jahonoro/api/index.php bo'lishi mumkin.
     */
    public static function normalizePath(string $uri, string $scriptName): string
    {
        $path = (string) (parse_url($uri, PHP_URL_PATH) ?? '/');
        $path = rawurldecode($path);

        $apiBase = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        $candidates = [$apiBase . '/index.php', $apiBase];
        foreach ($candidates as $prefix) {
            if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'))) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        $path = '/' . trim($path, '/');
        return (string) preg_replace('#/+#', '/', $path);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function bearerToken(): ?string
    {
        $value = $this->header('authorization');
        if ($value !== null && preg_match('/^Bearer\s+([A-Za-z0-9._~+\/=-]+)$/i', trim($value), $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * JSON tanasi massiv sifatida. Noto'g'ri JSON → bo'sh massiv.
     *
     * @return array<string, mixed>
     */
    public function json(): array
    {
        if ($this->jsonCache === null) {
            $data = $this->body !== '' ? json_decode($this->body, true) : null;
            $this->jsonCache = is_array($data) ? $data : [];
        }
        return $this->jsonCache;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $data = $this->json();
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->query) ? $this->query[$key] : $default;
    }

    /** @param array<string, string> $params */
    public function withParams(array $params): self
    {
        $clone = clone $this;
        $clone->params = $params;
        return $clone;
    }

    public function param(string $name): ?string
    {
        return $this->params[$name] ?? null;
    }
}
