<?php

declare(strict_types=1);

namespace App\Core;

/**
 * JSON javob: { "ok": true, "data": ... } yoki
 * { "ok": false, "error": { "code": "...", "message": "..." } }.
 */
final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status,
        public readonly array $payload,
        public readonly array $headers = [],
    ) {
    }

    public static function ok(mixed $data = null, int $status = 200): self
    {
        return new self($status, ['ok' => true, 'data' => $data]);
    }

    public static function error(string $code, string $message, int $status = 400, ?array $details = null): self
    {
        $error = ['code' => $code, 'message' => $message];
        if ($details !== null) {
            $error['details'] = $details;
        }
        return new self($status, ['ok' => false, 'error' => $error]);
    }

    public function body(): string
    {
        return (string) json_encode(
            $this->payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }

    public function send(): void
    {
        if (!headers_sent()) {
            header_remove('X-Powered-By');
            http_response_code($this->status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: no-referrer');
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }
        echo $this->body();
    }
}
