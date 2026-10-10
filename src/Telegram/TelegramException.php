<?php

declare(strict_types=1);

namespace App\Telegram;

use RuntimeException;

/**
 * Bot API xatosi. errorCode = 0 — tarmoq xatosi. Matnda token bo'lmaydi.
 */
final class TelegramException extends RuntimeException
{
    public function __construct(
        public readonly int $errorCode,
        public readonly string $description,
        public readonly ?int $retryAfter = null,
        public readonly ?int $migrateToChatId = null,
    ) {
        parent::__construct($description, $errorCode);
    }

    /** 429, 5xx va tarmoq xatolarida keyinroq qayta urinish mumkin. */
    public function isRetryable(): bool
    {
        return $this->errorCode === 0 || $this->errorCode === 429 || $this->errorCode >= 500;
    }
}
