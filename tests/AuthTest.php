<?php

declare(strict_types=1);

use App\Core\Services;
use App\Telegram\TelegramException;

test('Auth: owner birinchi kirishda yaratiladi va token oladi', function (): void {
    fresh_db();
    $token = login_owner();
    assert_same(64, strlen($token));
    $me = api('GET', '/me', null, $token);
    assert_same(200, $me->status);
    assert_same('owner', $me->payload['data']['role']);
    assert_same('Bahrullo', $me->payload['data']['name']);

    $row = Services::pdo()->query('SELECT token_hash FROM sessions')->fetch();
    assert_same(hash('sha256', $token), $row['token_hash'], 'Bazada faqat hash');
});

test('Auth: soxta initData → 401', function (): void {
    fresh_db();
    $res = api('POST', '/auth/telegram', ['init_data' => make_init_data(['id' => 1000], null, '1:WRONG')]);
    assert_same(401, $res->status);
    $res = api('POST', '/auth/telegram', []);
    assert_same(401, $res->status);
});

test('Auth: guruhda yo\'q odam → 403, bazaga yozilmaydi', function (): void {
    fresh_db();
    $res = api('POST', '/auth/telegram', ['init_data' => make_init_data(['id' => 5555, 'first_name' => 'Begona'])]);
    assert_same(403, $res->status);
    assert_same('no_access', $res->payload['error']['code']);
    assert_same(null, Services::users()->findByTelegramId(5555));
});

test('Auth: guruh a\'zosi birinchi marta → pending, owner tasdiqlagach kiradi', function (): void {
    $bot = fresh_db();
    $bot->members[3001] = 'member';
    $init = make_init_data(['id' => 3001, 'first_name' => 'Yangi']);
    $res = api('POST', '/auth/telegram', ['init_data' => $init]);
    assert_same(403, $res->status);
    assert_same('pending', $res->payload['error']['code']);
    $user = Services::users()->findByTelegramId(3001);
    assert_same('pending', $user['status']);

    Services::users()->setStatus((int) $user['id'], 'active');
    $res = api('POST', '/auth/telegram', ['init_data' => $init]);
    assert_same(200, $res->status);
    assert_same('member', $res->payload['data']['user']['role']);
});

test('Auth: guruhdan chiqarilgan a\'zo keyingi tekshiruvda bloklanadi', function (): void {
    $bot = fresh_db();
    $token = login_member($bot, 2001);
    assert_same(200, api('GET', '/me', null, $token)->status);

    $bot->members[2001] = 'kicked';
    // keshni eskirtiramiz
    Services::pdo()->exec("UPDATE users SET member_checked_at = NOW() - INTERVAL 11 MINUTE WHERE telegram_user_id = 2001");
    $res = api('GET', '/me', null, $token);
    assert_same(403, $res->status);
    assert_same('no_access', $res->payload['error']['code']);
});

test('Auth: a\'zolik 10 daqiqa keshlanadi', function (): void {
    $bot = fresh_db();
    $token = login_member($bot, 2001);
    $before = count($bot->callsOf('getChatMember'));
    api('GET', '/me', null, $token);
    api('GET', '/me', null, $token);
    assert_same($before, count($bot->callsOf('getChatMember')));
});

test('Auth: Telegram vaqtincha ishlamasa oldingi ijobiy natija amal qiladi', function (): void {
    $bot = fresh_db();
    $token = login_member($bot, 2001);
    Services::pdo()->exec("UPDATE users SET member_checked_at = NOW() - INTERVAL 30 MINUTE WHERE telegram_user_id = 2001");
    $bot->failWith = new TelegramException(0, 'network');
    assert_same(200, api('GET', '/me', null, $token)->status);
});

test('Auth: o\'chirilgan (disabled) a\'zo → 403', function (): void {
    $bot = fresh_db();
    $token = login_member($bot, 2001);
    $user = Services::users()->findByTelegramId(2001);
    Services::users()->setStatus((int) $user['id'], 'disabled');
    assert_same(403, api('GET', '/me', null, $token)->status);
});

test('Auth: token yo\'q yoki noto\'g\'ri → 401', function (): void {
    fresh_db();
    assert_same(401, api('GET', '/me')->status);
    assert_same(401, api('GET', '/me', null, str_repeat('a', 64))->status);
    assert_same(401, api('GET', '/me', null, 'short')->status);
});

test('Auth: muddati o\'tgan sessiya → 401', function (): void {
    fresh_db();
    $token = login_owner();
    Services::pdo()->exec('UPDATE sessions SET expires_at = NOW() - INTERVAL 1 MINUTE');
    assert_same(401, api('GET', '/me', null, $token)->status);
});

test('Auth: IP bo\'yicha 20/10 daqiqa chegara', function (): void {
    fresh_db();
    $bad = ['init_data' => 'x'];
    for ($i = 0; $i < 20; $i++) {
        api('POST', '/auth/telegram', $bad);
    }
    $res = api('POST', '/auth/telegram', $bad);
    assert_same(429, $res->status);
});
