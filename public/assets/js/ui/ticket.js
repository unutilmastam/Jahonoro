// "Keyingi to'y" chiptasi (6.1) — ilovadagi yagona yorqin element.

import { h, button, typeChip } from './components.js';
import { icon } from './icons.js';
import { parts, MONTHS, WEEKDAYS, weekdayIndex, todayISO, formatTime, countdown } from '../lib/date-uz.js';
import { googleMapsUrl, hasLocation } from '../lib/maps.js';
import { openLink } from '../tg.js';
import { once } from '../store.js';

function timeCell(label, value) {
  return h('div', { class: 'ticket__cell' },
    h('span', { class: 'ticket__label' }, label),
    h('span', { class: 'ticket__time num' }, formatTime(value)));
}

/**
 * @param {object} ev — FinanceGuard::present() natijasi
 */
export function ticket(ev) {
  const today = todayISO();
  const { y, m, d } = parts(ev.event_date);
  const dateLine = `${MONTHS[m - 1]}${y !== parts(today).y ? ` ${y}` : ''}, ${WEEKDAYS[weekdayIndex(ev.event_date)]}`;
  const address = [ev.district, ev.venue_address].filter(Boolean).join(', ');

  const second = ev.assembly_time
    ? timeCell('Yig‘ilish', ev.assembly_time)
    : (ev.departure_time ? timeCell('Jo‘nash', ev.departure_time) : null);

  const cd = countdown(ev.starts_at);
  const countdownEl = h('span', { class: `ticket__countdown${cd.today ? ' is-today' : ''}` },
    cd.today ? h('span', { class: 'ticket__dot', 'aria-hidden': 'true' }) : null,
    cd.text);

  const intro = !once.has('ticket-intro');
  once.mark('ticket-intro');

  const el = h('article', { class: `ticket theme-night${intro ? ' ticket--intro' : ''}` },
    h('a', { class: 'ticket__link', href: `#/events/${ev.id}`, 'aria-label': `Keyingi to‘y: ${ev.title}` }),
    h('div', { class: 'ticket__main' },
      h('div', { class: 'ticket__top' },
        h('span', { class: 'ticket__day num' }, String(d)),
        typeChip(ev.event_type)),
      h('p', { class: 'ticket__date' }, dateLine),
      h('div', { class: 'ticket__venue' },
        h('p', { class: 'ticket__venue-name' }, ev.venue_name || ev.title),
        address ? h('p', { class: 'ticket__venue-addr' }, address) : null)),
    h('div', { class: 'ticket__perf', 'aria-hidden': 'true' }, h('span', { class: 'ticket__perf-line' })),
    h('div', { class: 'ticket__stub' },
      h('div', { class: 'ticket__times' }, timeCell('Boshlanish', ev.start_time), second),
      h('div', { class: 'ticket__foot' },
        countdownEl,
        hasLocation(ev)
          ? button('Xarita', {
            variant: 'secondary',
            size: 'sm',
            iconName: 'map-pin',
            onClick: (e) => { e.preventDefault(); e.stopPropagation(); openLink(googleMapsUrl(ev)); },
          })
          : null)));

  return el;
}

export function ticketEmpty(isOwner, navigate) {
  return h('div', { class: 'empty empty--ticket' },
    icon('calendar', { size: 32, className: 'empty__icon' }),
    h('p', { class: 'empty__title' }, 'Kelgusi to‘ylar yo‘q'),
    h('p', { class: 'empty__text' }, 'Yangi buyurtma kelganda shu yerda ko‘rinadi.'),
    isOwner ? button('To‘y qo‘shish', { variant: 'primary', iconName: 'plus', onClick: () => navigate('/events/new') }) : null);
}
