// Kalendar (6.4): oy to'ri, tur rangidagi nuqtalar, tanlangan kun ro'yxati, suring bilan oy almashadi.

import * as api from '../api.js';
import { session } from '../store.js';
import * as tg from '../tg.js';
import { h, clear, eventRow, iconButton, errorState } from '../ui/components.js';
import { icon } from '../ui/icons.js';
import { openSheet } from '../ui/sheet.js';
import { rowsSkeleton } from '../ui/skeleton.js';
import { todayISO, parts, monthTitle, monthKey, weekdayIndex, addDays, WEEKDAYS_SHORT, MONTHS, dayLabel } from '../lib/date-uz.js';

const pad = (n) => String(n).padStart(2, '0');

function daysInMonth(y, m) {
  return new Date(Date.UTC(y, m, 0)).getUTCDate();
}

export async function render(ctx) {
  const today = todayISO();
  const t = parts(today);
  const initial = /^\d{4}-\d{2}$/.test(ctx.query.month ?? '') ? ctx.query.month.split('-').map(Number) : [t.y, t.m];
  const state = {
    y: initial[0],
    m: initial[1],
    selected: /^\d{4}-\d{2}-\d{2}$/.test(ctx.query.day ?? '') ? ctx.query.day : (initial[0] === t.y && initial[1] === t.m ? today : `${initial[0]}-${pad(initial[1])}-01`),
    items: [],
  };

  const titleBtn = h('button', { type: 'button', class: 'cal__title', 'aria-label': 'Oy va yilni tanlash' });
  const grid = h('div', { class: 'cal__grid', role: 'grid' });
  const dayList = h('section', { class: 'cal__day', 'aria-live': 'polite' });
  const page = h('div', { class: 'page page--calendar' },
    h('div', { class: 'cal' },
      h('div', { class: 'cal__head' },
        titleBtn,
        h('div', { class: 'cal__nav' },
          iconButton('chevron-left', 'Oldingi oy', () => shift(-1)),
          iconButton('chevron-right', 'Keyingi oy', () => shift(1)))),
      h('div', { class: 'cal__weekdays', 'aria-hidden': 'true' }, WEEKDAYS_SHORT.map((d) => h('span', { class: 'cal__weekday' }, d))),
      h('div', { class: 'cal__viewport' }, grid)),
    dayList);
  ctx.root.appendChild(page);

  if (session.isOwner()) {
    ctx.root.appendChild(h('a', {
      class: 'fab',
      href: '#/events/new',
      'aria-label': 'To‘y qo‘shish',
      onClick: (e) => {
        e.preventDefault();
        ctx.navigate(`/events/new?date=${state.selected}`);
      },
    }, icon('plus', { size: 26 })));
  }

  titleBtn.addEventListener('click', openPicker);

  function byDate() {
    const map = new Map();
    for (const ev of state.items) {
      if (!map.has(ev.event_date)) map.set(ev.event_date, []);
      map.get(ev.event_date).push(ev);
    }
    return map;
  }

  function drawGrid(direction = 0) {
    clear(titleBtn).append(monthTitle(state.y, state.m), icon('chevron-down', { size: 20 }));
    const first = `${state.y}-${pad(state.m)}-01`;
    const start = addDays(first, -weekdayIndex(first));
    const total = Math.ceil((weekdayIndex(first) + daysInMonth(state.y, state.m)) / 7) * 7;
    const events = byDate();
    clear(grid);
    for (let i = 0; i < total; i++) {
      const date = addDays(start, i);
      const p = parts(date);
      const inMonth = p.m === state.m;
      const dayEvents = (events.get(date) ?? []).filter((ev) => ev.status !== 'cancelled');
      const dots = h('span', { class: 'cal__dots', 'aria-hidden': 'true' });
      const shown = dayEvents.length > 3 ? dayEvents.slice(0, 2) : dayEvents;
      for (const ev of shown) dots.appendChild(h('span', { class: `cal__dot t-${ev.event_type}` }));
      if (dayEvents.length > 3) dots.appendChild(h('span', { class: 'cal__more' }, `+${dayEvents.length - 2}`));

      const label = `${p.d}-${MONTHS[p.m - 1]}${dayEvents.length ? `, ${dayEvents.length} ta to‘y` : ''}`;
      const cell = h('button', {
        type: 'button',
        role: 'gridcell',
        class: `cal__cell${inMonth ? '' : ' is-other'}${date === today ? ' is-today' : ''}${date === state.selected ? ' is-selected' : ''}`,
        'aria-label': label,
        'aria-selected': String(date === state.selected),
        onClick: () => select(date),
      }, h('span', null, String(p.d)), dots);
      grid.appendChild(cell);
    }
    if (direction !== 0) {
      grid.classList.remove('is-sliding-next', 'is-sliding-prev');
      void grid.offsetWidth;
      grid.classList.add(direction > 0 ? 'is-sliding-next' : 'is-sliding-prev');
    }
  }

  function drawDay() {
    clear(dayList);
    const list = state.items.filter((ev) => ev.event_date === state.selected);
    dayList.appendChild(h('h2', { class: 'section-title' }, dayLabel(state.selected, today)));
    if (list.length === 0) {
      dayList.appendChild(h('p', { class: 'cal__day-empty' }, 'Bu kunda to‘y yo‘q'));
      return;
    }
    dayList.appendChild(h('div', { class: 'block' }, list.map((ev) => eventRow(ev, { today }))));
  }

  function select(date) {
    if (date === state.selected) return;
    tg.haptic.selection();
    const p = parts(date);
    state.selected = date;
    history.replaceState(null, '', `#/calendar?month=${monthKey(state.y, state.m)}&day=${date}`);
    if (p.m !== state.m || p.y !== state.y) {
      state.y = p.y;
      state.m = p.m;
      load(0);
    } else {
      drawGrid();
      drawDay();
    }
  }

  function shift(delta) {
    let m = state.m + delta;
    let y = state.y;
    if (m < 1) { m = 12; y--; }
    if (m > 12) { m = 1; y++; }
    state.y = y;
    state.m = m;
    state.selected = y === t.y && m === t.m ? today : `${y}-${pad(m)}-01`;
    history.replaceState(null, '', `#/calendar?month=${monthKey(y, m)}`);
    load(delta);
  }

  async function load(direction = 0) {
    const key = monthKey(state.y, state.m);
    const cached = api.peek('/calendar', { month: key });
    state.items = cached?.items ?? [];
    drawGrid(direction);
    if (cached) {
      drawDay();
    } else {
      clear(dayList).appendChild(rowsSkeleton(2));
    }
    try {
      const data = await api.get('/calendar', { month: key });
      if (!ctx.isCurrent() || key !== monthKey(state.y, state.m)) return;
      state.items = data.items;
      drawGrid();
      drawDay();
    } catch (e) {
      if (!ctx.isCurrent()) return;
      clear(dayList).appendChild(errorState(e.message, () => load()));
    }
  }

  function openPicker() {
    let year = state.y;
    const yearLabel = h('span', { class: 'picker__year num' }, String(year));
    const months = h('div', { class: 'picker__months' });
    const drawMonths = () => {
      yearLabel.textContent = String(year);
      clear(months);
      MONTHS.forEach((name, i) => {
        const active = year === state.y && i + 1 === state.m;
        months.appendChild(h('button', {
          type: 'button',
          class: 'choice picker__month',
          'aria-pressed': String(active),
          onClick: () => {
            state.y = year;
            state.m = i + 1;
            state.selected = year === t.y && i + 1 === t.m ? today : `${year}-${pad(i + 1)}-01`;
            history.replaceState(null, '', `#/calendar?month=${monthKey(year, i + 1)}`);
            sheet.close();
            load(0);
          },
        }, name.charAt(0).toUpperCase() + name.slice(1)));
      });
    };
    const content = h('div', null,
      h('div', { class: 'picker__head' },
        iconButton('chevron-left', 'Oldingi yil', () => { year--; drawMonths(); }),
        yearLabel,
        iconButton('chevron-right', 'Keyingi yil', () => { year++; drawMonths(); })),
      months);
    drawMonths();
    const sheet = openSheet({ title: 'Oyni tanlang', content });
  }

  // Gorizontal suring bilan oy almashish
  let sx = null;
  let sy = null;
  const viewport = grid.parentElement;
  viewport.addEventListener('touchstart', (e) => {
    sx = e.touches[0].clientX;
    sy = e.touches[0].clientY;
  }, { passive: true });
  viewport.addEventListener('touchend', (e) => {
    if (sx === null) return;
    const dx = e.changedTouches[0].clientX - sx;
    const dy = e.changedTouches[0].clientY - sy;
    sx = null;
    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy) * 1.5) {
      tg.haptic.impact('light');
      shift(dx < 0 ? 1 : -1);
    }
  });

  document.body.classList.add('lock-swipes');
  tg.setVerticalSwipes(false);

  await load();
}
