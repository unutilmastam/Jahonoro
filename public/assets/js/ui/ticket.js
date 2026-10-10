// "Keyingi to'y" — firuza afisha (DESIGN 6.1), ilovadagi yagona yorqin element.

import { h, button, placeLine, TYPE_LABELS } from './components.js';
import { icon } from './icons.js';
import { parts, MONTHS, WEEKDAYS, weekdayIndex, todayISO, formatTime, countdown } from '../lib/date-uz.js';
import { googleMapsUrl, hasLocation, homeMapsUrl, hasHome } from '../lib/maps.js';
import { openLink } from '../tg.js';
import { once } from '../store.js';

/** Kun tartibidagi vaqtlar: Manzilda bo'lish, Nahor, Abet, Kech (bo'sh bo'lsa tushib qoladi). */
export const TIME_SLOTS = [
  { key: 'assembly_time', label: 'Manzilda', full: 'Manzilda bo‘lish', iconName: 'flag' },
  { key: 'nahor_time', label: 'Nahor', full: 'Nahor', iconName: 'sunrise' },
  { key: 'abet_time', label: 'Abet', full: 'Abet', iconName: 'sun' },
  { key: 'evening_time', label: 'Kech', full: 'Kech', iconName: 'moon' },
];

/**
 * @param {object} ev — FinanceGuard::present() natijasi
 */
export function ticket(ev) {
  const today = todayISO();
  const { y, m, d } = parts(ev.event_date);
  const cd = countdown(ev.starts_at);
  const intro = !once.has('ticket-intro');
  once.mark('ticket-intro');

  // Soat tartibida; "Manzilda bo'lish" teskari rangda ajralib turadi
  const slots = TIME_SLOTS.filter((s) => ev[s.key])
    .sort((a, b) => String(ev[a.key]).localeCompare(String(ev[b.key])));
  const times = slots.map((s) => h('div', { class: `hero__time${s.key === 'assembly_time' ? ' is-arrival' : ''}` },
    h('span', { class: 'hero__time-label' }, s.label),
    h('span', { class: 'hero__time-value num' }, formatTime(ev[s.key]))));

  const place = placeLine(ev);
  const mapUrl = hasLocation(ev) ? googleMapsUrl(ev) : (hasHome(ev) ? homeMapsUrl(ev) : null);

  return h('article', { class: `hero${intro ? ' hero--intro' : ''}${cd.today ? ' hero--today' : ''}` },
    h('a', { class: 'hero__link', href: `#/events/${ev.id}`, 'aria-label': `Keyingi to‘y: ${ev.title}` }),
    h('div', { class: 'hero__top' },
      h('span', { class: 'hero__kicker' },
        h('span', { class: 'chip__dot', 'aria-hidden': 'true' }),
        TYPE_LABELS[ev.event_type] ?? TYPE_LABELS.boshqa),
      h('span', { class: 'hero__countdown' }, cd.text)),
    h('div', { class: 'hero__date' },
      h('span', { class: 'hero__day num' }, String(d)),
      h('span', { class: 'hero__month' },
        h('span', null, `${MONTHS[m - 1]}${y !== parts(today).y ? ` ${y}` : ''}`),
        h('span', null, WEEKDAYS[weekdayIndex(ev.event_date)]))),
    h('p', { class: 'hero__title' }, ev.title),
    times.length ? h('div', { class: 'hero__times' }, times) : null,
    h('div', { class: 'hero__foot' },
      place ? h('span', { class: 'hero__place' }, icon('map-pin', { size: 18 }), h('span', null, place)) : h('span'),
      mapUrl
        ? Object.assign(button('Xarita', {
          variant: 'secondary',
          size: 'sm',
          iconName: 'navigation',
          onClick: (e) => { e.preventDefault(); e.stopPropagation(); openLink(mapUrl); },
        }), { className: 'btn btn--sm hero__btn' })
        : null));
}

export function ticketEmpty(isOwner, navigate) {
  return h('div', { class: 'empty empty--ticket' },
    icon('calendar', { size: 28, className: 'empty__icon' }),
    h('p', { class: 'empty__title' }, 'Kelgusi to‘ylar yo‘q'),
    h('p', { class: 'empty__text' }, 'Yangi buyurtma kelganda shu yerda ko‘rinadi.'),
    isOwner ? button('To‘y qo‘shish', { variant: 'primary', iconName: 'plus', onClick: () => navigate('/events/new') }) : null);
}
