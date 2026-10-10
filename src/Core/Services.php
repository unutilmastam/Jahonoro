<?php

declare(strict_types=1);

namespace App\Core;

use App\Auth\InitDataVerifier;
use App\Auth\MembershipService;
use App\Auth\SessionService;
use App\Repositories\AuditRepo;
use App\Repositories\EventRepo;
use App\Repositories\PaymentRepo;
use App\Repositories\ReminderRepo;
use App\Repositories\SettingsRepo;
use App\Repositories\UserRepo;
use App\Services\ConflictChecker;
use App\Services\EventService;
use App\Services\FinanceGuard;
use App\Services\ReminderScheduler;
use App\Telegram\MessageBuilder;
use App\Telegram\BotApi;
use PDO;

/**
 * Oddiy servis konteyneri: har servis bir marta, so'ralganda yaratiladi.
 * Testlar set() bilan soxta servis beradi.
 */
final class Services
{
    /** @var array<string, object> */
    private static array $instances = [];

    public static function set(string $name, object $instance): void
    {
        self::$instances[$name] = $instance;
    }

    public static function reset(): void
    {
        self::$instances = [];
    }

    /**
     * @template T of object
     * @param callable(): T $factory
     * @return T
     */
    private static function get(string $name, callable $factory): object
    {
        return self::$instances[$name] ??= $factory();
    }

    public static function pdo(): PDO
    {
        return self::get('pdo', static fn (): PDO => Db::pdo());
    }

    public static function bot(): BotApi
    {
        return self::get('bot', static fn (): BotApi => new BotApi((string) config('BOT_TOKEN', '')));
    }

    public static function rateLimiter(): RateLimiter
    {
        return self::get('rateLimiter', static fn (): RateLimiter => new RateLimiter(self::pdo()));
    }

    public static function initData(): InitDataVerifier
    {
        return self::get('initData', static fn (): InitDataVerifier => new InitDataVerifier((string) config('BOT_TOKEN', '')));
    }

    public static function sessions(): SessionService
    {
        return self::get('sessions', static fn (): SessionService => new SessionService(self::pdo()));
    }

    public static function membership(): MembershipService
    {
        return self::get('membership', static fn (): MembershipService => new MembershipService(
            self::bot(),
            self::users(),
            (string) config('GROUP_CHAT_ID', '')
        ));
    }

    public static function users(): UserRepo
    {
        return self::get('users', static fn (): UserRepo => new UserRepo(self::pdo()));
    }

    public static function audit(): AuditRepo
    {
        return self::get('audit', static fn (): AuditRepo => new AuditRepo(self::pdo()));
    }

    public static function events(): EventRepo
    {
        return self::get('events', static fn (): EventRepo => new EventRepo(self::pdo()));
    }

    public static function payments(): PaymentRepo
    {
        return self::get('payments', static fn (): PaymentRepo => new PaymentRepo(self::pdo()));
    }

    public static function settings(): SettingsRepo
    {
        return self::get('settings', static fn (): SettingsRepo => new SettingsRepo(self::pdo()));
    }

    public static function eventService(): EventService
    {
        return self::get('eventService', static fn (): EventService => new EventService(
            self::pdo(),
            self::events(),
            new ConflictChecker(self::events()),
            self::audit(),
            self::reminders(),
        ));
    }

    /** Har chaqiruvda yangi: global moliya sozlamasi o'zgargan bo'lishi mumkin. */
    public static function financeGuard(): FinanceGuard
    {
        return new FinanceGuard(self::settings()->financeVisibleToMembers());
    }

    public static function reminderRepo(): ReminderRepo
    {
        return self::get('reminderRepo', static fn (): ReminderRepo => new ReminderRepo(self::pdo()));
    }

    public static function messages(): MessageBuilder
    {
        return self::get('messages', static fn (): MessageBuilder => new MessageBuilder((string) config('MINI_APP_LINK', '')));
    }

    public static function reminders(): ReminderScheduler
    {
        return self::get('reminders', static fn (): ReminderScheduler => new ReminderScheduler(
            self::reminderRepo(),
            self::events(),
            self::settings(),
            self::bot(),
            self::messages(),
            (string) config('GROUP_CHAT_ID', '')
        ));
    }
}
