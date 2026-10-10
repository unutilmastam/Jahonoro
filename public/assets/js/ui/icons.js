// Ikonkalar: 24×24, 1.75 chiziq, yumaloq uchlar. Hammasi shu yerda, bitta uslubda.
// icon(name, {size, label}) → SVGElement

const SVG_NS = 'http://www.w3.org/2000/svg';

const P = (d) => ['path', { d }];
const C = (cx, cy, r) => ['circle', { cx, cy, r }];
const R = (x, y, width, height, rx = 0) => ['rect', { x, y, width, height, rx }];
const L = (x1, y1, x2, y2) => ['line', { x1, y1, x2, y2 }];

const ICONS = {
  calendar: [R(3.5, 5, 17, 15.5, 2.5), L(3.5, 10, 20.5, 10), L(8, 3, 8, 7), L(16, 3, 16, 7)],
  clock: [C(12, 12, 8.5), P('M12 7.5V12l3 2')],
  music: [P('M9 17.5V6l10-2v11.5'), C(6.5, 17.5, 2.5), C(16.5, 15.5, 2.5)],
  rings: [C(9, 14, 5), C(15, 14, 5), P('M10 5.5l2-2.5 2 2.5')],
  'map-pin': [P('M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z'), C(12, 10, 2.25)],
  navigation: [P('M4 11l16-7-7 16-2-7-7-2z')],
  wallet: [P('M4 7.5A2.5 2.5 0 0 1 6.5 5H18v3'), R(4, 7.5, 16, 12, 2.5), P('M16 13.5h.01')],
  payment: [R(3, 6, 18, 12.5, 2.5), L(3, 10, 21, 10), L(7, 14.5, 10, 14.5)],
  eye: [P('M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z'), C(12, 12, 3)],
  'eye-off': [P('M9.9 5.7A9.6 9.6 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-2.6 3.4'), P('M6.6 6.6C3.9 8.3 2.5 12 2.5 12S6 18.5 12 18.5c1.8 0 3.4-.6 4.7-1.4'), P('M9.9 9.9a3 3 0 0 0 4.2 4.2'), L(3, 3, 21, 21)],
  bell: [P('M6 16.5V11a6 6 0 0 1 12 0v5.5l1.5 2h-15z'), P('M10 20.5a2 2 0 0 0 4 0')],
  users: [C(9, 8.5, 3.5), P('M3 19.5c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5'), P('M15.5 5.2a3.5 3.5 0 0 1 0 6.6'), P('M17.5 14.3c2.1.6 3.5 2.6 3.5 5.2')],
  settings: [C(12, 12, 3), P('M19.4 13.5a7.6 7.6 0 0 0 0-3l2-1.6-2-3.4-2.4 1a7.6 7.6 0 0 0-2.6-1.5L14 2.5h-4l-.4 2.5A7.6 7.6 0 0 0 7 6.5l-2.4-1-2 3.4 2 1.6a7.6 7.6 0 0 0 0 3l-2 1.6 2 3.4 2.4-1a7.6 7.6 0 0 0 2.6 1.5l.4 2.5h4l.4-2.5a7.6 7.6 0 0 0 2.6-1.5l2.4 1 2-3.4z')],
  edit: [P('M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16z'), L(13.5, 6.5, 17.5, 10.5)],
  archive: [R(3, 4, 18, 4.5, 1.5), P('M5 8.5V18a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8.5'), L(10, 12.5, 14, 12.5)],
  trash: [P('M4 7h16'), P('M9.5 7V4.5h5V7'), P('M6 7l1 12.5a1.5 1.5 0 0 0 1.5 1.5h7a1.5 1.5 0 0 0 1.5-1.5L18 7'), L(10, 11, 10, 17), L(14, 11, 14, 17)],
  search: [C(11, 11, 6.5), L(16, 16, 20.5, 20.5)],
  filter: [P('M4 6h16'), P('M7 12h10'), P('M10 18h4')],
  'chevron-left': [P('M15 5l-7 7 7 7')],
  'chevron-right': [P('M9 5l7 7-7 7')],
  'chevron-down': [P('M5 9l7 7 7-7')],
  lock: [R(5, 10.5, 14, 10, 2.5), P('M8 10.5V8a4 4 0 0 1 8 0v2.5')],
  shield: [P('M12 3l7.5 3v5.5c0 4.6-3.2 8.3-7.5 9.5-4.3-1.2-7.5-4.9-7.5-9.5V6z'), P('M9 12l2 2 4-4')],
  more: [C(5.5, 12, 1.1), C(12, 12, 1.1), C(18.5, 12, 1.1)],
  plus: [L(12, 5, 12, 19), L(5, 12, 19, 12)],
  check: [P('M5 12.5l4.5 4.5L19 7.5')],
  x: [L(6, 6, 18, 18), L(18, 6, 6, 18)],
  home: [P('M4 10.5L12 4l8 6.5V19a1.5 1.5 0 0 1-1.5 1.5H15V14h-6v6.5H5.5A1.5 1.5 0 0 1 4 19z')],
  list: [L(9, 6.5, 20, 6.5), L(9, 12, 20, 12), L(9, 17.5, 20, 17.5), C(4.75, 6.5, 0.75), C(4.75, 12, 0.75), C(4.75, 17.5, 0.75)],
  refresh: [P('M19.5 12a7.5 7.5 0 1 1-2.2-5.3'), P('M19.5 4.5v4h-4')],
  phone: [P('M5 4h3.5l1.8 4.5-2.3 1.4a11 11 0 0 0 6.1 6.1l1.4-2.3L20 15.5V19a1.5 1.5 0 0 1-1.6 1.5A16.5 16.5 0 0 1 3.5 5.6 1.5 1.5 0 0 1 5 4z')],
  shirt: [P('M9 3.5L4 6l1.5 4.5L8 9.5V20.5h8V9.5l2.5 1L20 6l-5-2.5a3 3 0 0 1-6 0z')],
  speaker: [R(5.5, 3, 13, 18, 2.5), C(12, 14.5, 3.25), C(12, 7.5, 1.25)],
  alert: [P('M12 4L2.8 19.5h18.4z'), L(12, 10, 12, 14), P('M12 17h.01')],
  sunrise: [P('M5 17a7 7 0 0 1 14 0'), L(2.5, 17, 21.5, 17), L(12, 4, 12, 7), L(5.6, 9.6, 7.3, 11.3), L(18.4, 9.6, 16.7, 11.3), L(7, 20.5, 17, 20.5)],
  sun: [C(12, 12, 4), L(12, 2.5, 12, 4.5), L(12, 19.5, 12, 21.5), L(2.5, 12, 4.5, 12), L(19.5, 12, 21.5, 12), L(5.3, 5.3, 6.7, 6.7), L(17.3, 17.3, 18.7, 18.7), L(5.3, 18.7, 6.7, 17.3), L(17.3, 6.7, 18.7, 5.3)],
  moon: [P('M19.5 14.5A8 8 0 1 1 9.5 4.5a6.5 6.5 0 0 0 10 10z')],
  flag: [P('M5 21V4'), P('M5 4h11l-2 4 2 4H5')],
  locate: [C(12, 12, 3.5), C(12, 12, 8), L(12, 1.5, 12, 4), L(12, 20, 12, 22.5), L(1.5, 12, 4, 12), L(20, 12, 22.5, 12)],
  building: [P('M4 21V9l8-5 8 5v12'), P('M9 21v-6h6v6'), L(2.5, 21, 21.5, 21)],
};

/**
 * @param {string} name
 * @param {{size?: number, label?: string, className?: string}} [opts]
 * @returns {SVGSVGElement}
 */
export function icon(name, opts = {}) {
  const { size = 24, label, className } = opts;
  const svg = document.createElementNS(SVG_NS, 'svg');
  svg.setAttribute('viewBox', '0 0 24 24');
  svg.setAttribute('width', String(size));
  svg.setAttribute('height', String(size));
  svg.setAttribute('fill', 'none');
  svg.setAttribute('stroke', 'currentColor');
  svg.setAttribute('stroke-width', '1.75');
  svg.setAttribute('stroke-linecap', 'round');
  svg.setAttribute('stroke-linejoin', 'round');
  svg.setAttribute('class', `icon${className ? ` ${className}` : ''}`);
  if (label) {
    svg.setAttribute('role', 'img');
    svg.setAttribute('aria-label', label);
  } else {
    svg.setAttribute('aria-hidden', 'true');
  }
  svg.setAttribute('focusable', 'false');
  for (const [tag, attrs] of ICONS[name] ?? ICONS.alert) {
    const el = document.createElementNS(SVG_NS, tag);
    for (const [k, v] of Object.entries(attrs)) el.setAttribute(k, String(v));
    svg.appendChild(el);
  }
  return svg;
}

export const ICON_NAMES = Object.keys(ICONS);
