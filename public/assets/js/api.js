// API mijozi: Bearer token, GET uchun bitta qayta urinish, oflayn holat,
// takroriy yuborishdan himoya, 401 da bir marta qayta kirish.

import { session, cache } from './store.js';

const BASE = 'api';
const TIMEOUT_MS = 15000;

export const MESSAGES = {
  offline: 'Internet yo‘q. Ulanganda qayta urinib ko‘ring.',
  network: 'Server bilan aloqa yo‘q. Qayta urinib ko‘ring.',
  server: 'Serverda xatolik. Birozdan keyin qayta urinib ko‘ring.',
  session: 'Sessiya tugadi. Ilovani guruhdagi tugmadan qayta oching.',
};

export class ApiError extends Error {
  constructor(status, code, message, details = null) {
    super(message);
    this.status = status;
    this.code = code;
    this.details = details;
  }
}

let reauthenticate = null;
let reauthPromise = null;
const inflight = new Map();

/** auth.js qayta kirish funksiyasini beradi. */
export function setReauth(fn) {
  reauthenticate = fn;
}

function buildUrl(path, query) {
  const qs = query
    ? Object.entries(query)
      .filter(([, v]) => v !== null && v !== undefined && v !== '')
      .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
      .join('&')
    : '';
  return `${BASE}${path}${qs ? `?${qs}` : ''}`;
}

async function send(method, url, body) {
  if (navigator.onLine === false) {
    throw new ApiError(0, 'offline', MESSAGES.offline);
  }
  const headers = { Accept: 'application/json' };
  if (body !== undefined) headers['Content-Type'] = 'application/json';
  if (session.token) headers.Authorization = `Bearer ${session.token}`;

  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), TIMEOUT_MS);
  let res;
  try {
    res = await fetch(url, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
      cache: 'no-store',
      credentials: 'omit',
      signal: controller.signal,
    });
  } catch {
    throw new ApiError(0, navigator.onLine === false ? 'offline' : 'network', navigator.onLine === false ? MESSAGES.offline : MESSAGES.network);
  } finally {
    clearTimeout(timer);
  }

  let payload = null;
  try {
    payload = await res.json();
  } catch {
    payload = null;
  }
  if (res.ok && payload && payload.ok) {
    return payload.data;
  }
  const err = payload?.error;
  if (err) {
    throw new ApiError(res.status, err.code ?? 'error', err.message ?? MESSAGES.server, err.details ?? null);
  }
  throw new ApiError(res.status, 'server_error', MESSAGES.server);
}

async function withReauth(method, path, query, body) {
  const url = buildUrl(path, query);
  try {
    return await send(method, url, body);
  } catch (e) {
    if (e instanceof ApiError && e.status === 401 && reauthenticate && path !== '/auth/telegram') {
      reauthPromise ??= reauthenticate().finally(() => { reauthPromise = null; });
      await reauthPromise;
      return send(method, url, body);
    }
    throw e;
  }
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/** GET: tarmoq yoki 5xx xatosida bitta qayta urinish. Natija keshga yoziladi. */
export async function get(path, query = null) {
  const key = buildUrl(path, query);
  try {
    const data = await withReauth('GET', path, query);
    cache.set(key, data);
    return data;
  } catch (e) {
    if (e instanceof ApiError && (e.code === 'network' || e.status >= 500)) {
      await sleep(700);
      const data = await withReauth('GET', path, query);
      cache.set(key, data);
      return data;
    }
    throw e;
  }
}

/** Keshdagi oxirgi javob (darhol chizish uchun). */
export function peek(path, query = null) {
  return cache.get(buildUrl(path, query));
}

/** Yozish: bir xil so'rov tugamaguncha qayta yuborilmaydi. Muvaffaqiyatdan keyin kesh tozalanadi. */
function write(method, path, body) {
  const key = `${method} ${path} ${JSON.stringify(body ?? null)}`;
  if (inflight.has(key)) return inflight.get(key);
  const p = withReauth(method, path, null, body ?? {})
    .then((data) => {
      cache.clear();
      return data;
    })
    .finally(() => inflight.delete(key));
  inflight.set(key, p);
  return p;
}

export const post = (path, body) => write('POST', path, body);
export const put = (path, body) => write('PUT', path, body);
export const del = (path) => write('DELETE', path, undefined);

/** Kirish (Authorization'siz ham ishlaydi). */
export function authenticate(initData) {
  return send('POST', buildUrl('/auth/telegram'), { init_data: initData });
}
