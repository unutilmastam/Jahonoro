// To'ylar (agenda): qidiruv, filtr sheet, Kelgusi | O'tgan, kun sarlavhalari, cheksiz yuklash.

import * as api from '../api.js';
import { session } from '../store.js';
import { h, clear, eventRow, dayHeader, segment, emptyState, errorState, button, TYPE_LABELS, STATUS_LABELS } from '../ui/components.js';
import { icon } from '../ui/icons.js';
import { openSheet } from '../ui/sheet.js';
import { rowsSkeleton, delayedSkeleton } from '../ui/skeleton.js';
import { todayISO, dayLabel } from '../lib/date-uz.js';

export async function render(ctx) {
  const today = todayISO();
  const isOwner = session.isOwner();
  const state = {
    scope: ctx.query.scope === 'past' ? 'past' : 'upcoming',
    q: ctx.query.q ?? '',
    type: ctx.query.type ?? '',
    status: ctx.query.status ?? '',
    page: 1,
    hasMore: false,
    loading: false,
    items: [],
    token: 0,
  };

  const searchInput = h('input', {
    class: 'input input--with-icon',
    type: 'search',
    inputmode: 'search',
    enterkeyhint: 'search',
    placeholder: 'Qidirish: nom, to‘yxona, tuman',
    'aria-label': 'Qidirish',
    maxlength: '100',
    autocomplete: 'off',
  });
  searchInput.value = state.q;

  const filterCount = h('span', { class: 'count-badge', hidden: true });
  const filterBtn = h('button', { type: 'button', class: 'btn btn--secondary filter-btn', 'aria-label': 'Filtrlar', onClick: openFilters },
    icon('filter', { size: 20 }), filterCount);

  const list = h('div', { class: 'agenda' });
  const sentinel = h('div', { class: 'agenda__sentinel', 'aria-hidden': 'true' });
  const seg = segment([
    { value: 'upcoming', label: 'Kelgusi' },
    { value: 'past', label: 'O‘tgan' },
  ], state.scope, (v) => { state.scope = v; reload(); }, { label: 'Davr' });

  const page = h('div', { class: 'page page--events' },
    h('h1', { class: 'page-title' }, 'To‘ylar'),
    h('div', { class: 'search-bar' },
      h('div', { class: 'input-wrap search-bar__input' }, h('span', { class: 'input-icon' }, icon('search', { size: 20 })), searchInput),
      filterBtn),
    h('div', { class: 'events-seg' }, seg),
    list,
    sentinel);
  ctx.root.appendChild(page);

  if (isOwner) {
    ctx.root.appendChild(h('a', { class: 'fab', href: '#/events/new', 'aria-label': 'To‘y qo‘shish' }, icon('plus', { size: 26 })));
  }

  let debounce = null;
  searchInput.addEventListener('input', () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
      state.q = searchInput.value.trim();
      reload();
    }, 350);
  });
  searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      clearTimeout(debounce);
      state.q = searchInput.value.trim();
      searchInput.blur();
      reload();
    }
  });

  function activeFilters() {
    return (state.type ? 1 : 0) + (state.status ? 1 : 0);
  }

  function syncUrl() {
    const qs = new URLSearchParams();
    if (state.scope === 'past') qs.set('scope', 'past');
    if (state.q) qs.set('q', state.q);
    if (state.type) qs.set('type', state.type);
    if (state.status) qs.set('status', state.status);
    const s = qs.toString();
    history.replaceState(null, '', `#/events${s ? `?${s}` : ''}`);
    const n = activeFilters();
    filterCount.hidden = n === 0;
    filterCount.textContent = String(n);
    filterBtn.setAttribute('aria-label', n ? `Filtrlar: ${n} ta faol` : 'Filtrlar');
  }

  function query() {
    return { scope: state.scope, q: state.q, type: state.type, status: state.status, page: state.page };
  }

  function draw() {
    clear(list);
    if (state.items.length === 0) {
      const filtered = state.q || activeFilters();
      list.appendChild(filtered
        ? emptyState({
          iconName: 'search',
          title: 'Hech narsa topilmadi',
          text: 'Boshqa so‘z yoki filtr bilan urinib ko‘ring.',
          action: button('Filtrlarni tozalash', { variant: 'secondary', onClick: clearFilters }),
        })
        : emptyState({
          iconName: 'calendar',
          title: state.scope === 'past' ? 'O‘tgan to‘ylar yo‘q' : 'Kelgusi to‘ylar yo‘q',
          text: state.scope === 'past' ? 'O‘tkazilgan to‘ylar shu yerda saqlanadi.' : 'Yangi buyurtma kelganda shu yerda ko‘rinadi.',
          action: isOwner && state.scope !== 'past' ? button('To‘y qo‘shish', { variant: 'primary', iconName: 'plus', onClick: () => ctx.navigate('/events/new') }) : null,
        }));
      return;
    }
    let lastDate = null;
    let block = null;
    for (const ev of state.items) {
      if (ev.event_date !== lastDate) {
        lastDate = ev.event_date;
        block = h('div', { class: 'block' });
        list.append(dayHeader(dayLabel(ev.event_date, today)), block);
      }
      block.appendChild(eventRow(ev, { today }));
    }
  }

  async function fetchPage() {
    const token = ++state.token;
    state.loading = true;
    try {
      const data = await api.get('/events', query());
      if (token !== state.token || !ctx.isCurrent()) return;
      state.items = state.page === 1 ? data.items : state.items.concat(data.items);
      state.hasMore = data.has_more;
      draw();
    } finally {
      if (token === state.token) state.loading = false;
    }
  }

  async function reload() {
    syncUrl();
    state.page = 1;
    state.hasMore = false;
    const cached = api.peek('/events', query());
    if (cached) {
      state.items = cached.items;
      state.hasMore = cached.has_more;
      draw();
    }
    const stop = cached ? () => {} : delayedSkeleton(list, () => h('div', null, dayHeader(' '), rowsSkeleton(4)));
    try {
      await fetchPage();
    } catch (e) {
      if (ctx.isCurrent() && !cached) clear(list).appendChild(errorState(e.message, reload));
    } finally {
      stop();
    }
  }

  async function loadMore() {
    if (state.loading || !state.hasMore) return;
    state.page++;
    try {
      await fetchPage();
    } catch {
      state.page--;
    }
  }

  function clearFilters() {
    state.q = '';
    state.type = '';
    state.status = '';
    searchInput.value = '';
    reload();
  }

  function openFilters() {
    let type = state.type;
    let status = state.status;
    const typeChoices = h('div', { class: 'choice-chips' });
    const statusChoices = h('div', { class: 'choice-chips' });
    const drawChoices = () => {
      clear(typeChoices);
      clear(statusChoices);
      for (const [value, label] of [['', 'Hammasi'], ...Object.entries(TYPE_LABELS)]) {
        typeChoices.appendChild(h('button', {
          type: 'button',
          class: `choice${value ? ` t-${value}` : ''}`,
          'aria-pressed': String(type === value),
          onClick: () => { type = value; drawChoices(); },
        }, value ? h('span', { class: 'chip__dot', 'aria-hidden': 'true' }) : null, label));
      }
      const statuses = ['planned', 'confirmed', 'completed', 'cancelled', ...(isOwner ? ['archived'] : [])];
      for (const value of ['', ...statuses]) {
        statusChoices.appendChild(h('button', {
          type: 'button',
          class: 'choice',
          'aria-pressed': String(status === value),
          onClick: () => { status = value; drawChoices(); },
        }, value ? STATUS_LABELS[value] : 'Hammasi'));
      }
    };
    drawChoices();
    const apply = button('Qo‘llash', {
      variant: 'primary',
      block: true,
      onClick: () => {
        state.type = type;
        state.status = status;
        sheet.close();
        reload();
      },
    });
    const reset = button('Tozalash', {
      variant: 'ghost',
      block: true,
      onClick: () => { type = ''; status = ''; drawChoices(); },
    });
    const sheet = openSheet({
      title: 'Filtrlar',
      content: h('div', null,
        h('div', { class: 'sheet__section' }, h('p', { class: 'sheet__label' }, 'To‘y turi'), typeChoices),
        h('div', { class: 'sheet__section' }, h('p', { class: 'sheet__label' }, 'Holat'), statusChoices)),
      footer: h('div', { class: 'stack-buttons' }, apply, reset),
    });
  }

  const observer = new IntersectionObserver((entries) => {
    if (entries.some((e) => e.isIntersecting)) loadMore();
  }, { rootMargin: '400px 0px' });
  observer.observe(sentinel);

  await reload();
  ctx.restoreScroll();

  return () => {
    observer.disconnect();
    clearTimeout(debounce);
  };
}
