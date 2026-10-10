# JAHONORO — Dizayn tizimi

Bu fayl ilovaning ko'rinishi bo'yicha yagona manba. Frontendga tegishli har bir qaror shu yerdan olinadi. Bu yerda yozilmagan narsa kerak bo'lsa — shu fayl ruhida qaror qabul qil va faylning oxiridagi "Qarorlar jurnali"ga bir qator qo'sh.

## 1. G'oya

**Firuza va sahna.** Issiq qora sahna, fil suyagi rangli matn va bitta urg'u rangi: Registon gumbazlarining **firuzasi**. Ilova sahna orqasidagi taxtaga o'xshaydi: sokin, tartibli, zamonaviy. Faqat bitta joy yorqin.

Uchta tamoyil:

1. **Vaqt — qahramon.** Musiqachi uchun eng muhimi: qachon va qayerda. Sana va soatlar katta Unbounded raqamlarda yoziladi, qolgan hamma narsa ulardan kichik va sokin.
2. **Jasorat bitta joyda.** Esda qoladigan yagona element — firuza rangli "Keyingi to'y" kartasi (bosh sahifa). Qolgan ekranlar qora-kulrang va intizomli.
3. **Tuzilma — bu ma'lumot.** Rang va belgi faqat ma'no bildirsa ishlatiladi. Firuza = harakat, "hozir/bugun", "Manzilda bo'lish". Rangli nuqta yoki chiziq = to'y turi. Qizil = bekor qilingan yoki xato.

## 2. Ranglar

Faqat CSS o'zgaruvchilari orqali (`public/assets/css/tokens.css`). Komponentlarda hex yozilmaydi.

### Tungi (asosiy)

| Token | Qiymat | Vazifa |
| --- | --- | --- |
| `--bg` | `#0D0C0B` | Sahifa foni (issiq qora) |
| `--surface` | `#171513` | Bloklar, sheet |
| `--surface-2` | `#211E1B` | Input, bosilgan holat, ikonka doirasi |
| `--surface-3` | `#2B2723` | Fokusdagi input, toast |
| `--text` | `#F4EDE3` | Asosiy matn (fil suyagi) |
| `--text-2` | `#A69C91` | Ikkilamchi matn |
| `--text-3` | `#746B62` | Uchlamchi (muhim bo'lmagan) |
| `--accent` | `#3DD6C4` | Firuza: asosiy tugma, bugun, tanlangan, "Keyingi to'y" kartasi |
| `--accent-ink` | `#06231F` | Firuza ustidagi matn |
| `--warning` | `#F4B740` | Ogohlantirish |
| `--danger` / `--danger-text` | `#F2625A` / `#FF7A70` | Bekor qilingan, xato |

### Kunduzgi

| Token | Qiymat |
| --- | --- |
| `--bg` | `#F3EFE9` |
| `--surface` | `#FFFFFF` |
| `--surface-2` | `#EAE4DC` |
| `--text` | `#16130F` |
| `--text-2` | `#665D53` |
| `--accent` | `#0B7F74` (matn — `--accent-text` `#08756A`), ustida oq matn |

Mavzu `Telegram.WebApp.colorScheme` dan olinadi va `<html data-theme>` ga yoziladi. Kontrast: oddiy matn ≥ 4.5:1, ikkala mavzuda tekshirilgan.

### To'y turlari (faqat nuqta va ingichka chiziq)

| Tur | Token | Rang |
| --- | --- | --- |
| Kelin to'yi | `--t-kelin` | `#E9C46A` |
| Nikoh to'yi | `--t-nikoh` | `#E8919A` |
| Sunnat to'yi | `--t-sunnat` | `#5BC0EB` |
| Beshik to'yi | `--t-beshik` | `#B39DFF` |
| Fotiha marosimi | `--t-fotiha` | `#9BD18B` |
| Korporativ tadbir | `--t-korp` | `#8FA6C8` |
| Boshqa tadbir | `--t-boshqa` | `#A69C91` |

Rang hech qachon yolg'iz ma'no tashimaydi: chip ichida doim tur nomi yoziladi.

## 3. Tipografiya

| Rol | Shrift | Qayerda |
| --- | --- | --- |
| Raqamlar va sarlavhalar | **Unbounded** (600) | Sana, soat, sahifa sarlavhasi, logotip yozuvi |
| Matn va interfeys | **Manrope** (450–700) | Hamma qolgan joy |

- Ikkala shrift ham `public/assets/fonts/` da, woff2 formatida, OFL litsenziyasi bilan. Tashqi so'rov yo'q.
- O'zbekcha `oʻ`/`gʻ` uchun **U+2018 (‘)** ishlatiladi, chunki Manrope'da U+02BB yo'q. Tutuq belgisi — U+2019 (’).
- Raqamlar: `tabular-nums`, harflar oralig'i `-0.02em`.

| Token | O'lcham | Misol |
| --- | --- | --- |
| `--fs-hero` | 84 | "Keyingi to'y" kartasidagi kun raqami |
| `--fs-time-lg` | 28 | To'y sahifasidagi soatlar |
| `--fs-h1` | 26 | Sahifa sarlavhasi |
| `--fs-time` | 22 | Kartadagi soatlar, forma vaqt maydoni |
| `--fs-h2` | 18 | Bo'lim sarlavhasi (Manrope 700) |
| `--fs-row-time` | 17 | Ro'yxatdagi soat |
| `--fs-body` | 15 | Asosiy matn |
| `--fs-label` | 13 | Maydon nomi, chip |
| `--fs-micro` | 11 | Hafta kunlari, navigatsiya yozuvi |

Sarlavhalar jumla registrida. Faqat logotip `JAHONORO` bosh harflarda va kengaytirilgan harf oralig'ida (`0.16em`) yoziladi.

## 4. Shakl, oraliq, chuqurlik

- **Oraliq:** 4px to'r. Sahifa yon chetlari 20px (360px dan tor ekranda 16px).
- **Radiuslar:** "Keyingi to'y" kartasi va sheet 28px, bloklar 20px, input va tugmalar 14px, kalendar katagi 12px, chip va navigatsiya to'liq yumaloq.
- **Chuqurlik:** asosan fon farqi. Soya faqat suzuvchi elementlarda: pastki navigatsiya, "+" tugma, toast, sheet.
- **Naqsh:** o'zbek girih naqshi faqat "Keyingi to'y" kartasida, 7% shaffoflikda, burchakdan so'nib boradi.
- **Teginish maydoni:** kamida 44×44px.

## 5. Ikonkalar

`public/assets/js/ui/icons.js` — `icon(name, {size, label})`. Barcha ikonkalar 24×24, chiziq qalinligi 1.75, yumaloq uchlar, bitta uslubda chizilgan. Vaqtlar uchun: `flag` (Manzilda bo'lish), `sunrise` (Nahor), `sun` (Abet), `moon` (Kech). Joylar uchun: `building` (to'yxona), `home` (uy). Emoji faqat Telegram guruh xabarlarida ishlatiladi.

**Logotip:** peshtoq (o'tkir uchli ravoq) ichida nota. Ravoq firuza, nota fil suyagi rangida. Kod: `logoMark()` / `logo()` (`ui/components.js`). Fayl: `assets/img/logo.svg`. BotFather uchun rasmlar: `docs/brand/`.

## 6. Komponentlar

### 6.1 "Keyingi to'y" kartasi (esda qoladigan element)

Bosh sahifada, bitta. Butun fon firuza, matn `--accent-ink`.
- Tepada to'y turi va qolgan vaqt ("1 kun 12 soat qoldi"). Bugun bo'lsa — miltillovchi nuqta.
- Katta kun raqami (84px), yonida oy va hafta kuni.
- To'y nomi.
- Vaqtlar 2 ustunli kataklarda, soat tartibida. **Manzilda bo'lish** teskari rangda (qora fonda firuza), chunki bu guruh uchun eng muhim vaqt.
- Pastda joy nomi va "Xarita" tugmasi.
- Sessiyada birinchi ochilganda raqam va vaqtlar navbat bilan 10px pastdan ko'tariladi.

### 6.2 To'y qatori

Guruhlangan blok ichidagi qator: chapda soat (Unbounded 17px), uning ostida to'y turi rangidagi qisqa chiziq. O'rtada nom (Manrope 650) va joy. Bekor qilingan to'y — 50% shaffof, nomi chizilgan, o'ngda "Bekor qilingan" chipi. Bugungi to'yning soati firuza rangda.

### 6.3 Kun sarlavhasi

`Bugun`, `Ertaga`, `18-oktabr, shanba`. Yopishqoq, fon xira (blur).

### 6.4 Kalendar

Oy nomi Unbounded 21px, bir qatorda. Kataklar `--surface` blok ichida. Bugun — firuza halqa, tanlangan kun — firuza fon. Kun ostida 3 tagacha to'y turi rangidagi nuqta. Gorizontal suring bilan oy almashadi.

### 6.5 Tugmalar

| Tur | Ko'rinish |
| --- | --- |
| Asosiy | firuza fon, `--accent-ink` matn |
| Ikkilamchi | `--surface-2` fon |
| Shaffof | fon yo'q, firuza matn |
| Xavfli | qizil 13% fon, qizil matn |

Balandlik 52px (kichik — 38px). Bosilganda kichrayadi (`scale(0.97)`). Formada saqlash tugmasi — Telegram MainButton (firuza rangda).

### 6.6 Chip

28px, to'liq yumaloq. Tur chipi — rangli nuqta va nom. Holat chiplari: Tasdiqlangan — firuza, Bekor qilingan — qizil, qolganlari — kulrang.

### 6.7 Forma

- Bo'limlar: **Asosiy · Vaqtlar · To'yxona · To'y egasining uyi · Moliya · Qo'shimcha**.
- **Vaqtlar:** 4 ta katak — Manzilda bo'lish, Nahor, Abet, Kech. Har birida ikonka va katta soat. Nahor, abet yoki kechdan kamida bittasi shart.
- **To'yxona:** nomi va xaritadagi nuqtasi. **To'y egasining uyi:** manzili va xaritadagi nuqtasi.
- **Xaritadan belgilash:** sheet ichida xarita. Markazda qimirlamaydigan belgi turadi, xarita uning ostida suriladi. "Mening joylashuvim" tugmasi bor, Google Maps havolasi yoki koordinatani qo'yish ham mumkin. Owner o'zi qo'ygan nuqta tasdiqlangan hisoblanadi.
- Input 52px, `--surface-2` fon, fokusda firuza chegara.

### 6.8 Bottom sheet

Tepada 28px radius va tutqich. Pastga tortib yopiladi (xarita ichida emas). Telegram BackButton ham yopadi.

### 6.9 Toast

Pastki navigatsiya ustida, yumaloq, ikonka va bitta qator. 2.4 soniya ko'rinadi.

### 6.10 Bo'sh holat

Doira ichida ikonka, Unbounded sarlavha, bitta qator izoh, owner uchun bitta tugma.

### 6.11 Skeleton

Haqiqiy komponent shaklida. 300ms dan tez yuklansa umuman ko'rinmaydi.

### 6.12 Pastki navigatsiya

Suzuvchi yumaloq panel: chetlardan 12px, xira fon va soya. 4 ta bo'lim (a'zoga 3 ta). Faol bo'lim firuza ikonka va yozuv bilan, orqasida firuza 12% fon. Owner uchun Kalendar va To'ylar sahifalarida "+" tugma.

### 6.13 Moliya bloki

Kelishilgan narx · To'langan · Qoldiq. Raqamlar Unbounded 18px. Ostida firuza progress chizig'i. A'zoga moliya yashirin bo'lsa, blok umuman ko'rsatilmaydi.

### 6.14 Joylar va xarita

To'y sahifasida "Joylar" blokida 2 ta karta: **To'yxona** va **To'y egasining uyi**. Har kartada nom, xaritadagi nuqta (kichik xarita, bosilmaydi) va "Google Maps" | "Yandex" tugmalari. Nuqta qo'yilmagan bo'lsa — sariq qator: "Xaritada belgilanmagan — nomi bo'yicha qidiriladi".

## 7. Ekranlar

### Bosh sahifa
Logotip va avatar. "Xayrli kech," (kichik) va ism (Unbounded). Firuza karta (6.1), uchta raqam (Bu hafta · Bu oy · Kelgusi). Owner uchun: guruh ulanmagan / yangi a'zolar haqida eslatma, "Shu hafta", "Qarzdorlik", "So'nggi o'zgarishlar".

### Kalendar
6.4 va tanlangan kunning to'ylari.

### To'ylar
Qidiruv, filtr, `Kelgusi | O'tgan`, kun sarlavhalari bilan guruhlangan ro'yxat, pastga yetganda keyingi sahifa yuklanadi.

### To'y tafsiloti
Tur va holat chiplari, nom (Unbounded), sana. **Vaqtlar:** Manzilda bo'lish to'liq kenglikda firuza fonda, Nahor/Abet/Kech kartochkalarda. **Joylar** (6.14), **Moliya**, **Ijro**, **Faqat siz uchun** (owner). Owner uchun "…" menyusi.

### To'y formasi
6.7. Mojaro bo'lsa — sheet.

### Sozlamalar (owner)
Guruh (ulash / ulangan) · Moliya ko'rinishi · Eslatmalar · A'zolar · Tizim holati.

### Telegram tashqarisida
Markazda logotip belgisi, `JAHONORO` yozuvi va bitta qator matn.

## 8. Harakat

- Standart: 180ms, `cubic-bezier(0.2, 0.8, 0.2, 1)`. Sheet: 280ms. Sahifa almashinuvi: 160ms faqat opacity (siljishsiz).
- **Yagona orkestrlangan lahza:** sessiyada birinchi marta bosh sahifa ochilganda firuza kartadagi kun raqami, oy va vaqt kataklari navbat bilan (60ms oraliq) 10px pastdan ko'tariladi. Boshqa joylarda kirish animatsiyalari yo'q.
- Foydalanuvchi harakatiga javob: tugma bosilishi, sheet ochilishi, kalendar oy almashinuvi (12px siljish + opacity).
- `prefers-reduced-motion: reduce` — hamma animatsiya o'chadi, faqat opacity 100ms qoladi.
- Haptic (`Telegram.WebApp.HapticFeedback`): tab almashinuvi `impactOccurred('light')`; kalendar kun tanlash `selectionChanged()`; saqlandi `notificationOccurred('success')`; mojaro `'warning'`; xato `'error'`.

## 9. Telegram moslashuvi

- Ishga tushishda: `ready()`, `expand()`, `setHeaderColor` va `setBackgroundColor` — mavzuga qarab `--bg` (tokendan o'qiladi), `setBottomBarColor` mavjud bo'lsa.
- Forma va kalendar suring bilan ishlaganda `disableVerticalSwipes()`; boshqa sahifalarda `enableVerticalSwipes()`.
- Balandlik: `var(--tg-viewport-stable-height, 100vh)`. Yuqori/pastki xavfsiz zona: `env(safe-area-inset-*)` + mavjud bo'lsa `contentSafeAreaInset`.
- `BackButton` — ichki sahifalarda ko'rinadi, router orqaga qaytaradi yoki sheet'ni yopadi.
- Ichki havolalar hash router orqali; tashqi havolalar faqat `openLink`.
- Qurilmalar: iPhone SE (375×667) dan Pro Max gacha, Android 360px dan. Gorizontal skroll hech qayerda bo'lmasin.

## 10. Matn ovozi

- Sodda, aniq, faol. Tugma aniq nima bo'lishini aytadi va oqim davomida o'zgarmaydi: "Saqlash" → toast "Saqlandi"; "Avans qoʻshish" → "Avans qoʻshildi".
- Xatolar uzr so'ramaydi va noaniq emas: nima bo'ldi + nima qilish kerak.
  - "Internet yoʻq. Ulanganda qayta urinib koʻring." [Qayta urinish]
  - "Sessiya tugadi. Ilovani guruhdagi tugmadan qayta oching."
  - "Sizda bu ilovaga kirish huquqi yoʻq. Guruh rahbariga murojaat qiling."
- Lug'at (hamma joyda bir xil): Toʻy (event emas) · Toʻyxona/Joy · Boshlanish · Nahor · Abet · Yigʻilish · Joʻnash · Soundcheck · Avans · Qoldiq · Kelishilgan narx · Toʻyni bekor qilish · Tiklash · Arxivlash · Yopish (dialogni yopish — "Bekor qilish" emas, chalkashmasin).
- Sana formati: `18-oktabr, shanba`; yil faqat joriy yildan farq qilsa: `3-yanvar 2027, shanba`. Soat 24 soatlik `18:00`. Pul: `12 500 000 soʻm` (bo'sh joy — `\u202F`).

## 11. Qilinmaydi

- Hamma narsa bir xil yumaloq kartalarga bo'lingan "SaaS dashboard" ko'rinishi.
- Gradient fonlar, neon, porlash effektlari (blur faqat navigatsiya, kun sarlavhasi va sheet orqasida).
- Har kartaga bir xil kulrang soya.
- BOSH HARFLI yorliqlar, sarlavha ustidagi kichik "eyebrow" yozuvlar, tugmalarga "→".
- Raqamli bezak markerlar (01 / 02 / 03) — faqat haqiqiy ketma-ketlik (vaqt jadvali) bundan mustasno.
- Emoji navigatsiya, multfilm illyustratsiyalar, stok rasmlar.
- Tashqi CDN (shrift, ikonka, kutubxona) — hammasi o'zimizning hostingda.

## 12. Sifat tekshiruvi (har frontend bosqichi oxirida)

- [ ] Kontrast: matn ≥ 4.5:1, katta raqamlar ≥ 3:1 (ikkala mavzuda)
- [ ] Teginish maydonlari ≥ 44px
- [ ] 360px va 375×667 ekranda hech narsa kesilmaydi, gorizontal skroll yo'q
- [ ] Klaviatura fokusi ko'rinadi (`:focus-visible` firuza halqa)
- [ ] `prefers-reduced-motion` ishlaydi
- [ ] Skeleton → kontent o'tishida joy sakramaydi (CLS yo'q)
- [ ] Barcha ikonkalar bir xil chiziq qalinligida
- [ ] Xarita nuqtasi faqat owner tomonidan qo'yiladi; nuqtasiz joy uchun ogohlantirish chiqadi
- [ ] Hech qayerda hex rang yoki px shrift o'lchami komponent ichida qattiq yozilmagan (faqat tokenlar)
- [ ] O'zbekcha belgilar (ʻ) to'g'ri chiqadi
- [ ] Bosh sahifa bitta qarashda javob beradi: keyingi to'y qachon va qayerda

## Qarorlar jurnali

| Sana | Qaror | Sabab |
| --- | --- | --- |
| 2026-10-10 | Bodoni Moda + Onest | Konsert afishasi xarakteri + o'qilishi yaxshi interfeys shrifti |
| 2026-10-10 | Chipta — yagona yorqin element | Vaqt va joy — musiqachi uchun eng muhim ma'lumot |
| 2026-10-10 | UI matnida `ʻ` (U+02BB) o'rniga `‘` (U+2018), `ʼ` o'rniga `’` (U+2019) — butun loyihada | Bodoni Moda woff2'da U+02BB gilfi yo'q (Onest'da bor); sarlavhalarda zaxira shrift aralashmasin |
| 2026-10-10 | Telegram SDK `https://telegram.org/js/telegram-web-app.js` dan yuklanadi | Bu kutubxona emas, platformaning o'zi; Telegram klienti uni doim ochadi. Shrift, ikonka va boshqa hamma narsa o'zimizning hostingda |
| 2026-10-10 | Vaqt jadvali nuqtalari soat bo'yicha tartiblanadi (teng bo'lsa 7-bo'limdagi tartib) | Nahor ertalab bo'lsa ro'yxat haqiqiy ketma-ketlikni ko'rsatsin |
| 2026-10-10 | Qo'shimcha tokenlar: `--success-text`, `--warning-text`, `--danger-text`, `*-soft`, `--muted-soft` | 14% fon ustidagi kichik matn ikkala mavzuda ≥ 4.5:1 bo'lsin (kunduzgi: #24704F, #8A5F12, #B8322C; tungi xavfli matn #F07C75) |
| 2026-10-10 | Chipta ichida `.theme-night` sinfi — tungi tokenlar qayta e'lon qilinadi | Chipta ikkala mavzuda ham tungi; teshiklar `--page-bg` bilan |
| 2026-10-10 | Koordinata formada bitta maydon: "38.27, 67.89" yoki Google Maps havolasi | Telefonda ikki maydondan qulay; tasdiq switch'i faqat koordinata tanilsa yoqiladi |
| 2026-10-10 | `#/styleguide` sahifasi yaratilmadi | 5–7-bosqichlar bitta sessiyada bajarildi; 7-bosqich uni baribir olib tashlaydi. Komponentlar haqiqiy sahifalarda tekshirildi |
| 2026-10-10 | **2-versiya dizayn: "Firuza va sahna"** — issiq qora + firuza, Unbounded + Manrope. Bodoni/Onest va navy+oltin bekor qilindi | Foydalanuvchiga eski ko'rinish yoqmadi ("arzon"). Firuza — Samarqand gumbazlari, o'zbek ruhi; qora — sahna |
| 2026-10-10 | "Keyingi to'y" chiptasi o'rniga butunlay firuza karta | Yagona yorqin element yanada kuchli, perforatsiya va teshiklar ortiqcha bezak edi |
| 2026-10-10 | Vaqtlar: Manzilda bo'lish · Nahor · Abet · Kech. Jo'nash, Soundcheck va "Boshlanish" olib tashlandi | Foydalanuvchi talabi. Asosiy vaqt (`start_time`) = nahor/abet/kechning eng birinchisi |
| 2026-10-10 | Ikki joy: to'yxona va to'y egasining uyi, ikkalasi xaritadan belgilanadi | Nahor ko'pincha uyda, kech to'yxonada o'tadi |
| 2026-10-10 | Xarita: Leaflet 1.9.4 (repoda) + CARTO Voyager plitkalari (OpenStreetMap) | Kutubxona o'zimizda; xarita rasmlari tabiatan tashqi, kalitsiz va bepul. CSP'da faqat `*.basemaps.cartocdn.com` ruxsat etilgan |
| 2026-10-10 | Yangi logotip: peshtoq ichida nota | Oddiy, kichik o'lchamda ham o'qiladi, milliy va musiqiy ma'no |

