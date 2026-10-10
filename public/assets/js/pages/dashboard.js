// Bosh sahifa (DESIGN 7): logotip, salomlashuv, chipta, hafta/oy raqamlari, shu hafta, qarzdorlik, so'nggi o'zgarishlar.

import * as api from '../api.js';
import { session } from '../store.js';
import { h, clear, append, eventRow, dayHeader, listBlock, row, avatar, errorState } from '../ui/components.js';
import { ticket, ticketEmpty } from '../ui/ticket.js';
import { ticketSkeleton, rowsSkeleton, delayedSkeleton } from '../ui/skeleton.js';
import { greeting, todayISO, dayLabel, timeAgo, formatDayMonth } from '../lib/date-uz.js';
import { formatMoney } from '../lib/money.js';

const ACTION_LABELS = {
  'event.create': 'qo‘shildi',
  'event.update': 'o‘zgartirildi',
  'event.cancel': 'bekor qilindi',
  'event.restore': 'tiklandi',
};

function firstName(name) {
  return String(name || '').trim().split(/\s+/)[0] || '';
}

function header(user) {
  const name = firstName(user?.name);
  return h('header', { class: 'home-head' },
    h('div', { class: 'home-head__top' },
      h('span', { class: 'logo' }, 'JAHONORO'),
      avatar(user?.name)),
    h('p', { class: 'home-head__greet' }, name ? `${greeting()}, ${name}` : greeting()));
}

function stats(counts) {
  const cell = (value, label) => h('div', { class: 'stat' },
    h('span', { class: 'stat__value num' }, String(value)),
    h('span', { class: 'stat__label' }, label));
  return h('section', { class: 'stats', 'aria-label': 'To‘ylar soni' },
    cell(counts.week, 'Bu hafta'),
    cell(counts.month, 'Bu oy'),
    cell(counts.upcoming, 'Kelgusi'));
}

function weekList(items, today, nextId) {
  const rest = items.filter((ev) => ev.id !== nextId);
  if (rest.length === 0) return null;
  const groups = [];
  let lastDate = null;
  let block = null;
  for (const ev of rest) {
    if (ev.event_date !== lastDate) {
      lastDate = ev.event_date;
      block = h('div', { class: 'block' });
      groups.push(dayHeader(dayLabel(ev.event_date, today)), block);
    }
    block.appendChild(eventRow(ev, { today }));
  }
  return h('section', { class: 'home-section' },
    h('h2', { class: 'section-title' }, 'Shu hafta'),
    groups);
}

function debtBlock(debt, navigate) {
  if (!debt) return null;
  const value = Number.parseFloat(debt.balance) > 0 ? formatMoney(debt.balance) : 'Qarz yo‘q';
  return listBlock('Qarzdorlik',
    row('Jami qoldiq', null, {
      sub: debt.events > 0 ? `${debt.events} ta to‘y bo‘yicha` : null,
      trailing: h('span', { class: 'debt-value num' }, value),
      onClick: () => navigate('/events'),
    }));
}

function pendingBlock(count, navigate) {
  if (!count) return null;
  return h('div', { class: 'block notice-block' },
    row(`${count} ta yangi a’zo tasdiq kutmoqda`, null, {
      iconName: 'users',
      trailing: h('span', { class: 'badge-dot', 'aria-hidden': 'true' }),
      onClick: () => navigate('/settings'),
    }));
}

function groupNotice(linked, navigate) {
  if (linked !== false) return null;
  return h('div', { class: 'block notice-block' },
    row('Guruh hali ulanmagan', null, {
      iconName: 'users',
      sub: 'A’zolar kirishi va eslatmalar uchun guruhni ulang',
      trailing: h('span', { class: 'badge-dot', 'aria-hidden': 'true' }),
      onClick: () => navigate('/settings'),
    }));
}

function recentBlock(items) {
  if (!items || items.length === 0) return null;
  const rows = items.map((it) => row(it.title || 'To‘y', null, {
    sub: [ACTION_LABELS[it.action] ?? 'o‘zgardi', it.event_date ? formatDayMonth(it.event_date) : null, timeAgo(it.created_at)].filter(Boolean).join(' · '),
    href: it.event_id ? `#/events/${it.event_id}` : undefined,
  }));
  return listBlock('So‘nggi o‘zgarishlar', ...rows);
}

function draw(root, data, ctx) {
  const today = todayISO();
  const user = session.user;
  const isOwner = session.isOwner();
  append(clear(root), [
    header(user),
    h('div', { class: 'home-ticket' }, data.next ? ticket(data.next) : ticketEmpty(isOwner, ctx.navigate)),
    stats(data.counts),
    isOwner ? groupNotice(data.group_linked, ctx.navigate) : null,
    isOwner ? pendingBlock(data.pending_members, ctx.navigate) : null,
    weekList(data.week ?? [], today, data.next?.id),
    isOwner ? debtBlock(data.debt, ctx.navigate) : null,
    recentBlock(data.recent),
  ]);
}

export async function render(ctx) {
  const page = h('div', { class: 'page page--home' });
  ctx.root.appendChild(page);

  const cached = api.peek('/dashboard');
  if (cached) draw(page, cached, ctx);

  const load = async () => {
    const stop = cached ? () => {} : delayedSkeleton(page, () => h('div', null,
      header(session.user), h('div', { class: 'home-ticket' }, ticketSkeleton()), rowsSkeleton(2)));
    try {
      const data = await api.get('/dashboard');
      stop();
      if (ctx.isCurrent()) draw(page, data, ctx);
    } catch (e) {
      stop();
      if (!ctx.isCurrent()) return;
      if (!cached) clear(page).append(header(session.user), errorState(e.message, load));
    }
  };
  await load();
  ctx.restoreScroll();
}

