// Telegram tashqarisida yoki kirish yo'q bo'lganda: markazda logotip va bitta qator.

import { h, clear, button, logoMark } from '../ui/components.js';

/**
 * @param {HTMLElement} root
 * @param {{message: string, reason?: string, onRetry?: Function}} opts
 */
export function renderGate(root, { message, reason, onRetry }) {
  clear(root);
  const mark = logoMark({ size: 72 });
  mark.classList.add('gate__mark');
  root.appendChild(h('main', { class: 'gate' },
    mark,
    h('h1', { class: 'gate__logo' }, 'JAHONORO'),
    h('p', { class: 'gate__text' }, message),
    reason === 'offline' && onRetry
      ? button('Qayta urinish', { variant: 'secondary', iconName: 'refresh', onClick: onRetry })
      : null));
}

export function render(ctx) {
  renderGate(ctx.root, { message: 'Bu ilova faqat JAHONORO guruhidagi tugma orqali ochiladi.' });
}
