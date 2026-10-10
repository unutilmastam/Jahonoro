// Forma maydonlari (6.7): yorliq tepada, xato ostida.

import { h } from './components.js';
import { groupDigits } from '../lib/money.js';

let seq = 0;

/**
 * @param {string} label
 * @param {HTMLElement} control
 * @param {{hint?: string, name?: string}} opts
 */
export function field(label, control, { hint, name } = {}) {
  const id = control.id || `f${++seq}`;
  control.id = id;
  const error = h('p', { class: 'field__error', id: `${id}-err`, hidden: true });
  const wrap = h('div', { class: 'field', dataset: { field: name ?? control.name ?? '' } },
    h('label', { class: 'field__label', for: id }, label),
    control,
    hint ? h('p', { class: 'field__hint' }, hint) : null,
    error);
  wrap.setError = (message) => {
    wrap.classList.toggle('has-error', Boolean(message));
    error.hidden = !message;
    error.textContent = message || '';
    const input = wrap.querySelector('input, textarea, select');
    if (input) {
      if (message) {
        input.setAttribute('aria-invalid', 'true');
        input.setAttribute('aria-describedby', error.id);
      } else {
        input.removeAttribute('aria-invalid');
        input.removeAttribute('aria-describedby');
      }
    }
  };
  return wrap;
}

export function textInput(name, value = '', attrs = {}) {
  const el = h('input', { class: 'input', type: 'text', name, autocomplete: 'off', ...attrs });
  el.value = value ?? '';
  return el;
}

export function textArea(name, value = '', attrs = {}) {
  const el = h('textarea', { class: 'textarea', name, rows: '3', ...attrs });
  el.value = value ?? '';
  return el;
}

/** Pul maydoni: yozish paytida "12 500 000", o'ngda "so'm". */
export function moneyInput(name, value = '') {
  const input = h('input', {
    class: 'input num-input',
    type: 'text',
    inputmode: 'numeric',
    name,
    autocomplete: 'off',
    placeholder: '0',
  });
  input.value = value ? groupDigits(String(value).split('.')[0]) : '';
  input.addEventListener('input', () => {
    const before = input.value;
    const caretFromEnd = before.length - (input.selectionEnd ?? before.length);
    input.value = groupDigits(before);
    const pos = Math.max(0, input.value.length - caretFromEnd);
    try { input.setSelectionRange(pos, pos); } catch { /* */ }
  });
  const wrap = h('div', { class: 'input-wrap' }, input, h('span', { class: 'input-suffix' }, 'so‘m'));
  wrap.input = input;
  return wrap;
}

/** Xato xabarlarini maydonlarga yoyadi. */
export function applyErrors(form, details) {
  for (const f of form.querySelectorAll('.field')) f.setError?.('');
  let first = null;
  for (const [name, message] of Object.entries(details ?? {})) {
    const f = form.querySelector(`.field[data-field="${CSS.escape(name)}"]`);
    if (f) {
      f.setError(message);
      first ??= f;
    }
  }
  if (first) {
    first.scrollIntoView({ block: 'center', behavior: 'smooth' });
    first.querySelector('input, textarea, select')?.focus({ preventScroll: true });
  }
  return first !== null;
}
