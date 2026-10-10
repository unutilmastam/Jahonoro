// Skeleton (6.11): haqiqiy komponent shaklida. 300 ms dan tez yuklansa umuman ko'rsatilmaydi.

import { h, clear } from './components.js';

const bar = (cls) => h('span', { class: `sk ${cls}` });

export function ticketSkeleton() {
  return h('div', { class: 'hero hero--skeleton', 'aria-hidden': 'true' },
    h('div', { class: 'hero__top' }, bar('sk--chip'), bar('sk--line sk--w40')),
    h('div', { class: 'hero__date' }, bar('sk--hero')),
    bar('sk--line sk--w70'),
    h('div', { class: 'hero__times' }, bar('sk--time'), bar('sk--time'), bar('sk--time')));
}

export function rowsSkeleton(count = 3) {
  const rows = [];
  for (let i = 0; i < count; i++) {
    rows.push(h('div', { class: 'event-row event-row--skeleton', 'aria-hidden': 'true' },
      h('span', { class: 'event-row__bar' }),
      bar('sk--rowtime'),
      h('span', { class: 'event-row__body' }, bar('sk--line sk--w70'), bar('sk--line sk--w40'))));
  }
  return h('div', { class: 'block' }, rows);
}

export function blockSkeleton(lines = 3) {
  const rows = [];
  for (let i = 0; i < lines; i++) {
    rows.push(h('div', { class: 'row', 'aria-hidden': 'true' }, bar('sk--line sk--w60')));
  }
  return h('div', { class: 'block' }, rows);
}

/**
 * Yuklanish 300 ms dan oshsa konteynerga skeleton qo'yadi.
 * @returns {Function} bekor qilish (kontent tayyor bo'lganda chaqiriladi)
 */
export function delayedSkeleton(container, build) {
  const timer = setTimeout(() => {
    clear(container);
    container.appendChild(build());
    container.setAttribute('aria-busy', 'true');
  }, 300);
  return () => {
    clearTimeout(timer);
    container.removeAttribute('aria-busy');
  };
}
