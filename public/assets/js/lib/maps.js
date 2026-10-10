// Xarita havolalari. Koordinata tasdiqlanmagan bo'lsa — manzil matni bo'yicha qidiruv.

function addressQuery(event) {
  return [event.venue_name, event.venue_address, event.district]
    .map((s) => String(s ?? '').trim())
    .filter(Boolean)
    .join(', ');
}

export function hasPoint(event) {
  return Boolean(event.location_verified) && event.latitude !== null && event.longitude !== null
    && event.latitude !== undefined && event.longitude !== undefined;
}

export function hasLocation(event) {
  return hasPoint(event) || addressQuery(event) !== '';
}

export function googleMapsUrl(event) {
  if (hasPoint(event)) {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${event.latitude},${event.longitude}`)}`;
  }
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(addressQuery(event))}`;
}

export function yandexMapsUrl(event) {
  if (hasPoint(event)) {
    const pt = `${event.longitude},${event.latitude}`;
    return `https://yandex.uz/maps/?pt=${encodeURIComponent(pt)}&z=17&l=map`;
  }
  return `https://yandex.uz/maps/?text=${encodeURIComponent(addressQuery(event))}`;
}

/** "41.311081, 69.240562" yoki Google Maps havolasidan koordinata ajratish. */
export function parseCoordinates(text) {
  const s = String(text ?? '');
  const at = s.match(/@(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/);
  const q = s.match(/(-?\d{1,2}\.\d{3,})\s*[,;\s]\s*(-?\d{1,3}\.\d{3,})/);
  const m = at || q;
  if (!m) return null;
  const lat = Number(m[1]);
  const lng = Number(m[2]);
  if (Math.abs(lat) > 90 || Math.abs(lng) > 180) return null;
  return { lat: lat.toFixed(6), lng: lng.toFixed(6) };
}
