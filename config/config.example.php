<?php

declare(strict_types=1);

/*
 * JAHONORO sozlamalari namunasi.
 *
 * Serverda shu faylni config/config.php nomi bilan nusxalang, qiymatlarni
 * to'ldiring va ruxsatni 600 qiling. config.php .gitignore'da — hech qachon
 * commit qilinmaydi.
 */

return [
    // MySQL / MariaDB
    'DB_HOST' => 'localhost',
    'DB_PORT' => 3306,
    'DB_NAME' => 'cpaneluser_jahonoro',
    'DB_USER' => 'cpaneluser_jahonoro',
    'DB_PASS' => '',

    // Telegram bot (BotFather /newbot). Faqat serverda ishlatiladi.
    'BOT_TOKEN' => '',

    // Rahbarning Telegram ID raqami (owner)
    'OWNER_TELEGRAM_ID' => 0,

    // Guruh ID'si, odatda -100 bilan boshlanadi (getUpdates → chat.id)
    'GROUP_CHAT_ID' => '',

    // Ilova manzili (subdomen), oxirida / yo'q
    'APP_URL' => 'https://jahonoro.example.uz',

    // BotFather /newapp Direct Link, masalan https://t.me/JahonoroBot/jadval
    'MINI_APP_LINK' => '',

    'TIMEZONE' => 'Asia/Tashkent',
];
