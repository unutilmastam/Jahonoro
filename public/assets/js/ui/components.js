// DOM yordamchisi va umumiy komponentlar. Matn faqat textContent orqali kiradi.

import { icon } from './icons.js';
import { formatTime } from '../lib/date-uz.js';

export const TYPE_LABELS = {
  kelin: 'Kelin to‘yi',
  nikoh: 'Nikoh to‘yi',
  sunnat: 'Sunnat to‘yi',
  beshik: 'Beshik to‘yi',
  fotiha: 'Fotiha marosimi',
  korporativ: 'Korporativ tadbir',
  boshqa: 'Boshqa tadbir',
};

export const STATUS_LABELS = {
  planned: 'Rejada',
  confirmed: 'Tasdiqlangan',
  completed: 'O‘tkazilgan',
  cancelled: 'Bekor qilingan',
  archived: 'Arxiv',
};

/**
 * h('div', {class: 'a', onClick: fn, dataset: {id: 1}}, 'matn', child, [children])
 * Satr va son bolalar matn tuguni bo'ladi (HTML sifatida talqin qilinmaydi).
 */
export function h(tag, props = null, ...children) {
  const el = document.createElement(tag);
  if (props) {
    for (const [key, value] of Object.entries(props)) {
      if (value === null || value === undefined || value === false) continue;
      if (key === 'class') el.className = value;
      else if (key === 'dataset') Object.assign(el.dataset, value);
      else if (key === 'style' && typeof value === 'object') Object.assign(el.style, value);
      else if (key.startsWith('on') && typeof value === 'function') el.addEventListener(key.slice(2).toLowerCase(), value);
      else if (key === 'text') el.textContent = value;
      else if (value === true) el.setAttribute(key, '');
      else el.setAttribute(key, String(value));
    }
  }
  append(el, children);
  return el;
}

export function append(parent, children) {
  for (const child of children.flat(Infinity)) {
    if (child === null || child === undefined || child === false) continue;
    parent.appendChild(child instanceof Node ? child : document.createTextNode(String(child)));
  }
  return parent;
}

export function clear(el) {
  while (el.firstChild) el.removeChild(el.firstChild);
  return el;
}

export function spinner() {
  return h('span', { class: 'spinner', 'aria-hidden': 'true' });
}

/**
 * Tugma. variant: primary | secondary | ghost | danger. size: md | sm
 */
export function button(label, { variant = 'secondary', size = 'md', iconName, onClick, type = 'button', block = false, ariaLabel } = {}) {
  const btn = h('button', {
    type,
    class: `btn btn--${variant}${size === 'sm' ? ' btn--sm' : ''}${block ? ' btn--block' : ''}`,
    'aria-label': ariaLabel,
  },
  iconName ? icon(iconName, { size: size === 'sm' ? 18 : 20 }) : null,
  label ? h('span', { class: 'btn__label' }, label) : null);
  if (onClick) {
    btn.addEventListener('click', (e) => onClick(e, btn));
  }
  return btn;
}

/** Yuklanish holati: matn o'rnida spinner, kenglik o'zgarmaydi. */
export function setLoading(btn, loading) {
  if (!btn) return;
  btn.classList.toggle('is-loading', loading);
  btn.disabled = loading;
  if (loading && !btn.querySelector('.spinner')) btn.appendChild(spinner());
  if (!loading) btn.querySelector('.spinner')?.remove();
}

export function iconButton(name, label, onClick, extraClass = '') {
  return h('button', { type: 'button', class: `icon-btn ${extraClass}`.trim(), 'aria-label': label, onClick }, icon(name, { size: 24 }));
}

export function typeChip(type) {
  return h('span', { class: `chip chip--type t-${type}` },
    h('span', { class: 'chip__dot', 'aria-hidden': 'true' }),
    TYPE_LABELS[type] ?? TYPE_LABELS.boshqa);
}

export function statusChip(status) {
  return h('span', { class: `chip chip--status s-${status}` }, STATUS_LABELS[status] ?? status);
}

/** Guruhlangan ro'yxat bloki (sarlavhasi ixtiyoriy). */
export function listBlock(title, ...children) {
  return h('section', { class: 'block-group' },
    title ? h('h2', { class: 'block-title' }, title) : null,
    h('div', { class: 'block' }, ...children));
}

/**
 * To'y qatori (6.2).
 * @param {object} ev — FinanceGuard row
 * @param {{today?: string, onClick?: Function, href?: string}} opts
 */
export function eventRow(ev, { today, href } = {}) {
  const cancelled = ev.status === 'cancelled';
  const isToday = today && ev.event_date === today;
  const place = [ev.venue_name, ev.district].filter(Boolean).join(', ');
  return h('a', {
    class: `event-row t-${ev.event_type}${cancelled ? ' is-cancelled' : ''}${isToday ? ' is-today' : ''}`,
    href: href ?? `#/events/${ev.id}`,
  },
  h('span', { class: 'event-row__bar', 'aria-hidden': 'true' }),
  h('span', { class: 'event-row__time num' }, formatTime(ev.start_time)),
  h('span', { class: 'event-row__body' },
    h('span', { class: 'event-row__title' }, ev.title),
    place ? h('span', { class: 'event-row__place' }, place) : null),
  cancelled ? statusChip('cancelled') : null,
  h('span', { class: 'event-row__chevron' }, icon('chevron-right', { size: 20 })));
}

export function dayHeader(label) {
  return h('h3', { class: 'day-header' }, label);
}

/** Bo'sh holat (6.10) */
export function emptyState({ iconName = 'calendar', title, text, action }) {
  return h('div', { class: 'empty' },
    icon(iconName, { size: 32, className: 'empty__icon' }),
    h('p', { class: 'empty__title' }, title),
    text ? h('p', { class: 'empty__text' }, text) : null,
    action ?? null);
}

/** Xato holati — sahifa yuklanmasa. */
export function errorState(message, onRetry) {
  return emptyState({
    iconName: 'alert',
    title: 'Yuklab bo‘lmadi',
    text: message,
    action: onRetry ? button('Qayta urinish', { variant: 'secondary', iconName: 'refresh', onClick: onRetry }) : null,
  });
}

/**
 * Segment boshqaruvi.
 * @param {{value: string, label: string, iconName?: string}[]} options
 */
export function segment(options, value, onChange, { label } = {}) {
  const root = h('div', { class: 'segment', role: 'radiogroup', 'aria-label': label });
  const buttons = options.map((opt) => {
    const b = h('button', {
      type: 'button',
      class: 'segment__item',
      role: 'radio',
      'aria-checked': String(opt.value === value),
      onClick: () => {
        if (root.dataset.value === opt.value) return;
        select(opt.value);
        onChange(opt.value);
      },
    }, opt.iconName ? icon(opt.iconName, { size: 18 }) : null, h('span', null, opt.label));
    b.dataset.value = opt.value;
    return b;
  });
  function select(v) {
    root.dataset.value = v;
    for (const b of buttons) b.setAttribute('aria-checked', String(b.dataset.value === v));
  }
  append(root, buttons);
  select(value);
  root.setValue = select;
  return root;
}

/** Switch (checkbox asosida). */
export function switchControl(checked, onChange, label) {
  const input = h('input', { type: 'checkbox', class: 'switch__input', role: 'switch', 'aria-label': label });
  input.checked = Boolean(checked);
  input.addEventListener('change', () => onChange(input.checked, input));
  return h('label', { class: 'switch' }, input, h('span', { class: 'switch__track', 'aria-hidden': 'true' }, h('span', { class: 'switch__thumb' })));
}

/** Blok ichidagi oddiy qator: chapda nom, o'ngda qiymat yoki boshqaruv. */
export function row(label, value, { href, onClick, iconName, sub, trailing } = {}) {
  const tag = href ? 'a' : (onClick ? 'button' : 'div');
  const el = h(tag, {
    class: `row${href || onClick ? ' row--action' : ''}`,
    href,
    type: tag === 'button' ? 'button' : null,
    onClick,
  },
  iconName ? h('span', { class: 'row__icon' }, icon(iconName, { size: 20 })) : null,
  h('span', { class: 'row__main' },
    h('span', { class: 'row__label' }, label),
    sub ? h('span', { class: 'row__sub' }, sub) : null),
  value !== null && value !== undefined && value !== '' ? h('span', { class: 'row__value' }, value) : null,
  trailing ?? null,
  href || onClick ? h('span', { class: 'row__chevron' }, icon('chevron-right', { size: 20 })) : null);
  return el;
}

/** Avatar: ismning bosh harfi. */
export function avatar(name) {
  const letter = String(name || '?').trim().charAt(0).toUpperCase() || '?';
  return h('span', { class: 'avatar', 'aria-hidden': 'true' }, letter);
}
