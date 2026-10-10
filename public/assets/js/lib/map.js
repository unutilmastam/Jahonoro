// Xarita: Leaflet (repoda, assets/vendor/leaflet) + CARTO Voyager plitkalari (OpenStreetMap ma'lumoti).
// Nuqtani faqat owner o'zi qo'yadi — taxmin qilinmaydi.

import { h, button, iconButton } from '../ui/components.js';
import { icon } from '../ui/icons.js';
import { openSheet } from '../ui/sheet.js';
import { toast } from '../ui/toast.js';
import { parseCoordinates } from './maps.js';

const TILE_URL = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
const ATTRIBUTION = '© OpenStreetMap · © CARTO';
const DEFAULT_CENTER = [41.3, 64.6]; // O'zbekiston markazi
const STORE_KEY = 'jahonoro.map.center';

let loader = null;

/** Leaflet'ni faqat kerak bo'lganda yuklaydi. */
export function loadLeaflet() {
  if (window.L) return Promise.resolve(window.L);
  loader ??= new Promise((resolve, reject) => {
    const css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'assets/vendor/leaflet/leaflet.css';
    document.head.appendChild(css);
    const script = document.createElement('script');
    script.src = 'assets/vendor/leaflet/leaflet.js';
    script.onload = () => resolve(window.L);
    script.onerror = () => { loader = null; reject(new Error('Xaritani yuklab bo‘lmadi')); };
    document.head.appendChild(script);
  });
  return loader;
}

function pinIcon(L) {
  return L.divIcon({ className: '', html: '<span class="map-pin"></span>', iconSize: [34, 44], iconAnchor: [17, 42] });
}

function baseLayer(L) {
  return L.tileLayer(TILE_URL, { subdomains: 'abcd', maxZoom: 19, attribution: ATTRIBUTION, detectRetina: true });
}

function savedCenter() {
  try {
    const v = JSON.parse(localStorage.getItem(STORE_KEY) || 'null');
    if (Array.isArray(v) && v.length === 3) return v;
  } catch { /* */ }
  return null;
}

function saveCenter(lat, lng, zoom) {
  try { localStorage.setItem(STORE_KEY, JSON.stringify([lat, lng, zoom])); } catch { /* */ }
}

/**
 * Faqat ko'rsatish uchun kichik xarita (surilmaydi). Bosilsa — onClick.
 */
export async function miniMap(container, lat, lng) {
  container.classList.add('map');
  try {
    const L = await loadLeaflet();
    const map = L.map(container, {
      center: [lat, lng],
      zoom: 16,
      zoomControl: false,
      dragging: false,
      scrollWheelZoom: false,
      doubleClickZoom: false,
      touchZoom: false,
      boxZoom: false,
      keyboard: false,
      attributionControl: true,
    });
    map.attributionControl.setPrefix(false);
    baseLayer(L).addTo(map);
    L.marker([lat, lng], { icon: pinIcon(L), interactive: false, keyboard: false }).addTo(map);
    return map;
  } catch {
    container.textContent = '';
    return null;
  }
}

/** Joriy joylashuv: Telegram LocationManager yoki brauzer geolokatsiyasi. */
function locate() {
  return new Promise((resolve, reject) => {
    const lm = window.Telegram?.WebApp?.LocationManager;
    const viaBrowser = () => {
      if (!navigator.geolocation) {
        reject(new Error('Joylashuvni aniqlab bo‘lmadi'));
        return;
      }
      navigator.geolocation.getCurrentPosition(
        (p) => resolve({ lat: p.coords.latitude, lng: p.coords.longitude }),
        () => reject(new Error('Joylashuvga ruxsat berilmadi')),
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 },
      );
    };
    if (lm && typeof lm.init === 'function') {
      try {
        lm.init(() => {
          if (!lm.isLocationAvailable) {
            viaBrowser();
            return;
          }
          lm.getLocation((data) => {
            if (data && typeof data.latitude === 'number') resolve({ lat: data.latitude, lng: data.longitude });
            else viaBrowser();
          });
        });
        return;
      } catch { /* brauzerga o'tamiz */ }
    }
    viaBrowser();
  });
}

/**
 * Xaritadan nuqta belgilash oynasi.
 * @param {{title: string, lat?: number|null, lng?: number|null}} opts
 * @returns {Promise<{lat: string, lng: string}|null>}
 */
export function pickLocation({ title, lat = null, lng = null }) {
  return new Promise((resolve) => {
    let result = null;
    let map = null;
    const coordsLine = h('p', { class: 'map-picker__coords', 'aria-live': 'polite' });
    const mapEl = h('div', { class: 'map' });
    const locateBtn = iconButton('locate', 'Mening joylashuvim', async () => {
      locateBtn.disabled = true;
      try {
        const p = await locate();
        map?.setView([p.lat, p.lng], 17);
      } catch (e) {
        toast.error(e.message);
      } finally {
        locateBtn.disabled = false;
      }
    }, 'icon-btn--filled map-picker__locate');

    const paste = h('input', {
      class: 'input input--with-icon',
      type: 'text',
      inputmode: 'url',
      placeholder: 'Google Maps havolasi yoki 38.27, 67.89',
      'aria-label': 'Havola yoki koordinata',
      autocomplete: 'off',
    });
    paste.addEventListener('input', () => {
      const p = parseCoordinates(paste.value);
      if (p && map) map.setView([Number(p.lat), Number(p.lng)], 17);
    });

    const picker = h('div', { class: 'map-picker' },
      mapEl,
      h('span', { class: 'map-picker__center', 'aria-hidden': 'true' }, h('span', { class: 'map-pin' })),
      locateBtn);

    const choose = button('Shu joyni tanlash', {
      variant: 'primary',
      block: true,
      iconName: 'check',
      onClick: () => {
        if (!map) return;
        const c = map.getCenter();
        result = { lat: c.lat.toFixed(6), lng: c.lng.toFixed(6) };
        saveCenter(c.lat, c.lng, map.getZoom());
        sheet.close();
      },
    });

    const sheet = openSheet({
      title,
      content: h('div', null,
        picker,
        h('p', { class: 'map-picker__hint' }, 'Xaritani suring — nuqta markazdagi belgi ostida bo‘ladi.'),
        coordsLine,
        h('div', { class: 'input-wrap map-picker__paste' },
          h('span', { class: 'input-icon' }, icon('navigation', { size: 20 })), paste)),
      footer: choose,
      onClose: () => {
        map?.remove();
        resolve(result);
      },
    });

    loadLeaflet().then((L) => {
      const saved = savedCenter();
      const start = lat !== null && lng !== null ? [Number(lat), Number(lng), 17] : (saved ?? [...DEFAULT_CENTER, 6]);
      map = L.map(mapEl, { center: [start[0], start[1]], zoom: start[2], zoomControl: true, attributionControl: true });
      map.attributionControl.setPrefix(false);
      baseLayer(L).addTo(map);
      const update = () => {
        const c = map.getCenter();
        coordsLine.textContent = `${c.lat.toFixed(6)}, ${c.lng.toFixed(6)}`;
      };
      map.on('move', update);
      update();
      // Sheet animatsiyasidan keyin o'lchamni qayta hisoblash
      setTimeout(() => map.invalidateSize(), 320);
    }).catch((e) => {
      picker.replaceChildren(h('p', { class: 'map-picker__hint' }, `${e.message}. Havola yoki koordinatani pastga qo‘ying.`));
    });
  });
}
