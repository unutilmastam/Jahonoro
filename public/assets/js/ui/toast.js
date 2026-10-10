// Toast (6.9): pastki navigatsiya ustida, bitta qator, 2.4 s. Xatoda "Qayta urinish" bo'lsa yopilmaydi.

import { h, clear } from './components.js';
import { icon } from './icons.js';
import { haptic } from '../tg.js';

let root = null;
let timer = null;

function host() {
  root ??= document.getElementById('toast-root');
  return root;
}

function show(kind, message, { retry } = {}) {
  const el = host();
  if (!el) return;
  clearTimeout(timer);
  clear(el);
  const toast = h('div', { class: `toast toast--${kind}`, role: kind === 'error' ? 'alert' : 'status' },
    icon(kind === 'error' ? 'alert' : 'check', { size: 20, className: 'toast__icon' }),
    h('span', { class: 'toast__text' }, message),
    retry ? h('button', {
      type: 'button',
      class: 'toast__action',
      onClick: () => { hide(); retry(); },
    }, 'Qayta urinish') : null);
  el.appendChild(toast);
  requestAnimationFrame(() => toast.classList.add('is-visible'));
  if (!retry) {
    timer = setTimeout(hide, 2400);
  }
}

export function hide() {
  const el = host();
  const toast = el?.firstElementChild;
  if (!toast) return;
  toast.classList.remove('is-visible');
  setTimeout(() => toast.remove(), 200);
}

export const toast = {
  success(message) {
    haptic.notify('success');
    show('success', message);
  },
  error(message, opts) {
    haptic.notify('error');
    show('error', message, opts);
  },
};
