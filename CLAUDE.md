# CLAUDE.md — JAHONORO

Bu repo: JAHONORO musiqa guruhi (1 rahbar + 8 a'zo) uchun to'ylar jadvali Telegram Mini App.

**Har sessiya boshida o'qi:** `ARCHITECTURE.md` (nima va qanday) va `DESIGN.md` (qanday ko'rinadi). Ular yagona manba. Ular bilan zid narsa qilma; zarurat bo'lsa avval foydalanuvchiga ayt.

## Foydalanuvchi haqida

- Rahbar Bahrullo — qo'shiqchi va dasturchi. Kompyuteri yo'q: telefon va iPad'dan ishlaydi.
- Javoblar o'zbek lotin tilida, qisqa va aniq.
- Har bosqich oxirida: o'zgargan fayllar ro'yxati + telefondan qanday tekshirish (aniq URL yoki qadam). Terminal buyrug'i talab qiladigan tekshiruvni foydalanuvchiga berma — o'zing bajar.

## Ish tartibi

1. Foydalanuvchi "N-bosqichni bajar" deydi. Faqat o'sha bosqichni bajar (`ARCHITECTURE.md` 8-bo'lim).
2. Boshlashdan oldin pastdagi "Holat" bo'limini va oldingi bosqich fayllarini ko'r. Oldingi ishni buzma.
3. Har faylni to'liq yoz. Pseudocode, TODO, "qolganini o'zingiz" — yo'q.
4. Tekshir: barcha PHP fayllar `php -l` dan o'tsin; `tests/` dagi testlar ishlasin; JS fayllar `node --check` dan o'tsin (Node faqat tekshiruv uchun, loyihaga qo'shilmaydi).
5. Frontend bosqichlarida `DESIGN.md` 12-bo'lim tekshiruvini o'tkaz va natijani yoz. Imkon bo'lsa sahifani headless brauzerda 375×667 o'lchamda ochib skrinshot bilan o'zing tekshir.
6. "Holat" bo'limida bosqichni belgilab, bir qator izoh qo'sh.
7. Commit: `phase N: qisqa tavsif` (inglizcha yoki o'zbekcha).

## Texnik qoidalar

- PHP 8.2, framework yo'q, Composer'siz (qo'lda PSR-4 autoload `src/bootstrap.php` da). `declare(strict_types=1);`.
- MySQL/MariaDB, faqat PDO prepared, `ERRMODE_EXCEPTION`, `EMULATE_PREPARES=false`.
- Frontend: vanilla JS ES modules, build bosqichi yo'q, npm paket yo'q. Tashqi CDN yo'q — shriftlar va ikonkalar repoda.
- Shriftlar (Unbounded, Manrope) — woff2, `public/assets/fonts/` da (OFL litsenziyasi bilan).
- Xarita: Leaflet repoda (`assets/vendor/leaflet`), plitkalar CARTO'dan (CSP'da faqat `*.basemaps.cartocdn.com`). Boshqa tashqi kutubxona yo'q.
- UI matni faqat o'zbek lotin, `DESIGN.md` 10-bo'lim lug'ati bo'yicha. Kod identifikatorlari inglizcha.
- Ranglar, o'lchamlar faqat `tokens.css` o'zgaruvchilari orqali.
- Hosting: cPanel shared (hostmaster.uz). Doimiy jarayon, Docker, Node server yo'q. Fon ishlari faqat cron orqali.

## Buzilmaydigan qoidalar

- Bot token, DB parol — faqat `config/config.php` da (gitignore). Hech qachon kodda, frontendda, log'da, commit'da emas.
- initData har doim serverda imzo bilan tekshiriladi; brauzerdan kelgan user ID, rol, narx, qoldiqqa ishonilmaydi.
- Moliya maydonlari faqat `FinanceGuard::present()` orqali chiqadi. CSS/JS bilan yashirish — xavfsizlik emas.
- Guruh xabarlarida narx, avans, mijoz telefoni, ichki izoh — hech qachon.
- Har yozish endpoint'i `Access::requireOwner()` bilan.
- Ma'lumot DOM'ga faqat `textContent` / `escape()` orqali.
- Xarita nuqtasi taxmin qilinmaydi; koordinata faqat owner kiritadi va tasdiqlaydi.

## Dizayn darajasi

Bu ilova arzon admin panelga o'xshamasligi kerak. `DESIGN.md` dagi g'oya — "Firuza va sahna": issiq qora + firuza, Unbounded raqamlar; yagona yorqin element — firuza "Keyingi to'y" kartasi. Shubha bo'lganda: kamroq bezak, kattaroq raqam, aniqroq ierarxiya. `DESIGN.md` 11-bo'limdagi "Qilinmaydi" ro'yxatini buzma.

## Holat

- [x] 0 — Skelet va hosting sinovi — Core (Db, Router, Request, Response, Logger), `/api/ping`, .htaccess, logotipli index.html, 11 test; hostingda (jahonoro.itcode.uz, PHP 8.3.30, MySQL 8.0) `config`/`db`/`telegram_api` = ok
- [x] 1 — Maʼlumotlar bazasi — 8 jadval, FK, CHECK, seed; MariaDB 10.11 da ikki marta import xatosiz
- [x] 2 — Autentifikatsiya va aʼzolik — initData HMAC, Bearer sessiya (hash), getChatMember kesh 10 daq, pending oqimi, rate limit; 29 test
- [x] 3 — Toʻylar va moliya API — CRUD, holatlar, mojaro 409, avanslar (+ DELETE), dashboard, kalendar, aʼzolar, sozlamalar; FinanceGuard sizish testlari; 52 test
- [x] 4 — Telegram xabarlari va eslatmalar — MessageBuilder (oq roʻyxat), ReminderScheduler (dedupe, claim, qayta urinish), cron, remind-now, publish-button, health; 70 test
- [x] 5 — Frontend poydevori va dizayn tizimi — tokenlar (2 mavzu), Bodoni/Onest woff2, komponentlar, ikonkalar, api/auth/router/store/tg, sheet, toast, skeleton; CSP
- [x] 6 — Aʼzo sahifalari — Bosh sahifa (chipta + intro), Kalendar, Toʻylar (filtr, cheksiz yuklash), Tafsilot (timeline, xarita, moliya)
- [x] 7 — Owner ekranlari — forma (MainButton, mojaro sheet), avans, bekor/tiklash/arxiv, eslatma, Sozlamalar; 375×667 va 360 da skrinshot bilan tekshirildi
- [x] 8 — Hujjatlar, sinov, sayqal — docs/DEPLOY.md, docs/TESTING.md, README; DESIGN 12 tekshiruvi (kontrast, 44px, 360px, fokus, reduced-motion, tokenlar, ‘); hostingda sinov kutilmoqda
- [x] + Guruhni ilovadan ulash — startgroup havola + bir martalik kod, getUpdates (webhooksiz), begona guruhdan chiqish, supergroup migratsiyasi; 78 test
- [x] v2 — Foydalanuvchi talabi: vaqtlar (Manzilda bo‘lish/Nahor/Abet/Kech), 2 joy (to‘yxona + uy) xaritadan belgilash, yangi dizayn "Firuza va sahna", yangi logotip; migratsiya 003; 80 test
