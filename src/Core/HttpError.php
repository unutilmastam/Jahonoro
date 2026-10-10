<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Foydalanuvchiga ko'rsatiladigan xato: HTTP status + kod + o'zbekcha matn.
 */
final class HttpError extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly ?array $details = null,
    ) {
        parent::__construct($message, $status);
    }

    public static function unauthorized(): self
    {
        return new self(401, 'unauthorized', 'Sessiya tugadi. Ilovani guruhdagi tugmadan qayta oching.');
    }

    public static function forbidden(): self
    {
        return new self(403, 'no_access', 'Sizda bu ilovaga kirish huquqi yoʻq. Guruh rahbariga murojaat qiling.');
    }

    public static function ownerOnly(): self
    {
        return new self(403, 'owner_only', 'Bu amal faqat guruh rahbari uchun.');
    }

    public static function pending(): self
    {
        return new self(403, 'pending', 'Soʻrovingiz yuborildi. Guruh rahbari tasdiqlagach ilova ochiladi.');
    }

    public static function notFound(string $message = 'Toʻy topilmadi.'): self
    {
        return new self(404, 'not_found', $message);
    }

    public static function tooMany(): self
    {
        return new self(429, 'rate_limited', 'Juda koʻp soʻrov. Bir daqiqadan keyin qayta urinib koʻring.');
    }

    public function toResponse(): Response
    {
        return Response::error($this->errorCode, $this->getMessage(), $this->status, $this->details);
    }
}
