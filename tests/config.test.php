<?php

declare(strict_types=1);

/*
 * Testlar uchun config. DB qiymatlari muhit o'zgaruvchilaridan:
 * TEST_DB_HOST, TEST_DB_NAME, TEST_DB_USER, TEST_DB_PASS. Bu ma'lumotlar maxfiy emas.
 */

return [
    'DB_HOST' => getenv('TEST_DB_HOST') ?: '127.0.0.1',
    'DB_PORT' => 3306,
    'DB_NAME' => getenv('TEST_DB_NAME') ?: 'jahonoro_test',
    'DB_USER' => getenv('TEST_DB_USER') ?: 'jt',
    'DB_PASS' => getenv('TEST_DB_PASS') ?: 'jt',
    'BOT_TOKEN' => '123456789:TEST_TOKEN_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
    'OWNER_TELEGRAM_ID' => 1000,
    'GROUP_CHAT_ID' => '-1001234567890',
    'APP_URL' => 'https://jahonoro.test',
    'MINI_APP_LINK' => 'https://t.me/JahonoroTestBot/jadval',
    'TIMEZONE' => 'Asia/Tashkent',
];
