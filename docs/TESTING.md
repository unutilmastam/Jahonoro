# JAHONORO — sinov ro'yxati

Har bir holat: **qadam → kutilgan natija**. Telefondan, haqiqiy guruhda bajariladi. Avtomatik testlar (`php tests/run.php`, 70 ta) bu ro'yxatning server qismini allaqachon tekshiradi. Bu ro'yxat esa hostingda qo'lda o'tkaziladi.

Kerakli hisoblar: **owner** (siz), **a'zo** (guruhdagi bitta musiqachi), **begona** (guruhda yo'q odam).

## 1. Owner

1. `MINI_APP_LINK` ni oching → Bosh sahifa ochiladi, pastda 4 ta tab bor (Sozlamalar ham).
2. Kalendar va To‘ylar sahifasida o'ng pastda oltin "+" tugma bor.
3. Sozlamalar → Tizim holati → "Telegram ulanishi" **Ishlayapti**, "Cron" **Ishlayapti**.

## 1a. Guruhni ulash

1. Guruh ulanmagan holatda owner Bosh sahifasida "Guruh hali ulanmagan" qatori bor; a'zo ilovani ocholmaydi ("kirish huquqi yo‘q").
2. Sozlamalar → **Guruhni ulash** → **Telegram’da guruhni tanlash** → guruh tanlash oynasi ochiladi.
3. JAHONORO guruhini tanlab, botni admin qiling → ilovaga qayting → 3–10 soniyada "“…” ulandi", Guruh blokida guruh nomi va yashil "Ulangan".
4. Guruhda pin qilingan "📅 TO‘YLAR JADVALI" xabari paydo bo'ladi.
5. Boshqa odam botni o'z guruhiga qo'shsa → keyingi cron'da (5 daqiqa) bot o'sha guruhdan chiqib ketadi, ilova ulanmaydi.
6. **Boshqa guruhni ulash** bilan ikkinchi guruhni ulang → eski guruhda bo'lib yangi guruhda yo'q a'zo 10 daqiqa ichida kira olmaydi; eslatmalar yangi guruhga boradi.

## 2. A'zo

1. A'zo guruhdagi pin qilingan tugmani birinchi marta bosadi → "So‘rovingiz yuborildi. Guruh rahbari tasdiqlagach ilova ochiladi."
2. Owner: Sozlamalar → A'zolar → oltin nuqtali qator → **Tasdiqlash** → toast "A’zo tasdiqlandi".
3. A'zo qayta ochadi → Bosh sahifa ochiladi, faqat 3 ta tab bor, "+" tugma yo'q.
4. A'zo to'y tafsilotini ochadi → o'ng tepada "…" menyu yo'q, "Faqat siz uchun" bloki yo'q.

## 3. Begona

1. Guruhda yo'q odamga `MINI_APP_LINK` ni yuboring, u ochadi → "Sizda bu ilovaga kirish huquqi yo‘q. Guruh rahbariga murojaat qiling."
2. Owner: Sozlamalar → A'zolar ro'yxatida bu odam **yo'q**.
3. Faol a'zoni guruhdan chiqaring → 10 daqiqadan keyin ilovani qayta ochganda kirish yo'q.

## 4. Soxta initData

1. Kompyuter/telefon brauzerida `https://SUBDOMEN/` ni oching → "Bu ilova faqat JAHONORO guruhidagi tugma orqali ochiladi."
2. Brauzerda `https://SUBDOMEN/api/me` → `{"ok":false,"error":{"code":"unauthorized",…}}`.
3. `https://SUBDOMEN/api/events` → 401.

## 5. Moliya maxfiyligi

1. Owner: narxi 10 000 000, mijoz va ichki izohi bor to'y qo'shadi, 3 000 000 avans yozadi (izohi bilan).
2. A'zo shu to'yni ochadi → **Moliya bloki umuman yo'q** (qulf belgisi ham yo'q). Mijoz, telefon, izoh yo'q.
3. Owner: Sozlamalar → "Narxni a’zolarga ko‘rsatish" **yoqiladi**.
4. A'zo sahifani yangilaydi → Kelishilgan narx 10 000 000, To‘langan 3 000 000, Qoldiq 7 000 000. Avanslar ro'yxati va izohlar **yo'q**.
5. Owner to'y formasida Moliya ko‘rinishi → **Yashirin** → saqlaydi. A'zoda moliya yana yo'qoladi (global yoqiq bo'lsa ham).
6. Global sozlamani qayta o'chiring.

## 6. To'lov hisobi

1. Narxi 12 500 000 to'yga 2 500 000 va 1 500 000 avans → To‘langan 4 000 000, Qoldiq 8 500 000, progress chizig'i taxminan uchdan bir.
2. Avans summasiga `0` yozib saqlash → "Summa 0 dan katta bo‘lsin."
3. Bitta avansni axlat qutisi belgisi bilan o'chiring → tasdiqlang → qoldiq qayta hisoblanadi.
4. Qolgan summani to'liq to'lang → Qoldiq o'rnida yashil "To‘liq to‘langan".
5. Bosh sahifa → "Qarzdorlik" → Jami qoldiq to'g'ri.

## 7. Mojaro

1. Mavjud to'y bor kunga yangi to'y qo'shing → "Bu kunda yana 1 ta to‘y bor" sheet'i va o'sha to'y qatori chiqadi.
2. **Orqaga** → forma o'zgarmagan holda qoladi.
3. Qayta saqlang → **Baribir saqlash** → to'y saqlanadi.
4. 22:00 da boshlanadigan, 4 soatlik to'ydan keyin ertasi 00:30 ga to'y qo'shing → baribir mojaro chiqadi.

## 8. Eslatma takrori

1. Hozirdan 10 soat keyin boshlanadigan to'y qo'shing → 5 daqiqa ichida guruhga **bitta** xabar keladi ("TO‘Y BUGUN!" yoki "TO‘Y ERTAGA!").
2. Xabarda narx, avans, mijoz, telefon, ichki izoh **yo'q**. Pastida "🗺 Xaritada ochish" va "📅 TO‘YLAR JADVALI" tugmalari bor.
3. "📅 TO‘YLAR JADVALI" ni bosing → ilova to'g'ridan-to'g'ri shu to'y tafsilotida ochiladi.
4. To'yni hech narsa o'zgartirmasdan 2–3 marta saqlang → yangi xabar **kelmaydi**.
5. Sozlamalar → Eslatmalar ro'yxatida to'y "Yuborildi" holatida.

## 9. Eslatma xatosi

1. Botdan guruhdagi admin huquqini oling (yoki botni vaqtincha chiqaring).
2. Ertangi to'y qo'shing → 5–10 daqiqadan keyin Sozlamalar → Eslatmalar: "Xato" chipi va xato matni.
3. To'y menyusi → **Eslatmani hozir yuborish** → "Eslatma yuborilmadi. Botning guruhdagi huquqlarini tekshiring…" + "Qayta urinish".
4. Huquqni qaytaring → "Qayta urinish" → "Eslatma yuborildi".

## 10. Bekor qilish

1. To'y → "…" → **To‘yni bekor qilish** → tasdiqlang → "Bekor qilingan" chipi.
2. To'ylar ro'yxatida qator chizilgan va xira, o'ngda "Bekor qilingan".
3. Kalendarda shu kunda nuqta **yo'q**.
4. Ertangi to'yni bekor qilsangiz, eslatma **kelmaydi**.
5. "…" → **Tiklash** → holat "Rejada", eslatma qayta rejalashtiriladi.
6. "…" → **Arxivlash** → a'zo ro'yxatida to'y yo'q, tafsilot havolasi "To‘y topilmadi". Owner uni Filtr → Holat → Arxiv orqali topadi.

## 11. Vaqt o'zgarishi

1. Eslatmasi yuborilgan to'yning boshlanish vaqtini o'zgartiring → yangi vaqt uchun guruhga yana **bitta** eslatma keladi.
2. Eslatmasi hali yuborilmagan to'yning vaqtini o'zgartiring → eskisi bekor bo'ladi, faqat yangi vaqt bo'yicha bitta eslatma keladi.
3. Sozlamalar → Eslatmalar → "Guruhga necha soat oldin" ni 12 soatga o'zgartiring → kutilayotgan eslatmalarning vaqti yangilanadi.

## 12. Xarita

1. Koordinatasiz to'y (faqat to'yxona va tuman) → tafsilotda sariq qator "Joylashuv tasdiqlanmagan — manzil bo‘yicha qidiriladi". Google Maps / Yandex manzil matni bo'yicha qidiradi.
2. Formada Koordinata maydoniga Google Maps havolasini yoki `38.274200, 67.898100` ni qo'ying → ostida "Kenglik …, uzunlik …" chiqadi. **Joylashuv tasdiqlangan** ni yoqing → saqlang.
3. Tafsilotda sariq qator yo'q, xarita aynan o'sha nuqtani ochadi.
4. Noto'g'ri matn kiritilsa → "Koordinata tanilmadi" va switch yoqilmaydi.

## 13. Mobil

1. iPhone SE (yoki kichik Android 360px): hech bir sahifada gorizontal siljish yo'q, matnlar kesilmaydi.
2. Telegram'ning kunduzgi mavzusi → ilova och rangga o'tadi, chipta tungi rangda qoladi.
3. Telefon sozlamalarida "Harakatni kamaytirish" (Reduce motion) yoqilgan → chipta animatsiyasi yo'q, sheet'lar faqat xiralashib ochiladi.
4. Forma ochilganda Telegram'ning pastdagi oltin **Saqlash** tugmasi chiqadi, chiqib ketganda yo'qoladi.
5. Ichki sahifalarda Telegram'ning "Orqaga" tugmasi ishlaydi. Sheet ochiq bo'lsa, u avval sheet'ni yopadi.
6. Internetni o'chiring → amal bajaring → "Internet yo‘q. Ulanganda qayta urinib ko‘ring."
