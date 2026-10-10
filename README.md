# JAHONORO — toʻylar jadvali

JAHONORO musiqa guruhi uchun Telegram Mini App: toʻylar jadvali, kalendar, avanslar va guruhga avtomatik eslatmalar.

- Nima va qanday: [`ARCHITECTURE.md`](ARCHITECTURE.md)
- Qanday koʻrinadi: [`DESIGN.md`](DESIGN.md)
- Ish tartibi va holat: [`CLAUDE.md`](CLAUDE.md)

## Stek

PHP 8.2 (frameworksiz, Composer'siz) · MySQL/MariaDB (PDO) · vanilla JS ES modules · cPanel shared hosting + cron.

## Tuzilma

```
public/        subdomen docroot (index.html, .htaccess, api/index.php, assets/)
src/           PHP kod (bootstrap.php, routes.php, Core/, Auth/, Telegram/, Repositories/, Services/, Controllers/)
cron/          reminders.php — eslatmalar ishchisi (cPanel cron)
database/      migrations/*.sql — phpMyAdmin orqali import
config/        config.example.php → serverda config.php (gitignore)
storage/logs/  log fayllar
tests/         PHP CLI testlar: php tests/run.php
```

## O‘rnatish

Qadam-baqadam: [`docs/DEPLOY.md`](docs/DEPLOY.md). Qo'lda sinov ro'yxati: [`docs/TESTING.md`](docs/TESTING.md).

Qisqacha: cPanel Git Version Control → subdomen docroot `jahonoro/public` → MySQL + `database/migrations/*.sql` import → `config/config.php` → `/api/ping` → cron har 5 daqiqa → ilovada Sozlamalar → "Guruhni ulash" (guruh tanlanadi, bot admin bo'ladi, tugma pin qilinadi).

## Hosting sinovi (0-bosqich)

`GET /api/ping` javobi:

```json
{ "ok": true, "data": { "php_version": "8.2.x", "config": "ok", "db": "ok", "telegram_api": "ok", "time": "2026-10-10T14:00:00+05:00" } }
```

- `config: missing` — `config/config.php` yo'q.
- `db: fail` — DB_* qiymatlari noto'g'ri yoki baza yo'q.
- `telegram_api: fail` — BOT_TOKEN noto'g'ri yoki hosting `api.telegram.org` ga tashqi so'rovni bloklayapti (hosting qo'llab-quvvatlashiga yozish kerak).

Xato tafsilotlari `storage/logs/app-YYYY-MM-DD.log` da (token va parollarsiz).

## Testlar

```
php tests/run.php
```

MySQL kerak bo'lgan testlar `TEST_DB_HOST/NAME/USER/PASS` muhit o'zgaruvchilaridan foydalanadi (standart: `127.0.0.1`, `jahonoro_test`, `jt`/`jt`). Baza bo'lmasa ular "skip" bo'ladi. Testlar bazani har safar tozalaydi — hech qachon ishchi bazaga ulamang.
