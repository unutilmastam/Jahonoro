<?php

declare(strict_types=1);

/*
 * Test yordamchilari: soxta Telegram, toza baza, initData yasash, API chaqirish.
 */

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use App\Core\Services;
use App\Telegram\BotApi;
use App\Telegram\TelegramException;

final class FakeBotApi extends BotApi
{
    /** @var list<array{method: string, params: array}> */
    public array $calls = [];

    /** @var array<int, string> telegram_user_id → getChatMember status */
    public array $members = [];

    public ?TelegramException $failWith = null;

    private int $nextMessageId = 500;

    public function __construct()
    {
        parent::__construct('fake');
    }

    public function call(string $method, array $params = []): mixed
    {
        $this->calls[] = ['method' => $method, 'params' => $params];
        if ($this->failWith !== null) {
            throw $this->failWith;
        }
        return match ($method) {
            'getMe' => ['id' => 1, 'is_bot' => true, 'username' => 'JahonoroTestBot'],
            'getChatMember' => isset($this->members[$params['user_id']])
                ? ['status' => $this->members[$params['user_id']], 'user' => ['id' => $params['user_id']]]
                : throw new TelegramException(400, 'Bad Request: user not found'),
            'sendMessage' => ['message_id' => $this->nextMessageId++, 'chat' => ['id' => $params['chat_id']]],
            default => true,
        };
    }

    /** @return list<array{method: string, params: array}> */
    public function callsOf(string $method): array
    {
        return array_values(array_filter($this->calls, fn (array $c): bool => $c['method'] === $method));
    }
}

final class SkipTest extends RuntimeException
{
}

function db_available(): bool
{
    static $ok = null;
    if ($ok === null) {
        try {
            \App\Core\Db::pdo();
            $ok = true;
        } catch (Throwable $e) {
            fwrite(STDERR, 'DB: ' . $e->getMessage() . "\n");
            $ok = false;
        }
    }
    return $ok;
}

/**
 * Bazani tozalab, migratsiyalarni qayta yuklaydi va servislarni yangilaydi.
 */
function fresh_db(): FakeBotApi
{
    if (!db_available()) {
        throw new SkipTest('MySQL mavjud emas');
    }
    $pdo = \App\Core\Db::pdo();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
        $pdo->exec('DROP TABLE `' . str_replace('`', '', (string) $table) . '`');
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    foreach (glob(APP_ROOT . '/database/migrations/*.sql') ?: [] as $file) {
        foreach (split_sql((string) file_get_contents($file)) as $statement) {
            $pdo->exec($statement);
        }
    }

    Services::reset();
    Services::set('pdo', $pdo);
    $bot = new FakeBotApi();
    Services::set('bot', $bot);
    return $bot;
}

/** @return list<string> */
function split_sql(string $sql): array
{
    $sql = (string) preg_replace('/^\s*--.*$/m', '', $sql);
    return array_values(array_filter(array_map('trim', explode(';', $sql)), fn (string $s): bool => $s !== ''));
}

/**
 * Bot token bilan to'g'ri imzolangan initData.
 *
 * @param array<string, mixed> $user
 */
function make_init_data(array $user, ?int $authDate = null, ?string $token = null): string
{
    $token ??= (string) config('BOT_TOKEN');
    $fields = [
        'auth_date' => (string) ($authDate ?? time()),
        'query_id' => 'AAHtest',
        'user' => json_encode($user, JSON_UNESCAPED_UNICODE),
    ];
    ksort($fields);
    $lines = [];
    foreach ($fields as $k => $v) {
        $lines[] = $k . '=' . $v;
    }
    $secret = hash_hmac('sha256', $token, 'WebAppData', true);
    $fields['hash'] = hash_hmac('sha256', implode("\n", $lines), $secret);
    return http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
}

/**
 * API'ni HTTP'siz chaqiradi.
 *
 * @param array<string, mixed>|null $body
 * @param array<string, mixed>      $query
 */
function api(string $method, string $path, ?array $body = null, ?string $token = null, array $query = []): Response
{
    $headers = ['content-type' => 'application/json'];
    if ($token !== null) {
        $headers['authorization'] = 'Bearer ' . $token;
    }
    return App::handle(new Request(
        strtoupper($method),
        $path,
        $query,
        $headers,
        $body !== null ? (string) json_encode($body) : '',
        '127.0.0.1'
    ));
}

/** Owner sifatida kirib token qaytaradi. */
function login_owner(): string
{
    $res = api('POST', '/auth/telegram', ['init_data' => make_init_data(['id' => 1000, 'first_name' => 'Bahrullo'])]);
    assert_same(200, $res->status, 'Owner login: ' . $res->body());
    return (string) $res->payload['data']['token'];
}

/** Faol a'zo yaratib, token qaytaradi. */
function login_member(FakeBotApi $bot, int $telegramId = 2001, string $name = 'Aziz'): string
{
    $bot->members[$telegramId] = 'member';
    $users = Services::users();
    $user = $users->findByTelegramId($telegramId) ?? $users->create($telegramId, $name, null, 'member', 'active');
    $users->setStatus((int) $user['id'], 'active');
    $res = api('POST', '/auth/telegram', ['init_data' => make_init_data(['id' => $telegramId, 'first_name' => $name])]);
    assert_same(200, $res->status, 'Member login: ' . $res->body());
    return (string) $res->payload['data']['token'];
}
