// To'y tafsiloti (DESIGN 7): tur chipi, sarlavha, sana; vaqt jadvali, joy, moliya, ijro, faqat siz uchun.

import * as api from '../api.js';
import { session } from '../store.js';
import * as tg from '../tg.js';
import { h, clear, append, button, iconButton, typeChip, statusChip, listBlock, row, errorState, setLoading } from '../ui/components.js';
import { icon } from '../ui/icons.js';
import { openSheet, confirmSheet } from '../ui/sheet.js';
import { toast } from '../ui/toast.js';
import { blockSkeleton, delayedSkeleton } from '../ui/skeleton.js';
import { field, moneyInput, textInput, applyErrors } from '../ui/form.js';
import { formatDate, formatTime, todayISO, formatDayMonth, toDate } from '../lib/date-uz.js';
import { formatMoney, parseAmount, toNumber } from '../lib/money.js';
import { googleMapsUrl, yandexMapsUrl, hasLocation, hasPoint } from '../lib/maps.js';

const TIMELINE = [
  ['assembly_time', 'Yig‘ilish'],
  ['departure_time', 'Jo‘nash'],
  ['soundcheck_time', 'Soundcheck'],
  ['nahor_time', 'Nahor'],
  ['abet_time', 'Abet'],
  ['start_time', 'Boshlanish'],
];

const VISIBILITY_LABELS = {
  members: ['A’zolarga ochiq', 'eye'],
  private: ['A’zolarga yashirin', 'eye-off'],
};

function headerBlock(ev, isOwner, onMore) {
  return h('header', { class: 'detail-head' },
    h('div', { class: 'detail-head__chips' },
      typeChip(ev.event_type),
      ev.status !== 'planned' ? statusChip(ev.status) : null,
      isOwner ? iconButton('more', 'Amallar', onMore, 'detail-head__more') : null),
    h('h1', { class: 'detail-head__title' }, ev.title),
    h('p', { class: 'detail-head__date' },
      h('span', null, formatDate(ev.event_date)),
      h('span', { class: 'detail-head__sep', 'aria-hidden': 'true' }, '·'),
      h('span', { class: 'num' }, formatTime(ev.start_time))));
}

function timelineBlock(ev) {
  const points = TIMELINE
    .map(([key, label], order) => ({ key, label, time: ev[key], order }))
    .filter((p) => p.time)
    .sort((a, b) => (a.time === b.time ? a.order - b.order : a.time.localeCompare(b.time)));
  const items = points.map((p) => h('li', { class: `timeline__item${p.key === 'start_time' ? ' is-main' : ''}` },
    h('span', { class: 'timeline__dot', 'aria-hidden': 'true' }),
    h('span', { class: 'timeline__time num' }, formatTime(p.time)),
    h('span', { class: 'timeline__label' }, p.label)));
  const duration = ev.duration_min ? `Davomiyligi: ${Math.floor(ev.duration_min / 60) ? `${Math.floor(ev.duration_min / 60)} soat` : ''}${ev.duration_min % 60 ? ` ${ev.duration_min % 60} daqiqa` : ''}`.trim() : null;
  return listBlock('Vaqt jadvali',
    h('ol', { class: 'timeline' }, items),
    duration ? h('p', { class: 'row row--text block-sub' }, duration) : null,
    ev.schedule_notes ? h('p', { class: 'row row--text' }, ev.schedule_notes) : null);
}

function placeBlock(ev) {
  if (!hasLocation(ev)) return null;
  const address = [ev.venue_address, ev.district].filter(Boolean).join(', ');
  return listBlock('Joy',
    h('div', { class: 'row' },
      h('span', { class: 'row__icon' }, icon('map-pin', { size: 20 })),
      h('span', { class: 'row__main' },
        h('span', { class: 'row__label' }, ev.venue_name || address),
        ev.venue_name && address ? h('span', { class: 'row__sub' }, address) : null)),
    hasPoint(ev) ? null : h('p', { class: 'warning-line' }, icon('alert', { size: 18 }), 'Joylashuv tasdiqlanmagan — manzil bo‘yicha qidiriladi'),
    h('div', { class: 'map-actions' },
      button('Google Maps', { variant: 'secondary', iconName: 'navigation', onClick: () => tg.openLink(googleMapsUrl(ev)) }),
      button('Yandex', { variant: 'secondary', iconName: 'navigation', onClick: () => tg.openLink(yandexMapsUrl(ev)) })));
}

function financeBlock(ev, isOwner, payments, actions) {
  const f = ev.finance;
  if (!f) return null;
  const total = f.total_price;
  const paid = toNumber(f.paid);
  const balance = total !== null ? toNumber(f.balance) : null;
  const rows = [];

  rows.push(h('div', { class: 'row money-row' },
    h('span', { class: 'row__main' }, h('span', { class: 'row__label' }, 'Kelishilgan narx')),
    h('span', { class: 'row__value' }, total !== null ? formatMoney(total) : 'Kiritilmagan')));
  rows.push(h('div', { class: 'row money-row' },
    h('span', { class: 'row__main' }, h('span', { class: 'row__label' }, 'To‘langan')),
    h('span', { class: 'row__value' }, formatMoney(f.paid) || '0 so‘m')));
  if (balance !== null) {
    rows.push(balance <= 0
      ? h('div', { class: 'row money-row money-row--paid' },
        h('span', { class: 'row__main' }, h('span', { class: 'row__label' }, 'Qoldiq')),
        h('span', { class: 'row__value' }, 'To‘liq to‘langan'))
      : h('div', { class: 'row money-row' },
        h('span', { class: 'row__main' }, h('span', { class: 'row__label' }, 'Qoldiq')),
        h('span', { class: 'row__value' }, formatMoney(f.balance))));
  }

  const totalNum = toNumber(total);
  const ratio = totalNum > 0 ? Math.min(1, paid / totalNum) : 0;
  const progress = total !== null
    ? h('div', { class: 'money-progress', role: 'progressbar', 'aria-label': 'To‘langan ulushi', 'aria-valuemin': '0', 'aria-valuemax': '100', 'aria-valuenow': String(Math.round(ratio * 100)) },
      h('div', { class: 'money-progress__bar', style: { transform: `scaleX(${ratio})` } }))
    : null;

  let title = 'Moliya';
  let titleExtra = null;
  if (isOwner) {
    const [label, iconName] = VISIBILITY_LABELS[f.effective_visibility] ?? VISIBILITY_LABELS.private;
    titleExtra = h('span', { class: 'chip chip--status' }, icon(iconName, { size: 18 }), label);
  }

  const group = h('section', { class: 'block-group' },
    h('h2', { class: 'block-title' }, title, titleExtra),
    h('div', { class: 'block' }, rows, progress));

  if (isOwner) {
    const list = h('div', { class: 'block payments' });
    if (payments && payments.items.length) {
      for (const p of payments.items) {
        list.appendChild(row(formatMoney(p.amount), null, {
          sub: [formatDayMonth(p.paid_on), p.note].filter(Boolean).join(' · '),
          trailing: iconButton('trash', 'Avansni o‘chirish', () => actions.deletePayment(p), 'row__trailing-btn'),
        }));
      }
    }
    append(group, [
      payments && payments.items.length ? h('h3', { class: 'block-title block-title--sub' }, 'Avanslar') : null,
      payments && payments.items.length ? list : null,
      h('div', { class: 'block-actions' }, button('Avans qo‘shish', { variant: 'secondary', iconName: 'plus', onClick: actions.addPayment })),
    ]);
  }
  return group;
}

function performanceBlock(ev) {
  const items = [];
  if (ev.performance_notes) items.push(row(ev.performance_notes, null, { iconName: 'music', sub: 'Ijro' }));
  if (ev.equipment_notes) items.push(row(ev.equipment_notes, null, { iconName: 'speaker', sub: 'Jihozlar' }));
  if (ev.dress_code) items.push(row(ev.dress_code, null, { iconName: 'shirt', sub: 'Dress code' }));
  if (items.length === 0) return null;
  for (const it of items) it.classList.add('row--multiline');
  return listBlock('Ijro', ...items);
}

function privateBlock(ev) {
  if (!('client_name' in ev)) return null;
  const items = [];
  if (ev.client_name) items.push(row(ev.client_name, null, { iconName: 'users', sub: 'Mijoz' }));
  if (ev.client_phone) {
    items.push(h('a', { class: 'row row--action', href: `tel:${ev.client_phone.replace(/[^\d+]/g, '')}` },
      h('span', { class: 'row__icon' }, icon('phone', { size: 20 })),
      h('span', { class: 'row__main' }, h('span', { class: 'row__label num-plain' }, ev.client_phone), h('span', { class: 'row__sub' }, 'Telefon')),
      h('span', { class: 'row__chevron' }, icon('chevron-right', { size: 20 }))));
  }
  if (ev.internal_notes) {
    const r = row(ev.internal_notes, null, { iconName: 'lock', sub: 'Ichki izoh' });
    r.classList.add('row--multiline');
    items.push(r);
  }
  if (items.length === 0) return null;
  return listBlock('Faqat siz uchun', ...items);
}

export async function render(ctx) {
  const id = ctx.params.id;
  const isOwner = session.isOwner();
  const page = h('div', { class: 'page page--detail' });
  ctx.root.appendChild(page);

  let ev = null;
  let payments = null;

  const actions = {
    addPayment: () => openPaymentSheet(),
    deletePayment: async (p) => {
      const ok = await confirmSheet({
        title: 'Avansni o‘chirish',
        text: `${formatMoney(p.amount)} — ${formatDayMonth(p.paid_on)}. Bu yozuv butunlay o‘chiriladi.`,
        confirmLabel: 'O‘chirish',
        danger: true,
      });
      if (!ok) return;
      try {
        payments = await api.del(`/events/${id}/payments/${p.id}`);
        toast.success('Avans o‘chirildi');
        await load(true);
      } catch (e) {
        toast.error(e.message);
      }
    },
  };

  function draw() {
    append(clear(page), [
      headerBlock(ev, isOwner, openMenu),
      timelineBlock(ev),
      placeBlock(ev),
      financeBlock(ev, isOwner, payments, actions),
      performanceBlock(ev),
      isOwner ? privateBlock(ev) : null,
    ]);
  }

  async function load(silent = false) {
    const cached = !silent ? api.peek(`/events/${id}`) : null;
    if (cached) {
      ev = cached;
      draw();
    }
    const stop = cached || silent ? () => {} : delayedSkeleton(page, () => h('div', null,
      h('div', { class: 'detail-head' }, h('span', { class: 'sk sk--chip' }), h('span', { class: 'sk sk--line sk--w80 sk--title' }), h('span', { class: 'sk sk--line sk--w40' })),
      blockSkeleton(4), blockSkeleton(2)));
    try {
      const [event, pay] = await Promise.all([
        api.get(`/events/${id}`),
        isOwner ? api.get(`/events/${id}/payments`).catch(() => null) : Promise.resolve(null),
      ]);
      stop();
      if (!ctx.isCurrent()) return;
      ev = event;
      payments = pay;
      draw();
    } catch (e) {
      stop();
      if (!ctx.isCurrent()) return;
      if (e.status === 404) {
        clear(page).appendChild(errorState('To‘y topilmadi yoki o‘chirilgan.', null));
      } else if (!cached) {
        clear(page).appendChild(errorState(e.message, () => load()));
      }
    }
  }

  function isActive() {
    return ev && (ev.status === 'planned' || ev.status === 'confirmed');
  }

  async function changeStatus(action, successText) {
    try {
      ev = await api.post(`/events/${id}/${action}`);
      tg.haptic.notify('success');
      toast.success(successText);
      draw();
    } catch (e) {
      toast.error(e.message);
    }
  }

  function openMenu() {
    const item = (label, iconName, onClick, danger = false) => h('button', {
      type: 'button',
      class: `menu__item${danger ? ' menu__item--danger' : ''}`,
      onClick: () => { sheet.close(); onClick(); },
    }, icon(iconName, { size: 20 }), h('span', null, label));

    const future = ev && toDate(ev.starts_at).getTime() > Date.now();
    const items = [
      ev.status !== 'archived' && ev.status !== 'cancelled' ? item('Tahrirlash', 'edit', () => ctx.navigate(`/events/${id}/edit`)) : null,
      isActive() && future ? item('Eslatmani hozir yuborish', 'bell', remindNow) : null,
      ['planned', 'confirmed', 'completed'].includes(ev.status) ? item('To‘yni bekor qilish', 'x', cancelEvent, true) : null,
      ['cancelled', 'archived'].includes(ev.status) ? item('Tiklash', 'refresh', () => changeStatus('restore', 'To‘y tiklandi')) : null,
      ev.status !== 'archived' ? item('Arxivlash', 'archive', archiveEvent) : null,
    ].filter(Boolean);
    const sheet = openSheet({ title: 'Amallar', content: h('div', { class: 'menu' }, items) });
  }

  async function remindNow() {
    const ok = await confirmSheet({
      title: 'Eslatmani hozir yuborish',
      text: 'Guruhga shu to‘y haqida eslatma yuboriladi. Narx va mijoz ma’lumoti xabarga kirmaydi.',
      confirmLabel: 'Yuborish',
    });
    if (!ok) return;
    try {
      await api.post(`/events/${id}/remind-now`);
      toast.success('Eslatma yuborildi');
    } catch (e) {
      toast.error(e.message, { retry: remindNow });
    }
  }

  async function cancelEvent() {
    const ok = await confirmSheet({
      title: 'To‘yni bekor qilish',
      text: 'To‘y ro‘yxatda “Bekor qilingan” bo‘lib qoladi, eslatma yuborilmaydi. Keyin tiklash mumkin.',
      confirmLabel: 'To‘yni bekor qilish',
      danger: true,
    });
    if (ok) changeStatus('cancel', 'To‘y bekor qilindi');
  }

  async function archiveEvent() {
    const ok = await confirmSheet({
      title: 'Arxivlash',
      text: 'To‘y a’zolarga ko‘rinmaydi va ro‘yxatdan chiqadi. Arxivdan tiklash mumkin.',
      confirmLabel: 'Arxivlash',
    });
    if (ok) changeStatus('archive', 'To‘y arxivlandi');
  }

  function openPaymentSheet() {
    const amount = moneyInput('amount');
    const date = textInput('paid_on', todayISO(), { type: 'date' });
    const note = textInput('note', '', { maxlength: '255', placeholder: 'Masalan: naqd, Karim aka orqali' });
    const form = h('form', { class: 'sheet-form', novalidate: true },
      field('Summa', amount, { name: 'amount' }),
      field('To‘lov sanasi', date, { name: 'paid_on' }),
      field('Izoh', note, { name: 'note', hint: 'Faqat siz ko‘rasiz.' }));
    const save = button('Saqlash', { variant: 'primary', block: true, type: 'submit' });
    save.setAttribute('form', 'payment-form');
    form.id = 'payment-form';

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const value = parseAmount(amount.input.value);
      if (!value || value === '0') {
        applyErrors(form, { amount: 'Summa 0 dan katta bo‘lsin.' });
        return;
      }
      setLoading(save, true);
      try {
        payments = await api.post(`/events/${id}/payments`, { amount: value, paid_on: date.value, note: note.value.trim() });
        sheet.close();
        toast.success('Avans qo‘shildi');
        await load(true);
      } catch (err) {
        if (err.details) applyErrors(form, err.details);
        else toast.error(err.message);
      } finally {
        setLoading(save, false);
      }
    });

    const sheet = openSheet({ title: 'Avans qo‘shish', content: form, footer: save });
    setTimeout(() => amount.input.focus(), 300);
  }

  await load();
  ctx.restoreScroll();
}
