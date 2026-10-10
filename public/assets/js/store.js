// Xotiradagi holat: sessiya (token faqat shu yerda) va GET javoblari keshi.

const state = {
  token: null,
  user: null,
  cache: new Map(),
  flags: new Set(),
};

export const session = {
  get token() { return state.token; },
  get user() { return state.user; },
  set(token, user) {
    state.token = token;
    state.user = user;
  },
  clear() {
    state.token = null;
    state.user = null;
  },
  isOwner() {
    return state.user?.role === 'owner';
  },
};

export const cache = {
  get(key) {
    return state.cache.get(key);
  },
  set(key, value) {
    state.cache.set(key, value);
  },
  delete(key) {
    state.cache.delete(key);
  },
  /** Har yozishdan keyin: hamma ro'yxatlar eskiradi. */
  clear() {
    state.cache.clear();
  },
};

/** Sessiyada bir martalik belgilar (masalan, chipta animatsiyasi ko'rsatildi). */
export const once = {
  has(flag) { return state.flags.has(flag); },
  mark(flag) { state.flags.add(flag); },
};
