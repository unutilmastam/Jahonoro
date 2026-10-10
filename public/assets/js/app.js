// Ilova kirish nuqtasi: Telegram sozlash → kirish → navigatsiya → router.

import * as tg from './tg.js';
import { login } from './auth.js';
import { session } from './store.js';
import { startRouter, onRouteChange, navigate } from './router.js';
import { h, clear } from './ui/components.js';
import { icon } from './ui/icons.js';
import { renderGate } from './pages/fallback.js';

const TABS = [
  { id: 'home', path: '/', label: 'Bosh sahifa', iconName: 'home' },
  { id: 'calendar', path: '/calendar', label: 'Kalendar', iconName: 'calendar' },
  { id: 'events', path: '/events', label: 'To‘ylar', iconName: 'list' },
  { id: 'settings', path: '/settings', label: 'Sozlamalar', iconName: 'settings', owner: true },
];

function renderTabbar(nav) {
  clear(nav);
  const items = TABS.filter((t) => !t.owner || session.isOwner()).map((t) => {
    const a = h('a', { class: 'tabbar__item', href: `#${t.path}`, dataset: { tab: t.id } },
      icon(t.iconName, { size: 24 }),
      h('span', { class: 'tabbar__label' }, t.label));
    a.addEventListener('click', (e) => {
      e.preventDefault();
      if (!a.classList.contains('is-active')) tg.haptic.impact('light');
      navigate(t.path, { replace: true });
    });
    return a;
  });
  nav.appendChild(h('div', { class: 'tabbar__inner' }, items));
  nav.hidden = false;

  onRouteChange(({ route }) => {
    const hidden = Boolean(route.inner);
    nav.hidden = hidden;
    document.body.classList.toggle('no-tabbar', hidden);
    for (const a of nav.querySelectorAll('.tabbar__item')) {
      const active = a.dataset.tab === route.tab;
      a.classList.toggle('is-active', active);
      if (active) a.setAttribute('aria-current', 'page');
      else a.removeAttribute('aria-current');
    }
  });
}

async function boot() {
  tg.init();
  const app = document.getElementById('app');
  const nav = document.getElementById('tabbar');

  const result = await login();
  if (!result.ok) {
    nav.hidden = true;
    document.body.classList.add('no-tabbar');
    renderGate(app, { message: result.message, reason: result.reason, onRetry: () => location.reload() });
    return;
  }

  // Guruh xabaridagi tugma: ?startapp=e12 → to'y tafsiloti
  const start = tg.startParam();
  const m = /^e(\d{1,10})$/.exec(start);
  if (m && (!location.hash || location.hash === '#/' || location.hash === '#')) {
    history.replaceState(null, '', `#/events/${m[1]}`);
  }

  renderTabbar(nav);
  startRouter(app);
}

boot();
