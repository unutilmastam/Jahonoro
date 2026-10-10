# JAHONORO — Dizayn tizimi

Bu fayl ilovaning ko'rinishi bo'yicha yagona manba. Frontendga tegishli har bir qaror shu yerdan olinadi. Bu yerda yozilmagan narsa kerak bo'lsa — shu fayl ruhida qaror qabul qil va faylning oxiridagi "Qarorlar jurnali"ga bir qator qo'sh.

## 1. G'oya

**Konsert afishasi va sahna orqasi.** Tun ko'k fon, shampan oltin, baland kontrastli serif raqamlar. Ilova guruhning "sahna orqasidagi taxtasi" kabi: sokin, tartibli, qimmat ko'rinadi — va bitta joyda yorqin.

Uchta tamoyil:

1. **Vaqt — qahramon.** Musiqachi uchun eng muhim narsa: qachon, qayerda. Sana va soatlar katta serif raqamlarda, qolgan hamma narsa ulardan kichik va sokin.
2. **Jasorat bitta joyda.** Esda qoladigan yagona element — "Keyingi to'y" chiptasi (bosh sahifada). Qolgan ekranlar sokin, intizomli, bezaksiz.
3. **Tuzilma — bu ma'lumot.** Chiziq, rang, belgi faqat ma'no tashisa ishlatiladi. Rangli chiziq = to'y turi. Oltin = harakat yoki "hozir/bugun". Qizil = bekor qilingan yoki xato.

## 2. Ranglar

Faqat CSS o'zgaruvchilari orqali (`public/assets/css/tokens.css`). Komponentlarda hex yozilmaydi.

### Tungi (asosiy)

| Token | Qiymat | Vazifa |
| --- | --- | --- |
| `--bg` | `#101522` | Sahifa foni (tun ko'k) |
| `--surface` | `#181E2B` | Ro'yxat bloklari, sheet, chipta |
| `--surface-2` | `#212839` | Input, bosilgan holat, kalendar katagi |
| `--line` | `rgba(247,244,237,0.08)` | Ajratuvchi ingichka chiziq |
| `--line-strong` | `rgba(247,244,237,0.16)` | Input chegarasi |
| `--text` | `#F7F4ED` | Asosiy matn (fil suyagi) |
| `--text-2` | `#929BAD` | Ikkilamchi matn |
| `--text-3` | `#646D80` | Uchlamchi (faqat 13px+ va muhim bo'lmagan) |
| `--gold` | `#D8B36A` | Asosiy tugma, "bugun", tanlangan |
| `--gold-ink` | `#101522` | Oltin ustidagi matn |
| `--gold-soft` | `rgba(216,179,106,0.14)` | Oltin fon (chip, belgi) |
| `--gold-line` | `rgba(216,179,106,0.38)` | Chipta chegarasi |
| `--success` | `#3FA67A` | To'langan, yuborilgan |
| `--warning` | `#D9A441` | Mojaro, tasdiqlanmagan joy |
| `--danger` | `#E5564F` | Bekor qilingan, xato |

### Kunduzgi (Telegram light mavzusida)

| Token | Qiymat |
| --- | --- |
| `--bg` | `#F7F4ED` |
| `--surface` | `#FFFFFF` |
| `--surface-2` | `#EFEADF` |
| `--line` | `rgba(16,21,34,0.08)` |
| `--line-strong` | `rgba(16,21,34,0.16)` |
| `--text` | `#101522` |
| `--text-2` | `#5B6475` |
| `--text-3` | `#8A91A0` |
| `--gold` | `#D8B36A` (tugma foni, matn `--gold-ink`) |
| `--gold-text` | `#8C6A24` (oq fonda oltin matn — kontrast uchun) |

Mavzu `Telegram.WebApp.colorScheme` dan olinadi va `<html data-theme="dark|light">` ga yoziladi; `themeChanged` hodisasida yangilanadi. Chipta ikkala mavzuda ham tungi ranglarda qoladi — bu brend lahzasi.

### To'y turlari (faqat 3px chiziq, nuqta va chip uchun)

| Tur | Token | Rang |
| --- | --- | --- |
| Kelin to'yi | `--t-kelin` | `#D8B36A` |
| Nikoh to'yi | `--t-nikoh` | `#D49A9A` |
| Sunnat to'yi | `--t-sunnat` | `#5FAFA5` |
| Beshik to'yi | `--t-beshik` | `#A796DA` |
| Fotiha marosimi | `--t-fotiha` | `#93B37F` |
| Korporativ tadbir | `--t-korp` | `#7499CF` |
| Boshqa tadbir | `--t-boshqa` | `#929BAD` |

Rang hech qachon yolg'iz ma'no tashimaydi: chip ichida doim tur nomi yoziladi.

## 3. Tipografiya

| Rol | Shrift | Qayerda |
| --- | --- | --- |
| Raqamlar va sarlavhalar | **Bodoni Moda** (400, 500, 600) | Sana, soat, countdown, sahifa sarlavhasi, chipta |
| Matn va interfeys | **Onest** (400, 500, 600) | Hamma qolgan joy |

- Shriftlar `public/assets/fonts/` ga **woff2** qilib qo'yiladi (o'zimizning hostingdan, tashqi so'rovsiz). `font-display: swap`. Zaxira: `Georgia, serif` va `system-ui, -apple-system, "Segoe UI", Roboto, sans-serif`.
- O'zbek lotin belgilari: UI matnida `oʻ`, `gʻ` uchun **U+02BB (ʻ)**, tutuq belgisi uchun **U+02BC (ʼ)**. Shrift yuklangandan keyin bu glif bor-yo'qligini tekshir; yo'q bo'lsa butun loyihada **U+2018 (‘)** ga o'tkaz va jurnalga yoz. Oddiy `'` UI matnida ishlatilmaydi.
- Raqamlar: `font-variant-numeric: lining-nums tabular-nums;` — soatlar ustun bo'lib tekis turadi.

### Shkala (px / line-height)

| Token | O'lcham | LH | Shrift | Misol |
| --- | --- | --- | --- | --- |
| `--fs-hero` | 64 | 1.0 | Bodoni 500 | Chiptadagi kun raqami "18" |
| `--fs-time` | 34 | 1.05 | Bodoni 500 | Chiptadagi boshlanish soati |
| `--fs-h1` | 28 | 1.15 | Bodoni 500 | Sahifa sarlavhasi "Kalendar" |
| `--fs-h2` | 20 | 1.25 | Onest 600 | Bo'lim, tafsilot sarlavhasi |
| `--fs-row-time` | 22 | 1.1 | Bodoni 500 | Ro'yxatdagi soat |
| `--fs-body` | 15 | 1.45 | Onest 400 | Asosiy matn |
| `--fs-label` | 13 | 1.35 | Onest 500 | Maydon nomi, chip |
| `--fs-micro` | 12 | 1.3 | Onest 500 | Hafta kunlari, izoh |

Qoidalar: sarlavhalar jumla registrida (faqat birinchi harf katta). BOSH HARFLI yorliqlar, harf oralig'i kengaytirilgan "eyebrow"lar, sarlavhada bitta so'zni boshqa rangga bo'yash — **ishlatilmaydi**. Matn qatori 70 belgidan oshmaydi.

## 4. Shakl, oraliq, chuqurlik

- **Oraliq:** 4px to'r — `4, 8, 12, 16, 20, 24, 32, 40, 56`. Sahifa yon chetlari 16px (390px+ ekranda 20px).
- **Radius ierarxiyasi** (hammasiga bitta radius emas):
  - Chipta: 22px
  - Bottom sheet: 24px (faqat tepada)
  - Ro'yxat bloki (guruhlangan qatorlar konteyneri): 16px
  - Input, tugma: 12px
  - Chip: to'liq yumaloq
  - Kalendar katagi: 10px
- **Chuqurlik:** soyalar o'rniga chegara va fon farqi. Yagona soya — ochilgan sheet ostida: `0 -12px 40px rgba(0,0,0,0.35)`. Kartalarga soya berilmaydi.
- **Gradient:** faqat bitta joyda — chipta foni `--surface` dan 3% ochroq rangga juda yengil vertikal o'tish. Boshqa hech qayerda.
- **Teginish maydoni:** kamida 44×44px. Ro'yxat qatori kamida 64px balandlikda.

## 5. Ikonkalar

`public/assets/js/ui/icons.js` — `icon(name, {size, label})` funksiyasi inline SVG qaytaradi.

- `viewBox="0 0 24 24"`, `fill="none"`, `stroke="currentColor"`, `stroke-width="1.75"`, `stroke-linecap="round"`, `stroke-linejoin="round"`.
- O'lchamlar: 20 (qator ichida), 24 (navigatsiya, tugma), 18 (chip).
- `label` berilsa `role="img"` + `aria-label`; bo'lmasa `aria-hidden="true"`.
- Hammasi qo'lda chizilgan, bitta uslubda (Lucide uslubiga yaqin, lekin kod ichida o'zimizniki): calendar, clock, music, rings, map-pin, navigation, wallet, payment, eye, eye-off, bell, users, settings, edit, archive, trash, search, filter, chevron-left, chevron-right, chevron-down, lock, shield, more, plus, check, x, home, list, refresh, phone, shirt (dress code), speaker (jihozlar), alert.
- Emoji navigatsiya va tugmalarda **ishlatilmaydi** (faqat Telegram guruh xabarlarida).

## 6. Komponentlar

### 6.1 Keyingi to'y chiptasi (esda qoladigan element)

Bosh sahifaning eng tepasida, faqat bitta. Konsert chiptasi shaklida.

```
╭──────────────────────────────────────╮
│  18                     ● Kelin toʻyi │
│  oktabr, shanba                       │
│                                       │
│  Sohil toʻyxonasi                     │
│  Denov, Mustaqillik koʻchasi 12       │
◖ - - - - - - - - - - - - - - - - - - -◗  ← teshik + perforatsiya
│  Boshlanish        Yigʻilish          │
│  18:00             16:30              │
│                                       │
│  2 kun 4 soat qoldi       [Xarita]    │
╰──────────────────────────────────────╯
```

- Fon `--surface` (tungi), chegara 1px `--gold-line`, radius 22px.
- Yon teshiklar: perforatsiya chizig'i balandligida, diametri 20px, `mask` (`radial-gradient`) bilan o'yilgan — orqadagi `--bg` ko'rinadi.
- Perforatsiya: 1px `dashed` `--gold-line`, 16px chetdan.
- "18" — `--fs-hero`, `--text`. Oy va hafta kuni — `--fs-body`, `--text-2`.
- Soatlar — `--fs-time`, Bodoni. Countdown — Onest 500, `--gold`.
- Pastki qismda (stub) faqat bitta bezak: guruh nomidagi "oro" (bezak) ma'nosiga ishora — o'zbek girih naqshining juda nozik SVG patterni, 4% shaffoflikda, faqat stub foni ichida. Boshqa hech qayerda naqsh yo'q.
- Bugun bo'lsa: countdown o'rniga "Bugun" + oltin nuqta; boshlanishga 3 soatdan kam qolsa "3 soat ichida".
- Butun chipta bosiladi → tafsilot. "Xarita" — alohida kichik ikkilamchi tugma.
- Kelgusi to'y yo'q bo'lsa — chipta o'rniga bo'sh holat (6.10).

### 6.2 To'y qatori (ro'yxat, agenda, kun ro'yxati)

Kartalar emas — guruhlangan ro'yxat bloki ichidagi qatorlar (iOS sozlamalari uslubida, lekin o'zimizcha).

```
┃ 18:00   Kelin toʻyi — Aziz va Madina        ›
┃         Sohil toʻyxonasi, Denov
```

- Chapda 3px vertikal chiziq — to'y turi rangi, yumaloq uchlar.
- Soat — `--fs-row-time` Bodoni, 56px kenglikdagi ustun, tabular.
- Sarlavha — `--fs-body` 500, bir qator, `…` bilan qisqartiriladi. Ostida joy — `--fs-label`, `--text-2`.
- Qatorlar orasida `--line` ajratuvchi, soat ustunidan keyin boshlanadi.
- Bekor qilingan: sarlavha chizilgan, butun qator 55% shaffof, o'ngda "Bekor qilingan" chip (`--danger`).
- Bugungi qator: soat `--gold` rangida.

### 6.3 Kun sarlavhasi (agenda guruhi)

`Bugun`, `Ertaga`, keyin `18-oktabr, shanba`. `--fs-label` 600, `--text-2`, yopishqoq (`position: sticky`), fon `--bg` 92% + `backdrop-filter: blur(12px)`.

### 6.4 Kalendar

```
   Oktabr 2026                 ‹   ›
 Du  Se  Ch  Pa  Ju  Sh  Ya
           1   2   3   4
  5   6   7   8   9  10  11
                 •      ••
 12 [13] 14  15  16  17  18
 ...
```

- Hafta dushanbadan boshlanadi. Kun nomlari: Du Se Ch Pa Ju Sh Ya (`--fs-micro`, `--text-3`).
- Katak 44px+, raqam Onest 500 15px. Ostida 3 tagacha tur rangidagi 5px nuqta; ko'p bo'lsa 2 nuqta + "+2".
- Bugun: 1px `--gold` halqa. Tanlangan: `--gold` fon, `--gold-ink` raqam. Boshqa oy kunlari: `--text-3`.
- Oy nomi Bodoni `--fs-h1`. Chap/o'ng chevron tugmalar + gorizontal suring bilan oy almashadi; sarlavhani bosish → yil/oy tanlovchi sheet.
- Kalendar ostida tanlangan kunning qatorlari (6.2). Bo'sh kun: "Bu kunda toʻy yoʻq" (`--text-2`, bitta qator, tugmasiz).

### 6.5 Tugmalar

| Tur | Ko'rinish | Qayerda |
| --- | --- | --- |
| Asosiy | `--gold` fon, `--gold-ink` matn, 600 | Saqlash, Toʻy qoʻshish |
| Ikkilamchi | `--surface-2` fon, `--text` | Xaritada ochish, Avans qoʻshish |
| Shaffof | fon yo'q, `--gold` / `--gold-text` matn | Bekor qilish (dialogda), Hammasini koʻrish |
| Xavfli | `--danger` 14% fon, `--danger` matn | Toʻyni bekor qilish |

Balandlik 48px (kichik: 36px). Bosilganda `transform: scale(0.98)` + fon 8% to'qroq, 120ms. Yuklanayotganda matn o'rnida 18px spinner, kenglik o'zgarmaydi. Strelka (→) qo'shilmaydi.

Formada asosiy saqlash tugmasi — `Telegram.WebApp.MainButton` (oltin rangda: `setParams({color:'#D8B36A', text_color:'#101522'})`). Telegram tashqarisida — pastda yopishqoq oddiy tugma.

### 6.6 Chip

Balandlik 26px, gorizontal 10px, `--fs-label`. Tur chipi: 6px rangli nuqta + nom, fon `--surface-2`. Holat chiplari: Rejada (`--text-2`), Tasdiqlangan (`--success`), Bekor qilingan (`--danger`), Arxiv (`--text-3`) — 14% fon + rangli matn.

### 6.7 Input va forma

- Maydon nomi tepada, `--fs-label` 500 `--text-2`. Input 48px, `--surface-2` fon, 1px `--line-strong`, radius 12px. Fokus: 2px `--gold` halqa (`outline`), fon o'zgarmaydi.
- Xato: chegara `--danger`, ostida `--fs-label` `--danger` matn — nima noto'g'ri va qanday tuzatish ("Narx manfiy boʻlishi mumkin emas").
- Vaqt maydonlari ikkitadan yonma-yon (Nahor | Abet, Yigʻilish | Joʻnash). Native `type="time"`, `type="date"`.
- Pul maydoni: `inputmode="numeric"`, yozish paytida `12 500 000` ko'rinishida guruhlanadi, o'ngda "soʻm".
- Forma bo'limlari: Asosiy · Vaqt jadvali · Joy · Moliya · Qo'shimcha. Har biri alohida ro'yxat bloki, ustida `--fs-label` 600 sarlavha.
- Moliya ko'rinishi — 3 bo'lakli segment: `Umumiy sozlama | Yashirin | Aʼzolarga ochiq`, yonida eye/eye-off ikonka.

### 6.8 Bottom sheet

Pastdan chiqadi, radius 24px tepada, tepasida 36×4px tutqich. Fon `--surface`. Orqa fon `rgba(0,0,0,0.5)`. Pastga tortib yopiladi. Telegram `BackButton` ham yopadi. Ishlatiladi: filtrlar, avans qo'shish, oy/yil tanlash, tasdiqlash dialoglari.

### 6.9 Toast

Pastki navigatsiya ustida, markazda, `--surface-2` fon, 1px `--line-strong`, radius 12px, ikonka + bitta qator. 2.4 soniya. Muvaffaqiyat — check ikonka `--success`; xato — alert `--danger` + "Qayta urinish" tugmasi (bo'lsa, toast yopilmaydi).

### 6.10 Bo'sh holat

Rasm yoki illyustratsiya yo'q. 32px ikonka (`--text-3`), bitta qator sarlavha (`--fs-h2`), bitta qator izoh, owner uchun bitta tugma.

- Kelgusi to'y yo'q: "Kelgusi toʻylar yoʻq" / "Yangi buyurtma kelganda shu yerda koʻrinadi." / [Toʻy qoʻshish]
- Qidiruv natijasiz: "Hech narsa topilmadi" / "Boshqa soʻz yoki filtr bilan urinib koʻring." / [Filtrlarni tozalash]

### 6.11 Skeleton

Haqiqiy komponent shaklida (chipta, qatorlar), `--surface-2` bloklar, sekin yorug'lik o'tishi 1.4s. 300ms dan tez yuklansa skeleton umuman ko'rsatilmaydi (miltillamasin).

### 6.12 Pastki navigatsiya

4 ta (a'zoga 3 ta): Bosh sahifa · Kalendar · Toʻylar · Sozlamalar (faqat owner). Ikonka 24px + `--fs-micro` yozuv. Faol — `--gold` ikonka va yozuv, ikonka ostida yo'q, tepasida 2px × 20px oltin chiziq. Fon `--bg` 90% + blur, tepada `--line`. Pastdan `env(safe-area-inset-bottom)` qo'shiladi.

Owner uchun Kalendar va To'ylar sahifalarida o'ng pastda 56px yumaloq oltin "+" tugma (navigatsiyadan 16px yuqorida).

### 6.13 Moliya bloki (faqat owner yoki ochiq bo'lsa)

Tafsilot sahifasida alohida ro'yxat bloki: Kelishilgan narx · Toʻlangan · Qoldiq. Raqamlar Bodoni 22px tabular, o'ngga tekislangan. Qoldiq 0 bo'lsa `--success` "Toʻliq toʻlangan". Ostida ingichka progress (to'langan ulushi, `--gold`). Owner ko'radi: ko'rinish holati chipi ("Aʼzolarga yashirin" eye-off). A'zoga moliya yashirin bo'lsa — blok **umuman ko'rsatilmaydi** (qulf belgisi ham yo'q).

### 6.14 Xarita tugmalari

Tafsilotda joy bloki ostida ikkita ikkilamchi tugma yonma-yon: "Google Maps" va "Yandex". `Telegram.WebApp.openLink()` orqali ochiladi. Koordinata tasdiqlanmagan bo'lsa ustida `--warning` qator: "Joylashuv tasdiqlanmagan — manzil boʻyicha qidiriladi".

## 7. Ekranlar

### Bosh sahifa

```
JAHONORO                      [avatar]
Xayrli kech, Bahrullo

[ Keyingi toʻy chiptasi ]

Bu hafta 3   Bu oy 9   Kelgusi 14      ← 3 ustun, Bodoni raqam, ostida yozuv

Bugun / Shu hafta
┃ 13:00  ...
┃ 18:00  ...

Qarzdorlik (faqat owner)
Jami qoldiq      48 500 000 soʻm  ›

Soʻnggi oʻzgarishlar
...
```

"JAHONORO" — Bodoni 20px, harf oralig'i +0.06em (yagona istisno: bu logotip, yorliq emas). Salomlashuv vaqtga qarab: Xayrli tong / kun / kech.

### Kalendar
6.4 + tanlangan kun ro'yxati.

### Toʻylar (agenda)
Tepada qidiruv input (search ikonka) + filtr tugmasi (faol filtrlar soni bilan). Ostida segment: `Kelgusi | Oʻtgan`. Ro'yxat kun sarlavhalari (6.3) bilan guruhlangan. Pastga yetganda keyingi sahifa avtomatik yuklanadi.

### Toʻy tafsiloti
Tepada: tur chipi, sarlavha (`--fs-h1` Bodoni), sana. Keyin bloklar ketma-ket: **Vaqt jadvali** (vertikal timeline: Yigʻilish → Joʻnash → Soundcheck → Nahor → Abet → Boshlanish; har nuqtada soat Bodoni, chap tomonda ingichka chiziq bilan bog'langan; boshlanish oltin), **Joy** (nom, manzil, 6.14), **Moliya** (6.13), **Ijro** (eslatmalar, jihozlar, dress code), **Faqat siz uchun** (owner: mijoz, telefon, ichki izoh). Owner uchun tepada o'ngda "more" menyu: Tahrirlash, Eslatmani hozir yuborish, Toʻyni bekor qilish / Tiklash, Arxivlash.

### Toʻy formasi
6.7 bo'limlari. Saqlashda mojaro bo'lsa — sheet: "Bu kunda yana 1 ta toʻy bor" + mojaro qatori + [Baribir saqlash] (asosiy) / [Orqaga] (shaffof).

### Sozlamalar (owner)
Bloklar: Moliya ko'rinishi (switch) · Eslatmalar (necha soat oldin, oxirgi yuborilganlar holati) · A'zolar (ro'yxat, kutayotganlar tepada oltin belgi bilan) · Guruh (Guruhga tugma yuborish) · Tizim holati (cron oxirgi ishlagan vaqt, Telegram ulanishi).

### Telegram tashqarisida
Markazda logotip "JAHONORO" Bodoni 32px, ostida bir qator: "Bu ilova faqat JAHONORO guruhidagi tugma orqali ochiladi." Boshqa hech narsa.

## 8. Harakat

- Standart: 180ms, `cubic-bezier(0.2, 0.8, 0.2, 1)`. Sheet: 280ms. Sahifa almashinuvi: 160ms faqat opacity (siljishsiz).
- **Yagona orkestrlangan lahza:** sessiyada birinchi marta bosh sahifa ochilganda chipta paydo bo'ladi — kun raqami, keyin soatlar 8px pastdan, 60ms oraliq bilan joyiga tushadi; perforatsiya chapdan o'ngga "chiziladi" (300ms). Boshqa joylarda kirish animatsiyalari yo'q.
- Foydalanuvchi harakatiga javob: tugma bosilishi, sheet ochilishi, kalendar oy almashinuvi (12px siljish + opacity).
- `prefers-reduced-motion: reduce` — hamma animatsiya o'chadi, faqat opacity 100ms qoladi.
- Haptic (`Telegram.WebApp.HapticFeedback`): tab almashinuvi `impactOccurred('light')`; kalendar kun tanlash `selectionChanged()`; saqlandi `notificationOccurred('success')`; mojaro `'warning'`; xato `'error'`.

## 9. Telegram moslashuvi

- Ishga tushishda: `ready()`, `expand()`, `setHeaderColor('#101522')`, `setBackgroundColor` (mavzuga qarab `--bg`), `setBottomBarColor` mavjud bo'lsa.
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
- Gradient fonlar, neon, porlash effektlari, glassmorphism (blur faqat navigatsiya va kun sarlavhasida).
- Har kartaga bir xil kulrang soya.
- BOSH HARFLI yorliqlar, sarlavha ustidagi kichik "eyebrow" yozuvlar, tugmalarga "→".
- Raqamli bezak markerlar (01 / 02 / 03) — faqat haqiqiy ketma-ketlik (vaqt jadvali) bundan mustasno.
- Emoji navigatsiya, multfilm illyustratsiyalar, stok rasmlar.
- Tashqi CDN (shrift, ikonka, kutubxona) — hammasi o'zimizning hostingda.

## 12. Sifat tekshiruvi (har frontend bosqichi oxirida)

- [ ] Kontrast: matn ≥ 4.5:1, katta raqamlar ≥ 3:1 (ikkala mavzuda)
- [ ] Teginish maydonlari ≥ 44px
- [ ] 360px va 375×667 ekranda hech narsa kesilmaydi, gorizontal skroll yo'q
- [ ] Klaviatura fokusi ko'rinadi (`:focus-visible` oltin halqa)
- [ ] `prefers-reduced-motion` ishlaydi
- [ ] Skeleton → kontent o'tishida joy sakramaydi (CLS yo'q)
- [ ] Barcha ikonkalar bir xil chiziq qalinligida
- [ ] Hech qayerda hex rang yoki px shrift o'lchami komponent ichida qattiq yozilmagan (faqat tokenlar)
- [ ] O'zbekcha belgilar (ʻ) to'g'ri chiqadi
- [ ] Bosh sahifa bitta qarashda javob beradi: keyingi to'y qachon va qayerda

## Qarorlar jurnali

| Sana | Qaror | Sabab |
| --- | --- | --- |
| 2026-10-10 | Bodoni Moda + Onest | Konsert afishasi xarakteri + o'qilishi yaxshi interfeys shrifti |
| 2026-10-10 | Chipta — yagona yorqin element | Vaqt va joy — musiqachi uchun eng muhim ma'lumot |
