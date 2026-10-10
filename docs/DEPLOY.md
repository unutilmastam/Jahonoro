# JAHONORO — hostingga joylash (hostmaster.uz, cPanel)

Hamma qadamni telefon yoki iPad brauzeridan bajarish mumkin. Terminal kerak emas.

## 0. Hostingni tekshirish

cPanel'da quyidagilar borligiga ishonch hosil qiling:

- [ ] **MultiPHP Manager** — subdomen uchun PHP 8.2 yoki yangisi. **Select PHP Version / Extensions** bo'limida `pdo_mysql`, `curl`, `openssl`, `mbstring`, `json` yoqilgan.
- [ ] **MySQL Databases** — MySQL 8 yoki MariaDB 10.3+ (phpMyAdmin bosh sahifasida versiya yozilgan).
- [ ] **Git Version Control** bo'limi bor.
- [ ] **Subdomains** — docroot'ni o'zingiz tanlay olasiz.
- [ ] **Cron Jobs** bo'limi bor.
- [ ] **SSL/TLS Status** — AutoSSL subdomenga sertifikat beradi.

## 1. GitHub repo va deploy kaliti

1. GitHub'da `unutilmastam/jahonoro` repo **private** bo'lsin.
2. cPanel → **SSH Access** → **Manage SSH Keys** → **Generate a New Key**. Nomi: `id_rsa`, parol bo'sh. Keyin **Manage** → **Authorize**.
3. **View/Download** orqali *public* kalitni (`id_rsa.pub`) nusxalang.
4. GitHub → repo → **Settings → Deploy keys → Add deploy key**. Kalitni qo'ying, "Allow write access" belgilanmasin.

## 2. Repo'ni hostingga klonlash

cPanel → **Git Version Control** → **Create**:

- Clone URL: `git@github.com:unutilmastam/jahonoro.git`
- Repository Path: `/home/USER/jahonoro` (USER — cPanel login)
- Repository Name: `jahonoro`

Kerakli branch `main`. Ish boshqa branchda bo'lsa, avval GitHub'da Pull Request ochib `main` ga birlashtiring.

## 3. Subdomen

cPanel → **Domains** (yoki **Subdomains**) → **Create**:

- Subdomen: `jahonoro.itcode.uz`
- Document Root: `jahonoro/public`

So'ng **SSL/TLS Status** → **Run AutoSSL**. Brauzerda `https://jahonoro.itcode.uz` ochilishi va qulf belgisi chiqishi kerak.

> Docroot'ni `jahonoro/public` ga qo'yib bo'lmasa: docroot `public_html/jahonoro` bo'ladi, `public/` ichidagi hamma narsani (`.htaccess` bilan birga) o'sha papkaga ko'chiring. `public/api/index.php` ichidagi `dirname(__DIR__, 2)` yo'li `src/` ga yetib borishi kerak, aks holda uni `/home/USER/jahonoro` ga o'zgartiring. `src/`, `config/`, `cron/` hech qachon `public_html` ichida bo'lmasin.

## 4. Ma'lumotlar bazasi

1. cPanel → **MySQL Databases**: baza yarating (masalan `USER_jahonoro`). Foydalanuvchi yarating, kuchli parol bering. Foydalanuvchini bazaga **ALL PRIVILEGES** bilan qo'shing.
2. cPanel → **phpMyAdmin** → chapda bazani tanlang → **Import**:
   - `database/migrations/001_init.sql`
   - keyin `database/migrations/002_settings_seed.sql`

   Fayllarni telefonga GitHub'dan yuklab oling: fayl → **Raw** → saqlash.
3. **Structure** bo'limida 8 ta jadval ko'rinishi kerak.

## 5. Telegram bot

1. Telegram'da **@BotFather** → `/newbot` → nom va username (masalan `@JahonoroBot`). Token'ni saqlang, hech kimga bermang.
2. `/newapp` → botni tanlang → nom, tavsif, rasm (640×360) → Web App URL: `https://jahonoro.itcode.uz` → short name: `jadval`. BotFather `https://t.me/JahonoroBot/jadval` ko'rinishidagi havolani beradi — bu **MINI_APP_LINK**.
3. O'zingizning Telegram ID'ingiz: **@userinfobot** ga yozing — bu **OWNER_TELEGRAM_ID**.
4. Botni guruhga hozir qo'shmang — buni 9-qadamda ilovaning o'zi qiladi.
5. Botda webhook bo'lmasligi kerak (yangi botda yo'q). Ilgari boshqa joyda ishlatilgan bo'lsa, brauzerda bir marta oching: `https://api.telegram.org/bot<TOKEN>/deleteWebhook`.

## 6. config.php

cPanel → **File Manager** → `jahonoro/config/`:

1. `config.example.php` ustida **Copy** → nomi `config.php`.
2. `config.php` → **Edit** va to'ldiring:

```php
'DB_HOST' => 'localhost',
'DB_PORT' => 3306,
'DB_NAME' => 'USER_jahonoro',
'DB_USER' => 'USER_jahonoro',
'DB_PASS' => '…',
'BOT_TOKEN' => '123456789:AA…',
'OWNER_TELEGRAM_ID' => 123456789,
'GROUP_CHAT_ID' => '',          // bo'sh qoldiring — guruh ilovadan ulanadi
'APP_URL' => 'https://jahonoro.itcode.uz',
'MINI_APP_LINK' => 'https://t.me/JahonoroBot/jadval',
'TIMEZONE' => 'Asia/Tashkent',
```

3. `config.php` → **Permissions** → `600`.

`config.php` `.gitignore` da. "Update from Remote" qilganda u o'chmaydi va GitHub'ga chiqmaydi.

## 7. Hosting sinovi

Brauzerda: `https://jahonoro.itcode.uz/api/ping`

```json
{"ok":true,"data":{"php_version":"8.2.x","config":"ok","db":"ok","telegram_api":"ok","time":"…"}}
```

| Javob | Sabab | Nima qilish |
| --- | --- | --- |
| `config: missing` | `config/config.php` yo'q | 6-qadam |
| `db: fail` | DB nomi/parol noto'g'ri yoki foydalanuvchi bazaga qo'shilmagan | 4-qadam |
| `telegram_api: fail` | Token noto'g'ri yoki hosting `api.telegram.org` ga chiqishni bloklagan | Avval token'ni tekshiring. To'g'ri bo'lsa — hostmaster.uz qo'llab-quvvatlashiga yozing: "api.telegram.org:443 ga chiquvchi HTTPS ulanishni oching" |
| 404 yoki HTML sahifa | Docroot yoki `.htaccess` ishlamayapti | 3-qadam; `public/.htaccess` joyida ekanini tekshiring |

Xato tafsiloti: File Manager → `jahonoro/storage/logs/app-YYYY-MM-DD.log` (token va parollarsiz).

## 8. Cron (eslatmalar)

cPanel → **Cron Jobs** → **Add New Cron Job**:

- Common Settings: **Once Per Five Minutes** (`*/5 * * * *`)
- Command:

```
/opt/cpanel/ea-php82/root/usr/bin/php /home/USER/jahonoro/cron/reminders.php >/dev/null 2>&1
```

PHP yo'li hostingda boshqacha bo'lishi mumkin. MultiPHP Manager'da tanlangan versiyaga qarang (`ea-php83` va hokazo). 10 daqiqadan keyin ilovada Sozlamalar → Tizim holati → "Cron" qatori **Ishlayapti** bo'lishi kerak.

## 9. Birinchi kirish

1. Telegram'da `MINI_APP_LINK` havolasini oching. Siz owner bo'lib kirasiz (ID config'dagi bilan bir xil). Bosh sahifada "Guruh hali ulanmagan" qatori ko'rinadi.
2. Sozlamalar → Guruh → **Guruhni ulash** → **Telegram’da guruhni tanlash**. Telegram guruh tanlash oynasini ochadi: yopiq JAHONORO guruhini tanlang, botni admin qilib qo'shishni tasdiqlang (faqat "Xabarlarni pin qilish" huquqi).
3. Ilovaga qayting — bir necha soniyada "“JAHONORO” ulandi" chiqadi, bot guruhga "📅 TO‘YLAR JADVALI" tugmali xabar yuborib, uni pin qiladi. Kod 30 daqiqa amal qiladi; ulguring olmasangiz tugmani qayta bosing.
4. A'zolar shu tugmani bosadi. Birinchi marta ochganda ular "So‘rovingiz yuborildi" xabarini ko'radi. Sozlamalar → A'zolar ro'yxatida oltin nuqta bilan chiqadi, siz **Tasdiqlash** ni bosasiz.

## 10. Yakuniy sinov

1. Ertangi sanaga test to'y qo'shing. 5–10 daqiqada guruhga **bitta** eslatma kelishi kerak. Xabarda narx va mijoz bo'lmasligi kerak.
2. Bitta a'zo bilan oching: narx yo'q, "+" tugma va tahrir menyusi yo'q.
3. Test to'yni oching → "…" → **Arxivlash**.

To'liq sinov ro'yxati: `docs/TESTING.md`.

## Yangilash

GitHub'da `main` ga push → cPanel → **Git Version Control** → **Manage** → **Pull or Deploy** → **Update from Remote**.

Agar yangi `database/migrations/00X_*.sql` fayl qo'shilgan bo'lsa, uni phpMyAdmin'da qo'lda import qiling.

## Xavfsizlik eslatmalari

- Bot token va DB parol faqat `config/config.php` da. Token oshkor bo'lsa: @BotFather → `/revoke` → yangi token'ni config'ga yozing.
- `src/`, `config/`, `cron/`, `storage/` veb orqali ochilmaydi, chunki docroot faqat `public/`.
- Ilova faqat Telegram ichida ishlaydi. Brauzerda ochilsa "Bu ilova faqat JAHONORO guruhidagi tugma orqali ochiladi" chiqadi.
