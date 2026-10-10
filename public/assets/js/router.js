// Hash router: #/, #/calendar, #/events, #/events/12, #/events/new, #/events/12/edit, #/settings.
// Sahifa moduli: export function render(ctx) → ixtiyoriy tozalash funksiyasi.

import { session } from './store.js';
import * as tg from './tg.js';
import { closeTopSheet, hasOpenSheet } from './ui/sheet.js';
import { h, clear, errorState } from './ui/components.js';

const ROUTES = [
  { pattern: /^\/$/, module: 'dashboard', tab: 'home' },
  { pattern: /^\/calendar$/, module: 'calendar', tab: 'calendar' },
  { pattern: /^\/events$/, module: 'events', tab: 'events' },
  { pattern: /^\/events\/new$/, module: 'event-form', inner: true, owner: true, parent: '/events' },
  { pattern: /^\/events\/(\d+)$/, module: 'event-detail', inner: true, keys: ['id'], parent: '/events' },
  { pattern: /^\/events\/(\d+)\/edit$/, module: 'event-form', inner: true, owner: true, keys: ['id'], parent: (p) => `/events/${p.id}` },
  { pattern: /^\/settings$/, module: 'settings', tab: 'settings', owner: true },
];

const listeners = new Set();
const scrollPositions = new Map();

let current = null;
let cleanup = null;
let renderToken = 0;
let depth = 0;
let viewRoot = null;

export function onRouteChange(fn) {
  listeners.add(fn);
  return () => listeners.delete(fn);
}

export function navigate(path, { replace = false } = {}) {
  const target = `#${path}`;
  if (location.hash === target) {
    render();
    return;
  }
  if (replace) {
    history.replaceState(null, '', target);
    render();
  } else {
    depth++;
    location.hash = target;
  }
}

export function back() {
  if (closeTopSheet()) return;
  if (depth > 0) {
    depth--;
    history.back();
    return;
  }
  const parent = current?.route.parent;
  const path = typeof parent === 'function' ? parent(current.params) : parent;
  navigate(path ?? '/', { replace: true });
}

function parse() {
  const raw = location.hash.replace(/^#/, '') || '/';
  const [path, qs = ''] = raw.split('?');
  const query = Object.fromEntries(new URLSearchParams(qs));
  for (const route of ROUTES) {
    const m = path.match(route.pattern);
    if (m) {
      const params = {};
      (route.keys ?? []).forEach((k, i) => { params[k] = m[i + 1]; });
      return { route, params, query, path };
    }
  }
  return null;
}

function updateBackButton() {
  if (hasOpenSheet() || current?.route.inner) {
    tg.showBack(back);
  } else {
    tg.hideBack();
  }
}

async function render() {
  const match = parse();
  if (!match || (match.route.owner && !session.isOwner())) {
    navigate('/', { replace: true });
    return;
  }

  if (current) scrollPositions.set(current.path + JSON.stringify(current.query), window.scrollY);
  if (cleanup) {
    try { cleanup(); } catch { /* */ }
    cleanup = null;
  }
  tg.hideMainButton();
  document.body.classList.remove('lock-swipes');
  tg.setVerticalSwipes(true);
  while (closeTopSheet()) { /* ochiq sheet'larni yopish */ }

  current = match;
  const token = ++renderToken;
  updateBackButton();
  for (const fn of listeners) fn(match);

  const view = h('main', { class: 'view', id: 'view', tabindex: '-1' });
  clear(viewRoot).appendChild(view);
  window.scrollTo(0, 0);

  try {
    const mod = await import(`./pages/${match.route.module}.js`);
    if (token !== renderToken) return;
    const ctx = {
      root: view,
      params: match.params,
      query: match.query,
      navigate,
      back,
      isCurrent: () => token === renderToken,
      restoreScroll: () => {
        const y = scrollPositions.get(match.path + JSON.stringify(match.query));
        if (y) window.scrollTo(0, y);
      },
    };
    const result = await mod.render(ctx);
    if (token === renderToken && typeof result === 'function') cleanup = result;
  } catch (e) {
    if (token !== renderToken) return;
    console.error(e);
    clear(view).appendChild(h('div', { class: 'page' }, errorState('Sahifani ochib bo‘lmadi.', () => render())));
  }
  requestAnimationFrame(() => view.classList.add('is-in'));
}

export function startRouter(root) {
  viewRoot = root;
  window.addEventListener('hashchange', render);
  document.addEventListener('sheetchange', updateBackButton);
  render();
}

export function currentRoute() {
  return current;
}
