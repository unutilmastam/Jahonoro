// To'y formasi (owner): Asosiy · Vaqtlar · Joylar · Moliya · Qo'shimcha. Saqlash — Telegram MainButton.

import * as api from '../api.js';
import * as tg from '../tg.js';
import { h, clear, button, segment, errorState, setLoading, eventRow, TYPE_LABELS } from '../ui/components.js';
import { icon } from '../ui/icons.js';
import { openSheet } from '../ui/sheet.js';
import { toast } from '../ui/toast.js';
import { blockSkeleton } from '../ui/skeleton.js';
import { field, textInput, textArea, moneyInput, applyErrors } from '../ui/form.js';
import { TIME_SLOTS } from '../ui/ticket.js';
import { todayISO } from '../lib/date-uz.js';
import { parseAmount } from '../lib/money.js';
import { pickLocation, miniMap } from '../lib/map.js';

function section(title, ...children) {
  return h('section', { class: 'form-section' },
    h('h2', { class: 'block-title' }, title),
    h('div', { class: 'form-block' }, children));
}

/**
 * Joy maydoni: nom/manzil + xaritadagi nuqta (owner o'zi qo'yadi).
 * @returns {{el: HTMLElement, input: HTMLInputElement, point: () => ({lat: string, lng: string}|null)}}
 */
function placeField({ label, name, value, placeholder, maxlength, lat, lng, pointField, mapTitle }) {
  let point = lat !== null && lat !== undefined && lng !== null && lng !== undefined
    ? { lat: String(lat), lng: String(lng) }
    : null;
  const input = textInput(name, value, { maxlength: String(maxlength), placeholder });
  const mapBox = h('div', { class: 'place-field__map' });
  const actions = h('div', { class: 'place-field__actions' });
  const wrap = h('div', { class: 'place-field field', dataset: { field: pointField } });
  const error = h('p', { class: 'field__error', hidden: true });

  wrap.setError = (message) => {
    error.hidden = !message;
    error.textContent = message || '';
  };

  const draw = () => {
    clear(actions);
    mapBox.hidden = !point;
    clear(mapBox);
    if (point) {
      // Har safar yangi konteyner: Leaflet bitta elementga ikki marta ulanmaydi
      const inner = h('div');
      mapBox.appendChild(inner);
      miniMap(inner, Number(point.lat), Number(point.lng));
      actions.append(
        button('Nuqtani o‘zgartirish', { variant: 'secondary', size: 'sm', iconName: 'map-pin', onClick: pick }),
        button('O‘chirish', { variant: 'ghost', size: 'sm', onClick: () => { point = null; draw(); } }),
      );
    } else {
      actions.append(button('Xaritadan belgilash', { variant: 'secondary', size: 'sm', iconName: 'map-pin', onClick: pick }));
    }
  };

  async function pick() {
    const chosen = await pickLocation({ title: mapTitle, lat: point?.lat ?? null, lng: point?.lng ?? null });
    if (chosen) {
      point = chosen;
      tg.haptic.notify('success');
      draw();
    }
  }

  wrap.append(field(label, input, { name }), mapBox, actions, error);
  draw();
  return { el: wrap, input, point: () => point };
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
  const title = textInput('title', v('title'), { maxlength: '160', placeholder: 'Masalan: Kelin to‘yi — Aziz va Madina' });
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
  const date = textInput('event_date', v('event_date', initialDate), { type: 'date' });
  let status = v('status', 'planned');
  const statusOptions = [{ value: 'planned', label: 'Rejada' }, { value: 'confirmed', label: 'Tasdiqlangan' }];
  if (ev && (ev.status === 'completed' || ev.event_date < todayISO())) statusOptions.push({ value: 'completed', label: 'O‘tkazilgan' });
  const statusLocked = ev && (ev.status === 'cancelled' || ev.status === 'archived');
  const statusSeg = segment(statusOptions, statusLocked ? 'planned' : status, (val) => { status = val; }, { label: 'Holat' });

  // --- Vaqtlar
  const timeInputs = {};
  const timeCells = TIME_SLOTS.map((slot) => {
    const input = h('input', { class: 'input', type: 'time', name: slot.key, 'aria-label': slot.full });
    input.value = v(slot.key) ? String(v(slot.key)).slice(0, 5) : '';
    timeInputs[slot.key] = input;
    return h('label', { class: 'time-field' },
      h('span', { class: 'time-field__head' }, icon(slot.iconName, { size: 18 }), slot.full),
      input);
  });
  const timeGrid = h('div', { class: 'time-grid' }, timeCells);
  const timesError = h('p', { class: 'field__error', hidden: true });
  const timesField = h('div', { class: 'field', dataset: { field: 'times' } }, timeGrid, timesError);
  timesField.setError = (message) => {
    timeGrid.classList.toggle('has-error', Boolean(message));
    timesError.hidden = !message;
    timesError.textContent = message || '';
  };

  // --- Joylar
  const venue = placeField({
    label: 'To‘yxona nomi',
    name: 'venue_name',
    value: v('venue_name'),
    placeholder: 'Masalan: Sohil to‘yxonasi',
    maxlength: 160,
    lat: ev?.latitude ?? null,
    lng: ev?.longitude ?? null,
    pointField: 'latitude',
    mapTitle: 'To‘yxona joylashuvi',
  });
  const home = placeField({
    label: 'To‘y egasining manzili',
    name: 'home_address',
    value: v('home_address'),
    placeholder: 'Masalan: Denov, Bog‘ishamol ko‘chasi 5',
    maxlength: 255,
    lat: ev?.home_latitude ?? null,
    lng: ev?.home_longitude ?? null,
    pointField: 'home_latitude',
    mapTitle: 'To‘y egasining uyi',
  });

  // --- Moliya
  const price = moneyInput('total_price', v('total_price'));
  let visibility = v('financial_visibility', 'inherit');
  const visibilitySeg = segment([
    { value: 'inherit', label: 'Umumiy' },
    { value: 'private', label: 'Yashirin', iconName: 'eye-off' },
    { value: 'members', label: 'Ochiq', iconName: 'eye' },
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
    section('Vaqtlar',
      timesField,
      h('p', { class: 'field__hint' }, 'Nahor, abet yoki kechdan kamida bittasini kiriting. Bo‘sh qolganlari ko‘rinmaydi.')),
    section('To‘yxona', venue.el),
    section('To‘y egasining uyi', home.el),
    section('Moliya',
      field('Kelishilgan narx', price, { name: 'total_price' }),
      h('div', { class: 'field', dataset: { field: 'financial_visibility' } },
        h('span', { class: 'field__label' }, 'A’zolarga ko‘rinishi'),
        visibilitySeg,
        h('p', { class: 'field__hint' }, '“Umumiy” — Sozlamalardagi tanlov amal qiladi.'))),
    section('Qo‘shimcha',
      field('Mijoz ismi', clientName, { name: 'client_name', hint: 'Faqat siz ko‘rasiz.' }),
      field('Telefon', clientPhone, { name: 'client_phone' }),
      field('Ijro izohi', perf, { name: 'performance_notes' }),
      field('Jihozlar', equip, { name: 'equipment_notes' }),
      field('Dress code', dress, { name: 'dress_code' }),
      field('Qo‘shimcha izoh', schedNotes, { name: 'schedule_notes' }),
      field('Ichki izoh', internal, { name: 'internal_notes', hint: 'Faqat siz ko‘rasiz.' })));

  const timeValue = (key) => timeInputs[key].value || null;

  function payload(confirmConflict) {
    const vp = venue.point();
    const hp = home.point();
    return {
      title: title.value.trim(),
      event_type: type,
      event_date: date.value,
      status: statusLocked ? undefined : status,
      assembly_time: timeValue('assembly_time'),
      nahor_time: timeValue('nahor_time'),
      abet_time: timeValue('abet_time'),
      evening_time: timeValue('evening_time'),
      venue_name: venue.input.value.trim(),
      latitude: vp ? vp.lat : null,
      longitude: vp ? vp.lng : null,
      home_address: home.input.value.trim(),
      home_latitude: hp ? hp.lat : null,
      home_longitude: hp ? hp.lng : null,
      // Avvalgi versiyada kiritilgan qiymatlar o'chib ketmasin
      venue_address: ev?.venue_address ?? '',
      district: ev?.district ?? '',
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
    if (!timeValue('nahor_time') && !timeValue('abet_time') && !timeValue('evening_time')) {
      errors.times = 'Nahor, abet yoki kech vaqtidan kamida bittasini kiriting.';
    }
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
