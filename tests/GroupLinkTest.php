<?php

declare(strict_types=1);

use App\Core\Services;

function start_update(int $id, string $chatId, int $fromId, string $text, string $type = 'supergroup'): array
{
    return ['update_id' => $id, 'message' => [
        'message_id' => $id, 'from' => ['id' => $fromId], 'text' => $text,
        'chat' => ['id' => (int) $chatId, 'type' => $type, 'title' => 'JAHONORO'],
    ]];
}

function added_update(int $id, string $chatId, int $fromId, string $title = 'Begona guruh'): array
{
    return ['update_id' => $id, 'my_chat_member' => [
        'from' => ['id' => $fromId],
        'chat' => ['id' => (int) $chatId, 'type' => 'supergroup', 'title' => $title],
        'old_chat_member' => ['status' => 'left'],
        'new_chat_member' => ['status' => 'administrator'],
    ]];
}

function link_code(): string
{
    return (string) Services::settings()->get('group_link_code');
}


test('GroupLink: havola startgroup + admin=pin_messages, kod bir martalik', function (): void {
    fresh_db();
    $owner = login_owner();
    $res = api('POST', '/group/link', null, $owner);
    assert_same(200, $res->status, $res->body());
    $code = link_code();
    assert_same(16, strlen($code));
    assert_same('https://t.me/JahonoroTestBot?startgroup=' . $code . '&admin=pin_messages', $res->payload['data']['url']);
});

test('GroupLink: owner "/start kod" yuborgan guruh ulanadi, tugma yuboriladi va pin qilinadi', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    api('POST', '/group/link', null, $owner);
    $bot->updates = [start_update(10, '-1009999', 1000, '/start@JahonoroTestBot ' . link_code())];
    $res = api('POST', '/group/link/check', null, $owner);
    assert_same(true, $res->payload['data']['just_linked'], $res->body());
    assert_same('JAHONORO', $res->payload['data']['title']);
    assert_same('-1009999', Services::settings()->get('group_chat_id'));
    assert_same('11', Services::settings()->get('tg_update_offset'));
    assert_same('-1009999', (string) $bot->callsOf('sendMessage')[0]['params']['chat_id']);
    assert_same(1, count($bot->callsOf('pinChatMessage')));
    assert_same(false, Services::groupLinker()->status()['pending'], 'Kod ishlatilgandan keyin bekor');
});

test('GroupLink: botni qo\'shish hodisasi (my_chat_member) ham yetarli', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    api('POST', '/group/link', null, $owner);
    $bot->updates = [added_update(5, '-1007777', 1000, 'Guruhimiz')];
    $res = api('POST', '/group/link/check', null, $owner);
    assert_same(true, $res->payload['data']['just_linked']);
    assert_same('-1007777', Services::settings()->get('group_chat_id'));
});

test('GroupLink: begona odam kod bilan yoki kodsiz ulay olmaydi; bot begona guruhdan chiqadi', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    api('POST', '/group/link', null, $owner);
    $bot->updates = [
        start_update(1, '-1005555', 4444, '/start ' . link_code()),
        added_update(2, '-1005555', 4444),
    ];
    $res = api('POST', '/group/link/check', null, $owner);
    assert_same(false, $res->payload['data']['just_linked']);
    assert_same(null, Services::settings()->get('group_chat_id'));
    $leave = $bot->callsOf('leaveChat');
    assert_same(1, count($leave));
    assert_same('-1005555', (string) $leave[0]['params']['chat_id']);
});

test('GroupLink: noto\'g\'ri yoki muddati o\'tgan kod qabul qilinmaydi', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    api('POST', '/group/link', null, $owner);
    $bot->updates = [start_update(1, '-1001111', 1000, '/start notogri')];
    assert_same(false, api('POST', '/group/link/check', null, $owner)->payload['data']['just_linked']);

    $code = link_code();
    Services::settings()->set('group_link_expires', (string) (time() - 1));
    $bot->updates = [start_update(2, '-1001111', 1000, '/start ' . $code)];
    assert_same(false, api('POST', '/group/link/check', null, $owner)->payload['data']['just_linked']);
});

test('GroupLink: guruh almashsa eski guruh a\'zolari kira olmaydi, yangi guruh a\'zolari kiradi', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    $member = login_member($bot, 2001);
    assert_same(200, api('GET', '/me', null, $member)->status);

    api('POST', '/group/link', null, $owner);
    $bot->updates = [start_update(1, '-1002222', 1000, '/start ' . link_code())];
    api('POST', '/group/link/check', null, $owner);
    Services::reset();
    Services::set('pdo', App\Core\Db::pdo());
    Services::set('bot', $bot);

    // FakeBotApi a'zolikni chat_id'ga qaramay qaytaradi — yangi guruhda yo'q deb belgilaymiz
    unset($bot->members[2001]);
    assert_same(403, api('GET', '/me', null, $member)->status);
    $calls = $bot->callsOf('getChatMember');
    assert_same('-1002222', (string) end($calls)['params']['chat_id'], 'Yangi guruh bo\'yicha tekshiriladi');

    $bot->members[2001] = 'member';
    Services::pdo()->exec('UPDATE users SET member_checked_at = NULL');
    assert_same(200, api('GET', '/me', null, $member)->status);
});

test('GroupLink: supergroup\'ga aylanish (migrate_to_chat_id) ID\'ni yangilaydi', function (): void {
    $bot = fresh_db();
    $owner = login_owner();
    Services::settings()->set('group_chat_id', '-3333');
    $bot->updates = [['update_id' => 1, 'message' => [
        'message_id' => 1, 'from' => ['id' => 1], 'migrate_to_chat_id' => -1003333,
        'chat' => ['id' => -3333, 'type' => 'group', 'title' => 'JAHONORO'],
    ]]];
    api('POST', '/group/link/check', null, $owner);
    assert_same('-1003333', Services::settings()->get('group_chat_id'));
});

test('GroupLink: a\'zo bu endpointlarga kira olmaydi', function (): void {
    $bot = fresh_db();
    $member = login_member($bot);
    foreach ([['GET', '/group'], ['POST', '/group/link'], ['POST', '/group/link/check']] as [$m, $p]) {
        assert_same(403, api($m, $p, null, $member)->status, "{$m} {$p}");
    }
});
