// To'y formasi (owner): Asosiy · Vaqt jadvali · Joy · Moliya · Qo'shimcha. Saqlash — Telegram MainButton.

import * as api from '../api.js';
import * as tg from '../tg.js';
import { h, clear, button, segment, switchControl, errorState, setLoading, eventRow, TYPE_LABELS } from '../ui/components.js';
import { openSheet } from '../ui/sheet.js';
import { toast } from '../ui/toast.js';
import { blockSkeleton } from '../ui/skeleton.js';
import { field, textInput, textArea, moneyInput, applyErrors } from '../ui/form.js';
import { todayISO } from '../lib/date-uz.js';
import { parseAmount } from '../lib/money.js';
import { parseCoordinates } from '../lib/maps.js';

const DURATIONS = [
  ['', 'Ko‘rsatilmagan'], ['60', '1 soat'], ['90', '1,5 soat'], ['120', '2 soat'], ['180', '3 soat'],
  ['240', '4 soat'], ['300', '5 soat'], ['360', '6 soat'], ['480', '8 soat'],
];

function section(title, ...children) {
  return h('section', { class: 'form-section' },
    h('h2', { class: 'block-title' }, title),
    h('div', { class: 'form-block' }, children));
}

export async function render(ctx) {
  const editId = ctx.params.id ?? null;
  const page = h('div', { class: 'page page--form' });
  ctx.root.appendChild(page);
  document.body.classList.add('lock-swipes');
  tg.setVerticalSwipes(false);

  let ev = null;
  if (editId) {
    page.appendChild(blockSkeleton(4));
    try {
      ev = await api.get(`/events/${editId}`);
    } catch (e) {
      if (!ctx.isCurrent()) return undefined;
      clear(page).appendChild(errorState(e.message, () => ctx.navigate(`/events/${editId}/edit`, { replace: true })));
      return undefined;
    }
    if (!ctx.isCurrent()) return undefined;
  }

  const v = (key, fallback = '') => (ev && ev[key] !== null && ev[key] !== undefined ? ev[key] : fallback);
  const initialDate = /^\d{4}-\d{2}-\d{2}$/.test(ctx.query.date ?? '') ? ctx.query.date : todayISO();

  // --- Asosiy
  const title = textInput('title', v('title'), { maxlength: '160', placeholder: 'Masalan: Kelin to‘yi — Aziz va Madina', required: true });
  let type = v('event_type', 'kelin');
  const typeChips = h('div', { class: 'choice-chips', role: 'radiogroup', 'aria-label': 'To‘y turi' });
  const drawTypes = () => {
    clear(typeChips);
    for (const [value, label] of Object.entries(TYPE_LABELS)) {
      typeChips.appendChild(h('button', {
        type: 'button',
        class: `choice t-${value}`,
        role: 'radio',
        'aria-checked': String(type === value),
        onClick: () => { type = value; tg.haptic.selection(); drawTypes(); },
      }, h('span', { class: 'chip__dot', 'aria-hidden': 'true' }), label));
    }
  };
  drawTypes();
  const date = textInput('event_date', v('event_date', initialDate), { type: 'date', required: true });
  let status = v('status', 'planned');
  const statusOptions = [{ value: 'planned', label: 'Rejada' }, { value: 'confirmed', label: 'Tasdiqlangan' }];
  if (ev && (ev.status === 'completed' || ev.event_date < todayISO())) statusOptions.push({ value: 'completed', label: 'O‘tkazilgan' });
  const statusLocked = ev && (ev.status === 'cancelled' || ev.status === 'archived');
  const statusSeg = segment(statusOptions, statusLocked ? 'planned' : status, (val) => { status = val; }, { label: 'Holat' });

  // --- Vaqt jadvali
  const time = (name, label, required = false) => field(label, textInput(name, v(name) ? String(v(name)).slice(0, 5) : '', { type: 'time', required }), { name });
  const duration = h('select', { class: 'select', name: 'duration_min' },
    DURATIONS.map(([val, label]) => h('option', { value: val }, label)));
  duration.value = DURATIONS.some(([val]) => val === String(v('duration_min'))) ? String(v('duration_min')) : '';

  // --- Joy
  const venueName = textInput('venue_name', v('venue_name'), { maxlength: '160', placeholder: 'Masalan: Sohil to‘yxonasi' });
  const venueAddress = textInput('venue_address', v('venue_address'), { maxlength: '255', placeholder: 'Ko‘cha, uy' });
  const district = textInput('district', v('district'), { maxlength: '100', placeholder: 'Masalan: Denov' });
  const coords = textInput('coords', ev && ev.latitude !== null && ev.latitude !== undefined ? `${ev.latitude}, ${ev.longitude}` : '', {
    inputmode: 'text',
    placeholder: '38.270000, 67.890000',
  });
  const coordsHint = h('span');
  let verified = Boolean(v('location_verified', false));
  const verifiedSwitch = switchControl(verified, (checked) => { verified = checked; }, 'Joylashuv tasdiqlangan');
  const verifiedInput = verifiedSwitch.querySelector('input');
  const syncCoords = () => {
    const raw = coords.value.trim();
    const parsed = parseCoordinates(raw);
    verifiedInput.disabled = !parsed;
    if (!parsed) {
      verifiedInput.checked = false;
      verified = false;
    }
    coordsHint.textContent = raw === ''
      ? 'Google Maps’da nuqtani bosib turing, koordinatani nusxalab shu yerga qo‘ying. Havolani ham qo‘yish mumkin.'
      : (parsed ? `Kenglik ${parsed.lat}, uzunlik ${parsed.lng}` : 'Koordinata tanilmadi. Masalan: 38.270000, 67.890000');
  };
  coords.addEventListener('input', syncCoords);
  const coordsField = field('Koordinata', coords, { name: 'latitude' });
  coordsField.insertBefore(h('p', { class: 'field__hint' }, coordsHint), coordsField.querySelector('.field__error'));
  syncCoords();

  // --- Moliya
  const price = moneyInput('total_price', v('total_price'));
  let visibility = v('financial_visibility', 'inherit');
  const visibilitySeg = segment([
    { value: 'inherit', label: 'Umumiy sozlama' },
    { value: 'private', label: 'Yashirin', iconName: 'eye-off' },
    { value: 'members', label: 'A’zolarga ochiq', iconName: 'eye' },
  ], visibility, (val) => { visibility = val; }, { label: 'Moliya ko‘rinishi' });

  // --- Qo'shimcha
  const clientName = textInput('client_name', v('client_name'), { maxlength: '128' });
  const clientPhone = textInput('client_phone', v('client_phone'), { type: 'tel', inputmode: 'tel', maxlength: '32', placeholder: '+998 90 123 45 67' });
  const perf = textArea('performance_notes', v('performance_notes'), { maxlength: '2000', placeholder: 'Qo‘shiqlar, tartib, mehmonlar' });
  const equip = textArea('equipment_notes', v('equipment_notes'), { maxlength: '2000', placeholder: 'Kolonka, mikrofonlar, sintezator' });
  const dress = textInput('dress_code', v('dress_code'), { maxlength: '255', placeholder: 'Masalan: qora kostyum, oq ko‘ylak' });
  const schedNotes = textArea('schedule_notes', v('schedule_notes'), { maxlength: '2000' });
  const internal = textArea('internal_notes', v('internal_notes'), { maxlength: '2000' });

  const form = h('form', { class: 'event-form', novalidate: true },
    h('h1', { class: 'page-title' }, editId ? 'To‘yni tahrirlash' : 'Yangi to‘y'),
    section('Asosiy',
      field('Sarlavha', title, { name: 'title' }),
      h('div', { class: 'field', dataset: { field: 'event_type' } }, h('span', { class: 'field__label' }, 'To‘y turi'), typeChips),
      field('Sana', date, { name: 'event_date' }),
      statusLocked ? null : h('div', { class: 'field' }, h('span', { class: 'field__label' }, 'Holat'), statusSeg)),
    section('Vaqt jadvali',
      h('div', { class: 'field-pair' }, time('start_time', 'Boshlanish', true), time('soundcheck_time', 'Soundcheck')),
      h('div', { class: 'field-pair' }, time('nahor_time', 'Nahor'), time('abet_time', 'Abet')),
      h('div', { class: 'field-pair' }, time('assembly_time', 'Yig‘ilish'), time('departure_time', 'Jo‘nash')),
      field('Davomiyligi', duration, { name: 'duration_min' })),
    section('Joy',
      field('To‘yxona', venueName, { name: 'venue_name' }),
      field('Manzil', venueAddress, { name: 'venue_address' }),
      field('Tuman', district, { name: 'district' }),
      coordsField,
      h('div', { class: 'switch-row' },
        h('span', { class: 'switch-row__text' },
          h('span', { class: 'switch-row__label' }, 'Joylashuv tasdiqlangan'),
          h('span', { class: 'field__hint' }, 'Faqat nuqtani o‘zingiz tekshirgan bo‘lsangiz yoqing.')),
        verifiedSwitch)),
    section('Moliya',
      field('Kelishilgan narx', price, { name: 'total_price' }),
      h('div', { class: 'field', dataset: { field: 'financial_visibility' } }, h('span', { class: 'field__label' }, 'Moliya ko‘rinishi'), visibilitySeg)),
    section('Qo‘shimcha',
      field('Mijoz ismi', clientName, { name: 'client_name', hint: 'Faqat siz ko‘rasiz.' }),
      field('Telefon', clientPhone, { name: 'client_phone' }),
      field('Ijro izohi', perf, { name: 'performance_notes' }),
      field('Jihozlar', equip, { name: 'equipment_notes' }),
      field('Dress code', dress, { name: 'dress_code' }),
      field('Vaqt jadvali izohi', schedNotes, { name: 'schedule_notes' }),
      field('Ichki izoh', internal, { name: 'internal_notes', hint: 'Faqat siz ko‘rasiz.' })));

  const val = (name) => form.querySelector(`[name="${name}"]`)?.value.trim() ?? '';

  function payload(confirmConflict) {
    const parsed = parseCoordinates(coords.value);
    return {
      title: title.value.trim(),
      event_type: type,
      event_date: date.value,
      status: statusLocked ? undefined : status,
      start_time: val('start_time'),
      soundcheck_time: val('soundcheck_time') || null,
      nahor_time: val('nahor_time') || null,
      abet_time: val('abet_time') || null,
      assembly_time: val('assembly_time') || null,
      departure_time: val('departure_time') || null,
      duration_min: duration.value ? Number(duration.value) : null,
      venue_name: venueName.value.trim(),
      venue_address: venueAddress.value.trim(),
      district: district.value.trim(),
      latitude: parsed ? parsed.lat : null,
      longitude: parsed ? parsed.lng : null,
      location_verified: Boolean(parsed) && verified,
      total_price: parseAmount(price.input.value),
      financial_visibility: visibility,
      client_name: clientName.value.trim(),
      client_phone: clientPhone.value.trim(),
      performance_notes: perf.value.trim(),
      equipment_notes: equip.value.trim(),
      dress_code: dress.value.trim(),
      schedule_notes: schedNotes.value.trim(),
      internal_notes: internal.value.trim(),
      confirm_conflict: confirmConflict,
    };
  }

  function localErrors() {
    const errors = {};
    if (!title.value.trim()) errors.title = 'Sarlavha kiritilishi shart.';
    if (!date.value) errors.event_date = 'Sana kiritilishi shart.';
    if (!val('start_time')) errors.start_time = 'Boshlanish vaqti kiritilishi shart.';
    if (coords.value.trim() && !parseCoordinates(coords.value)) errors.latitude = 'Koordinata tanilmadi. Masalan: 38.270000, 67.890000';
    return errors;
  }

  let saving = false;
  const fallbackBtn = tg.hasMainButton() ? null : button('Saqlash', { variant: 'primary', block: true, type: 'submit' });

  function setSaving(on) {
    saving = on;
    tg.setMainButtonLoading(on);
    setLoading(fallbackBtn, on);
  }

  async function save(confirmConflict = false) {
    if (saving) return;
    const errors = localErrors();
    if (Object.keys(errors).length) {
      tg.haptic.notify('error');
      applyErrors(form, errors);
      return;
    }
    applyErrors(form, {});
    setSaving(true);
    try {
      const body = payload(confirmConflict);
      const saved = editId ? await api.put(`/events/${editId}`, body) : await api.post('/events', body);
      tg.haptic.notify('success');
      toast.success('Saqlandi');
      ctx.navigate(`/events/${saved.id}`, { replace: true });
    } catch (e) {
      if (e.code === 'conflict') {
        tg.haptic.notify('warning');
        showConflict(e);
      } else if (e.code === 'validation' && e.details) {
        tg.haptic.notify('error');
        applyErrors(form, e.details);
      } else {
        toast.error(e.message, e.code === 'network' || e.code === 'offline' ? { retry: () => save(confirmConflict) } : undefined);
      }
    } finally {
      setSaving(false);
    }
  }

  function showConflict(e) {
    const conflicts = e.details?.conflicts ?? [];
    const proceed = button('Baribir saqlash', { variant: 'primary', block: true, onClick: () => { sheet.close(); save(true); } });
    const backBtn = button('Orqaga', { variant: 'ghost', block: true, onClick: () => sheet.close() });
    const sheet = openSheet({
      title: `Bu kunda yana ${conflicts.length} ta to‘y bor`,
      content: h('div', { class: 'block conflict-list' }, conflicts.map((c) => {
        const r = eventRow(c, { href: `#/events/${c.id}` });
        r.addEventListener('click', (ev2) => ev2.preventDefault());
        return r;
      })),
      footer: h('div', { class: 'stack-buttons' }, proceed, backBtn),
    });
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    save();
  });

  clear(page).appendChild(form);
  if (fallbackBtn) {
    form.appendChild(h('div', { class: 'sticky-action' }, fallbackBtn));
  } else {
    tg.showMainButton('Saqlash', () => save());
  }
  if (!editId) setTimeout(() => title.focus({ preventScroll: true }), 200);

  return () => {
    tg.hideMainButton();
  };
}
