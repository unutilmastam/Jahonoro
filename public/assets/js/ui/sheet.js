// Bottom sheet (6.8): pastdan chiqadi, tutqich, pastga tortib yopiladi, BackButton yopadi.

import { h, iconButton } from './components.js';
import * as tg from '../tg.js';

const stack = [];

/** Router BackButton bosilganda avval sheet'ni yopadi. */
export function closeTopSheet() {
  const top = stack[stack.length - 1];
  if (top) {
    top.close();
    return true;
  }
  return false;
}

export function hasOpenSheet() {
  return stack.length > 0;
}

/**
 * @param {{title?: string, content: Node, footer?: Node, onClose?: Function, label?: string}} opts
 * @returns {{close: Function, el: HTMLElement}}
 */
export function openSheet({ title, content, footer, onClose, label }) {
  const host = document.getElementById('sheet-root');
  const previouslyFocused = document.activeElement;

  const panel = h('div', {
    class: 'sheet',
    role: 'dialog',
    'aria-modal': 'true',
    'aria-label': label ?? title ?? 'Oyna',
    tabindex: '-1',
  },
  h('div', { class: 'sheet__handle', 'aria-hidden': 'true' }),
  title ? h('div', { class: 'sheet__head' },
    h('h2', { class: 'sheet__title' }, title),
    iconButton('x', 'Yopish', () => api.close(), 'sheet__close')) : null,
  h('div', { class: 'sheet__body' }, content),
  footer ? h('div', { class: 'sheet__footer' }, footer) : null);

  const backdrop = h('div', { class: 'sheet-backdrop', onClick: () => api.close() });
  const wrap = h('div', { class: 'sheet-layer' }, backdrop, panel);
  host.appendChild(wrap);
  document.body.classList.add('has-sheet');
  tg.setVerticalSwipes(false);

  requestAnimationFrame(() => {
    wrap.classList.add('is-open');
    panel.focus({ preventScroll: true });
  });

  // Pastga tortib yopish
  let startY = null;
  let delta = 0;
  const onStart = (e) => {
    const body = panel.querySelector('.sheet__body');
    if (body && body.scrollTop > 0 && !e.target.closest('.sheet__handle, .sheet__head')) return;
    startY = e.touches[0].clientY;
    delta = 0;
    panel.style.transition = 'none';
  };
  const onMove = (e) => {
    if (startY === null) return;
    delta = Math.max(0, e.touches[0].clientY - startY);
    panel.style.transform = `translateY(${delta}px)`;
  };
  const onEnd = () => {
    if (startY === null) return;
    panel.style.transition = '';
    panel.style.transform = '';
    if (delta > 90) api.close();
    startY = null;
  };
  panel.addEventListener('touchstart', onStart, { passive: true });
  panel.addEventListener('touchmove', onMove, { passive: true });
  panel.addEventListener('touchend', onEnd);

  const onKey = (e) => {
    if (e.key === 'Escape') api.close();
  };
  document.addEventListener('keydown', onKey);

  let closed = false;
  const api = {
    el: panel,
    close() {
      if (closed) return;
      closed = true;
      document.removeEventListener('keydown', onKey);
      const i = stack.indexOf(api);
      if (i >= 0) stack.splice(i, 1);
      wrap.classList.remove('is-open');
      wrap.classList.add('is-closing');
      const finish = () => {
        wrap.remove();
        if (stack.length === 0) {
          document.body.classList.remove('has-sheet');
          tg.setVerticalSwipes(!document.body.classList.contains('lock-swipes'));
        }
      };
      const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      setTimeout(finish, reduce ? 100 : 280);
      if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
        previouslyFocused.focus({ preventScroll: true });
      }
      document.dispatchEvent(new CustomEvent('sheetchange'));
      onClose?.();
    },
  };
  stack.push(api);
  document.dispatchEvent(new CustomEvent('sheetchange'));
  tg.haptic.impact('light');
  return api;
}

/**
 * Tasdiqlash dialogi.
 * @returns {Promise<boolean>}
 */
export function confirmSheet({ title, text, confirmLabel, danger = false, cancelLabel = 'Yopish' }) {
  return new Promise((resolve) => {
    let result = false;
    const confirm = h('button', {
      type: 'button',
      class: `btn ${danger ? 'btn--danger' : 'btn--primary'} btn--block`,
      onClick: () => { result = true; sheet.close(); },
    }, h('span', { class: 'btn__label' }, confirmLabel));
    const cancel = h('button', {
      type: 'button',
      class: 'btn btn--ghost btn--block',
      onClick: () => sheet.close(),
    }, h('span', { class: 'btn__label' }, cancelLabel));
    const sheet = openSheet({
      title,
      content: text ? h('p', { class: 'sheet__text' }, text) : h('span'),
      footer: h('div', { class: 'stack-buttons' }, confirm, cancel),
      onClose: () => resolve(result),
    });
  });
}
