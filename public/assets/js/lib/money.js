// So'm formatlash: 12 500 000 so'm (guruh ajratuvchi — U+202F tor bo'sh joy).

const NNBSP = ' ';

/** "12500000.00" | 12500000 → "12 500 000" */
export function formatAmount(value) {
  if (value === null || value === undefined || value === '') return '';
  const [intPart, frac = ''] = String(value).replace(/[^\d.-]/g, '').split('.');
  const negative = intPart.startsWith('-');
  const digits = intPart.replace('-', '').replace(/^0+(?=\d)/, '');
  const grouped = digits.replace(/\B(?=(\d{3})+(?!\d))/g, NNBSP);
  const cents = frac.replace(/0+$/, '');
  return `${negative ? '−' : ''}${grouped}${cents ? `,${cents.padEnd(2, '0')}` : ''}`;
}

export function formatMoney(value) {
  const amount = formatAmount(value);
  return amount ? `${amount}${NNBSP}so‘m` : '';
}

/** Input uchun: yozish paytida guruhlash, faqat raqamlar. */
export function groupDigits(raw) {
  const digits = String(raw).replace(/\D/g, '').replace(/^0+(?=\d)/, '').slice(0, 12);
  return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

/** "12 500 000" → "12500000" (bo'sh bo'lsa null) */
export function parseAmount(raw) {
  const digits = String(raw ?? '').replace(/\D/g, '');
  return digits === '' ? null : digits.replace(/^0+(?=\d)/, '');
}

/** Tiyinlarsiz butun so'm soni (progress hisoblash uchun). */
export function toNumber(value) {
  const n = Number.parseFloat(String(value ?? '0'));
  return Number.isFinite(n) ? n : 0;
}
