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
src/           PHP kod (bootstrap.php, Core/, Controllers/, ...)
config/        config.example.php → serverda config.php (gitignore)
storage/logs/  log fayllar
tests/         PHP CLI testlar: php tests/run.php
```

## Oʻrnatish (qisqa)

To'liq ro'yxat — `ARCHITECTURE.md` 9-bo'lim.

1. cPanel → Git Version Control → repo'ni `/home/USER/jahonoro` ga klonlash.
2. Subdomen docroot'i → `jahonoro/public`, AutoSSL yoqish.
3. MySQL baza va foydalanuvchi yaratish.
4. File Manager: `config/config.example.php` → `config/config.php`, qiymatlarni to'ldirish, ruxsat `600`.
5. Brauzerda `https://<subdomen>/api/ping` ochish.

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
