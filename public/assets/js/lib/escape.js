// HTML escape — faqat zarur joylarda (asosiy yo'l: textContent).
const MAP = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

export function escape(value) {
  return String(value ?? '').replace(/[&<>"']/g, (ch) => MAP[ch]);
}
