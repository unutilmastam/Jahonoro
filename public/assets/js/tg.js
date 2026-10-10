// Telegram.WebApp ustidan yupqa qatlam. Telegram tashqarisida hamma funksiya xavfsiz "hech narsa qilmaydi".

const wa = () => window.Telegram?.WebApp;

/** tokens.css dagi rang (Telegram API hex talab qiladi). */
function token(name, scope = document.documentElement) {
  return getComputedStyle(scope).getPropertyValue(name).trim();
}

function supports(version) {
  const app = wa();
  return Boolean(app && typeof app.isVersionAtLeast === 'function' && app.isVersionAtLeast(version));
}

export function isTelegram() {
  return Boolean(wa() && wa().initData);
}

export function initData() {
  return wa()?.initData ?? '';
}

export function startParam() {
  return wa()?.initDataUnsafe?.start_param ?? '';
}

export function colorScheme() {
  return wa()?.colorScheme === 'light' ? 'light' : 'dark';
}

export function applyTheme() {
  const scheme = colorScheme();
  document.documentElement.dataset.theme = scheme;
  const app = wa();
  if (!app) return scheme;
  try {
    if (supports('6.1')) {
      app.setHeaderColor(token('--bg'));
      app.setBackgroundColor(token('--bg'));
    }
    if (supports('7.10') && typeof app.setBottomBarColor === 'function') {
      app.setBottomBarColor(token('--bg'));
    }
  } catch { /* eski klientlar */ }
  return scheme;
}

function applySafeArea() {
  const app = wa();
  const root = document.documentElement.style;
  const set = (name, inset) => {
    if (!inset) return;
    for (const side of ['top', 'right', 'bottom', 'left']) {
      root.setProperty(`--${name}-${side}`, `${inset[side] ?? 0}px`);
    }
  };
  set('tg-safe', app?.safeAreaInset);
  set('tg-content-safe', app?.contentSafeAreaInset);
}

function applyViewport() {
  const app = wa();
  if (app?.viewportStableHeight) {
    document.documentElement.style.setProperty('--tg-viewport-stable-height', `${app.viewportStableHeight}px`);
  }
}

export function init() {
  const app = wa();
  applyTheme();
  if (!app) return;
  try {
    app.ready();
    app.expand();
  } catch { /* */ }
  applyViewport();
  applySafeArea();
  app.onEvent?.('themeChanged', applyTheme);
  app.onEvent?.('viewportChanged', applyViewport);
  app.onEvent?.('safeAreaChanged', applySafeArea);
  app.onEvent?.('contentSafeAreaChanged', applySafeArea);
}

export function setVerticalSwipes(enabled) {
  const app = wa();
  if (!app || !supports('7.7')) return;
  try {
    if (enabled) app.enableVerticalSwipes();
    else app.disableVerticalSwipes();
  } catch { /* */ }
}

// --- BackButton ---------------------------------------------------------

let backHandler = null;

export function showBack(handler) {
  const app = wa();
  if (!app || !supports('6.1')) return;
  if (backHandler) app.BackButton.offClick(backHandler);
  backHandler = handler;
  app.BackButton.onClick(backHandler);
  app.BackButton.show();
}

export function hideBack() {
  const app = wa();
  if (!app || !supports('6.1')) return;
  if (backHandler) app.BackButton.offClick(backHandler);
  backHandler = null;
  app.BackButton.hide();
}

// --- MainButton ---------------------------------------------------------

let mainHandler = null;

export function hasMainButton() {
  return Boolean(wa()?.MainButton) && isTelegram();
}

export function showMainButton(text, handler) {
  const app = wa();
  if (!hasMainButton()) return false;
  const mb = app.MainButton;
  if (mainHandler) mb.offClick(mainHandler);
  mainHandler = handler;
  mb.setParams({ text, color: token('--accent'), text_color: token('--accent-ink'), is_active: true, is_visible: true });
  mb.onClick(mainHandler);
  mb.show();
  return true;
}

export function setMainButtonLoading(loading) {
  const mb = wa()?.MainButton;
  if (!mb) return;
  if (loading) {
    mb.showProgress?.(false);
    mb.disable?.();
  } else {
    mb.hideProgress?.();
    mb.enable?.();
  }
}

export function hideMainButton() {
  const mb = wa()?.MainButton;
  if (!mb) return;
  if (mainHandler) mb.offClick(mainHandler);
  mainHandler = null;
  mb.hideProgress?.();
  mb.hide();
}

// --- Haptic -------------------------------------------------------------

export const haptic = {
  impact(style = 'light') {
    try { if (supports('6.1')) wa().HapticFeedback.impactOccurred(style); } catch { /* */ }
  },
  selection() {
    try { if (supports('6.1')) wa().HapticFeedback.selectionChanged(); } catch { /* */ }
  },
  notify(type) {
    try { if (supports('6.1')) wa().HapticFeedback.notificationOccurred(type); } catch { /* */ }
  },
};

// --- Havolalar va dialoglar ---------------------------------------------

export function openLink(url) {
  const app = wa();
  if (app && typeof app.openLink === 'function' && isTelegram()) {
    app.openLink(url);
  } else {
    window.open(url, '_blank', 'noopener,noreferrer');
  }
}

/** t.me havolasini Telegram ichida ochadi (guruh tanlash oynasi va h.k.). */
export function openTelegramLink(url) {
  const app = wa();
  if (app && typeof app.openTelegramLink === 'function' && isTelegram()) {
    app.openTelegramLink(url);
  } else {
    window.open(url, '_blank', 'noopener,noreferrer');
  }
}

export function close() {
  wa()?.close?.();
}
