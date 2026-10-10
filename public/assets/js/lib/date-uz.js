// O'zbekcha sana va vaqt. Hamma hisob Asia/Tashkent bo'yicha (UTC+5, yozgi vaqt yo'q).

export const TZ = 'Asia/Tashkent';
const OFFSET = '+05:00';

export const MONTHS = ['yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr'];
export const WEEKDAYS = ['dushanba', 'seshanba', 'chorshanba', 'payshanba', 'juma', 'shanba', 'yakshanba'];
export const WEEKDAYS_SHORT = ['Du', 'Se', 'Ch', 'Pa', 'Ju', 'Sh', 'Ya'];

const pad = (n) => String(n).padStart(2, '0');

/** Toshkentdagi bugungi sana: 'YYYY-MM-DD'. */
export function todayISO(now = new Date()) {
  return new Intl.DateTimeFormat('en-CA', { timeZone: TZ, year: 'numeric', month: '2-digit', day: '2-digit' }).format(now);
}

/** Toshkentdagi hozirgi soat (0–23). */
export function currentHour(now = new Date()) {
  return Number(new Intl.DateTimeFormat('en-GB', { timeZone: TZ, hour: '2-digit', hour12: false }).format(now)) % 24;
}

/** 'YYYY-MM-DD' → {y, m, d} */
export function parts(iso) {
  const [y, m, d] = String(iso).slice(0, 10).split('-').map(Number);
  return { y, m, d };
}

/** 'YYYY-MM-DD' yoki 'YYYY-MM-DD HH:MM:SS' → Date (Toshkent vaqti). */
export function toDate(value, time = '00:00') {
  const s = String(value);
  const datePart = s.slice(0, 10);
  const timePart = s.length > 10 ? s.slice(11, 16) : time;
  return new Date(`${datePart}T${timePart}:00${OFFSET}`);
}

/** Hafta kuni: 0 = dushanba … 6 = yakshanba. */
export function weekdayIndex(iso) {
  const { y, m, d } = parts(iso);
  return (new Date(Date.UTC(y, m - 1, d)).getUTCDay() + 6) % 7;
}

export function addDays(iso, days) {
  const { y, m, d } = parts(iso);
  const dt = new Date(Date.UTC(y, m - 1, d + days));
  return `${dt.getUTCFullYear()}-${pad(dt.getUTCMonth() + 1)}-${pad(dt.getUTCDate())}`;
}

export function daysBetween(fromIso, toIso) {
  const a = parts(fromIso);
  const b = parts(toIso);
  return Math.round((Date.UTC(b.y, b.m - 1, b.d) - Date.UTC(a.y, a.m - 1, a.d)) / 86400000);
}

/** 18-oktabr, shanba · yil joriydan farq qilsa: 3-yanvar 2027, shanba */
export function formatDate(iso, today = todayISO()) {
  const { y, m, d } = parts(iso);
  const year = y !== parts(today).y ? ` ${y}` : '';
  return `${d}-${MONTHS[m - 1]}${year}, ${WEEKDAYS[weekdayIndex(iso)]}`;
}

/** 18-oktabr (hafta kunisiz) */
export function formatDayMonth(iso, today = todayISO()) {
  const { y, m, d } = parts(iso);
  const year = y !== parts(today).y ? ` ${y}` : '';
  return `${d}-${MONTHS[m - 1]}${year}`;
}

/** Kun sarlavhasi: Bugun · Ertaga · Kecha · 18-oktabr, shanba */
export function dayLabel(iso, today = todayISO()) {
  const diff = daysBetween(today, iso);
  if (diff === 0) return 'Bugun';
  if (diff === 1) return 'Ertaga';
  if (diff === -1) return 'Kecha';
  return formatDate(iso, today);
}

/** Oy sarlavhasi: Oktabr 2026 */
export function monthTitle(year, month) {
  const name = MONTHS[month - 1];
  return `${name.charAt(0).toUpperCase()}${name.slice(1)} ${year}`;
}

export function monthKey(year, month) {
  return `${year}-${pad(month)}`;
}

/** HH:MM */
export function formatTime(value) {
  return value ? String(value).slice(0, 5) : '';
}

/**
 * Chipta uchun qolgan vaqt: "2 kun 4 soat qoldi", "3 soat ichida", "Bugun", "Hozir".
 * @returns {{text: string, today: boolean}}
 */
export function countdown(startsAt, now = new Date()) {
  const start = toDate(startsAt);
  const ms = start.getTime() - now.getTime();
  const isToday = String(startsAt).slice(0, 10) === todayISO(now);
  if (ms <= 0) return { text: 'Hozir', today: true };
  const hours = Math.floor(ms / 3600000);
  if (hours < 3) {
    if (hours === 0) {
      const minutes = Math.max(1, Math.floor(ms / 60000));
      return { text: `${minutes} daqiqa ichida`, today: true };
    }
    return { text: `${hours} soat ichida`, today: true };
  }
  if (isToday) return { text: 'Bugun', today: true };
  const days = Math.floor(hours / 24);
  const rest = hours % 24;
  if (days === 0) return { text: `${rest} soat qoldi`, today: false };
  return { text: rest > 0 ? `${days} kun ${rest} soat qoldi` : `${days} kun qoldi`, today: false };
}

/** So'nggi o'zgarishlar uchun: "hozirgina", "5 daqiqa oldin", "2 soat oldin", "kecha", "18-oktabr" */
export function timeAgo(dateTime, now = new Date()) {
  const ms = now.getTime() - toDate(dateTime).getTime();
  const minutes = Math.floor(ms / 60000);
  if (minutes < 1) return 'hozirgina';
  if (minutes < 60) return `${minutes} daqiqa oldin`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24 && String(dateTime).slice(0, 10) === todayISO(now)) return `${hours} soat oldin`;
  const diff = daysBetween(String(dateTime).slice(0, 10), todayISO(now));
  if (diff === 1) return 'kecha';
  return formatDayMonth(String(dateTime).slice(0, 10));
}

/** Salomlashuv vaqtga qarab */
export function greeting(now = new Date()) {
  const h = currentHour(now);
  if (h >= 5 && h < 11) return 'Xayrli tong';
  if (h >= 11 && h < 17) return 'Xayrli kun';
  return 'Xayrli kech';
}
