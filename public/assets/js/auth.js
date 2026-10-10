// Kirish: initData → token. Token faqat xotirada (store.js), ilova qayta ochilsa qayta almashinuv.

import { authenticate, setReauth, ApiError, MESSAGES } from './api.js';
import { session } from './store.js';
import { initData, isTelegram } from './tg.js';

/**
 * @returns {Promise<{ok: true, user: object} | {ok: false, reason: string, message: string}>}
 */
export async function login() {
  if (!isTelegram()) {
    return { ok: false, reason: 'outside', message: 'Bu ilova faqat JAHONORO guruhidagi tugma orqali ochiladi.' };
  }
  try {
    const data = await authenticate(initData());
    session.set(data.token, data.user);
    return { ok: true, user: data.user };
  } catch (e) {
    if (e instanceof ApiError) {
      if (e.code === 'pending') return { ok: false, reason: 'pending', message: e.message };
      if (e.code === 'no_access') return { ok: false, reason: 'no_access', message: e.message };
      if (e.status === 401) return { ok: false, reason: 'expired', message: e.message || MESSAGES.session };
      if (e.code === 'offline' || e.code === 'network') return { ok: false, reason: 'offline', message: e.message };
      return { ok: false, reason: 'error', message: e.message };
    }
    return { ok: false, reason: 'error', message: MESSAGES.server };
  }
}

// Token muddati tugasa (12 soat) — initData bilan qayta kirish.
setReauth(async () => {
  const result = await login();
  if (!result.ok) {
    session.clear();
    throw new ApiError(401, 'unauthorized', MESSAGES.session);
  }
});
