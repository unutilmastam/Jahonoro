// Sozlamalar (owner): Moliya ko'rinishi · Eslatmalar · A'zolar · Guruh · Tizim holati.

import * as api from '../api.js';
import * as tg from '../tg.js';
import { h, clear, listBlock, row, switchControl, button, errorState, setLoading, avatar } from '../ui/components.js';
import { icon } from '../ui/icons.js';
import { openSheet, confirmSheet } from '../ui/sheet.js';
import { toast } from '../ui/toast.js';
import { blockSkeleton, delayedSkeleton } from '../ui/skeleton.js';
import { field, textInput, applyErrors } from '../ui/form.js';
import { timeAgo, formatDayMonth, formatTime } from '../lib/date-uz.js';

const HOURS = [6, 12, 24, 36, 48];

const REMINDER_STATUS = {
  pending: ['Kutilmoqda', ''],
  sending: ['Yuborilmoqda', ''],
  sent: ['Yuborildi', 's-confirmed'],
  failed: ['Xato', 's-cancelled'],
};

const MEMBER_STATUS = {
  pending: 'Tasdiq kutmoqda',
  active: 'Faol',
  disabled: 'O‘chirilgan',
};

export async function render(ctx) {
  const page = h('div', { class: 'page page--settings' },
    h('h1', { class: 'page-title' }, 'Sozlamalar'));
  const body = h('div', { class: 'settings-body' });
  const systemSlot = h('div', { class: 'settings-system' });
  page.append(body, systemSlot);
  ctx.root.appendChild(page);

  let settings = null;
  let members = null;
  let reminders = null;
  let group = null;

  function financeSection() {
    const sw = switchControl(settings.finance_visible_to_members, async (checked, input) => {
      input.disabled = true;
      try {
        settings = await api.put('/settings', { finance_visible_to_members: checked });
        toast.success('Saqlandi');
      } catch (e) {
        input.checked = !checked;
        toast.error(e.message);
      } finally {
        input.disabled = false;
      }
    }, 'Narxni a’zolarga ko‘rsatish');
    return h('section', { class: 'block-group' },
      h('h2', { class: 'block-title' }, 'Moliya ko‘rinishi'),
      h('div', { class: 'block' },
        h('div', { class: 'row' },
          h('span', { class: 'row__icon' }, icon(settings.finance_visible_to_members ? 'eye' : 'eye-off', { size: 20 })),
          h('span', { class: 'row__main' },
            h('span', { class: 'row__label' }, 'Narxni a’zolarga ko‘rsatish'),
            h('span', { class: 'row__sub' }, 'Kelishilgan narx, to‘langan va qoldiq. Mijoz va izohlar hech qachon ko‘rinmaydi.')),
          sw)),
      h('p', { class: 'block-note' }, 'To‘y formasidagi “Yashirin” yoki “A’zolarga ochiq” tanlovi shu sozlamadan ustun turadi.'));
  }

  function remindersSection() {
    const select = h('select', { class: 'select select--inline', 'aria-label': 'Necha soat oldin' },
      HOURS.map((n) => h('option', { value: String(n) }, `${n} soat`)));
    select.value = String(settings.reminder_hours_before);
    if (!HOURS.includes(settings.reminder_hours_before)) {
      select.appendChild(h('option', { value: String(settings.reminder_hours_before) }, `${settings.reminder_hours_before} soat`));
      select.value = String(settings.reminder_hours_before);
    }
    select.addEventListener('change', async () => {
      const previous = settings.reminder_hours_before;
      select.disabled = true;
      try {
        settings = await api.put('/settings', { reminder_hours_before: Number(select.value) });
        toast.success('Saqlandi');
        reminders = await api.get('/reminders/status').catch(() => reminders);
        draw();
      } catch (e) {
        select.value = String(previous);
        toast.error(e.message);
      } finally {
        select.disabled = false;
      }
    });

    const items = (reminders?.items ?? []).slice(0, 5).map((r) => {
      const [label, cls] = REMINDER_STATUS[r.status] ?? [r.status, ''];
      const when = r.status === 'sent' && r.sent_at ? `yuborildi ${timeAgo(r.sent_at)}` : `${formatDayMonth(r.scheduled_at)}, ${formatTime(r.scheduled_at.slice(11))}`;
      return row(r.title, null, {
        sub: [`${formatDayMonth(r.event_date)} to‘yi`, when, r.status === 'failed' && r.last_error ? r.last_error : null].filter(Boolean).join(' · '),
        trailing: h('span', { class: `chip chip--status ${cls}` }, label),
        href: `#/events/${r.event_id}`,
      });
    });

    return h('section', { class: 'block-group' },
      h('h2', { class: 'block-title' }, 'Eslatmalar'),
      h('div', { class: 'block' },
        h('div', { class: 'row' },
          h('span', { class: 'row__icon' }, icon('bell', { size: 20 })),
          h('span', { class: 'row__main' },
            h('span', { class: 'row__label' }, 'Guruhga necha soat oldin'),
            h('span', { class: 'row__sub' }, 'To‘y boshlanishidan oldin, bir marta')),
          select)),
      items.length ? h('div', { class: 'block' }, items) : h('p', { class: 'block-note' }, 'Hozircha eslatmalar yo‘q.'));
  }

  function membersSection() {
    const list = members?.items ?? [];
    const rows = list.map((m) => {
      const isPending = m.status === 'pending';
      const r = h('button', { type: 'button', class: `row row--action member-row${m.status === 'disabled' ? ' is-muted' : ''}`, onClick: () => openMember(m) },
        avatar(m.name),
        h('span', { class: 'row__main' },
          h('span', { class: 'row__label' }, m.name),
          h('span', { class: 'row__sub' }, m.role === 'owner'
            ? 'Rahbar'
            : [MEMBER_STATUS[m.status], m.username ? `@${m.username}` : null, !m.in_group && m.status !== 'pending' ? 'guruhda emas' : null].filter(Boolean).join(' · '))),
        isPending ? h('span', { class: 'badge-dot', 'aria-label': 'Tasdiq kutmoqda' }) : null,
        h('span', { class: 'row__chevron' }, icon('chevron-right', { size: 20 })));
      if (m.role === 'owner') r.disabled = true;
      return r;
    });
    rows.push(row('A’zo qo‘shish', null, { iconName: 'plus', onClick: openAddMember }));
    return listBlock('A’zolar', ...rows);
  }

  function groupSection() {
    const g = group ?? { linked: false, title: '', source: 'none' };
    const rows = [];
    if (g.linked) {
      rows.push(row(g.title || 'Guruh', null, {
        iconName: 'users',
        sub: g.source === 'config' ? 'config.php orqali ulangan' : 'Ilova faqat shu guruh a’zolariga ochiladi',
        trailing: h('span', { class: 'chip chip--status s-confirmed' }, 'Ulangan'),
      }));
      rows.push(row('Guruhga tugma yuborish', null, { iconName: 'bell', onClick: publishButton }));
      rows.push(row('Boshqa guruhni ulash', null, { iconName: 'refresh', onClick: openLinkSheet }));
    }
    return h('section', { class: 'block-group' },
      h('h2', { class: 'block-title' }, 'Guruh'),
      g.linked
        ? h('div', { class: 'block' }, rows)
        : h('div', { class: 'block group-empty' },
          h('div', { class: 'group-empty__body' },
            h('p', { class: 'group-empty__title' }, 'Guruh ulanmagan'),
            h('p', { class: 'group-empty__text' }, 'Yopiq guruhingizni ulang. Ilova faqat shu guruh a’zolariga ochiladi, eslatmalar shu guruhga boradi.')),
          h('div', { class: 'group-empty__action' },
            button('Guruhni ulash', { variant: 'primary', iconName: 'plus', block: true, onClick: openLinkSheet }))),
      g.linked ? h('p', { class: 'block-note' }, 'Guruhdan chiqqan odam keyingi tekshiruvda (10 daqiqa ichida) ilovaga kira olmaydi.') : null);
  }

  function openLinkSheet() {
    let timer = null;
    let started = false;
    const statusLine = h('p', { class: 'link-status', 'aria-live': 'polite' });
    const steps = h('ol', { class: 'link-steps' },
      h('li', null, 'Pastdagi tugmani bosing — Telegram guruh tanlash oynasini ochadi.'),
      h('li', null, 'JAHONORO guruhini tanlang va botni admin qilib qo‘shing (faqat “Xabarlarni pin qilish” huquqi kerak).'),
      h('li', null, 'Shu oynaga qayting — guruh o‘zi ulanadi va guruhga “TO‘YLAR JADVALI” tugmasi yuboriladi.'));
    const go = button('Telegram’da guruhni tanlash', { variant: 'primary', block: true, iconName: 'users' });
    const close = button('Yopish', { variant: 'ghost', block: true, onClick: () => sheet.close() });

    const setStatus = (text, waiting) => {
      clear(statusLine).append(waiting ? h('span', { class: 'spinner', 'aria-hidden': 'true' }) : '', text);
      statusLine.hidden = !text;
    };
    setStatus('', false);

    const poll = async () => {
      try {
        const res = await api.post('/group/link/check');
        if (res.just_linked || (res.linked && res.source === 'app' && !res.pending)) {
          clearTimeout(timer);
          timer = null;
          group = res;
          tg.haptic.notify('success');
          sheet.close();
          toast.success(`“${res.title || 'Guruh'}” ulandi`);
          draw();
          return;
        }
        if (!res.pending) {
          setStatus('Kod muddati tugadi. Tugmani qayta bosing.', false);
          started = false;
          return;
        }
      } catch (e) {
        setStatus(e.message, false);
        if (e.code === 'webhook_set') return;
      }
      timer = setTimeout(poll, 3000);
    };

    go.addEventListener('click', async () => {
      setLoading(go, true);
      try {
        const link = await api.post('/group/link');
        tg.openTelegramLink(link.url);
        setStatus('Guruh tanlanishi kutilmoqda…', true);
        if (!started) {
          started = true;
          clearTimeout(timer);
          timer = setTimeout(poll, 3000);
        }
      } catch (e) {
        toast.error(e.message);
      } finally {
        setLoading(go, false);
      }
    });

    const sheet = openSheet({
      title: 'Guruhni ulash',
      content: h('div', null, steps, statusLine),
      footer: h('div', { class: 'stack-buttons' }, go, close),
      onClose: () => clearTimeout(timer),
    });
  }

  function draw() {
    clear(body).append(groupSection(), financeSection(), remindersSection(), membersSection());
  }

  async function drawSystem() {
    clear(systemSlot).appendChild(h('section', { class: 'block-group' }, h('h2', { class: 'block-title' }, 'Tizim holati'), blockSkeleton(3)));
    try {
      const s = await api.get('/health');
      if (!ctx.isCurrent()) return;
      const cronText = s.cron_last_run ? timeAgo(s.cron_last_run) : 'Hali ishlamagan';
      clear(systemSlot).appendChild(listBlock('Tizim holati',
        row('Telegram ulanishi', null, {
          iconName: 'shield',
          sub: s.bot_username ? `@${s.bot_username}` : null,
          trailing: h('span', { class: `chip chip--status ${s.telegram_api === 'ok' ? 's-confirmed' : 's-cancelled'}` }, s.telegram_api === 'ok' ? 'Ishlayapti' : 'Ulanmagan'),
        }),
        row('Cron (eslatmalar)', null, {
          iconName: 'clock',
          sub: `Oxirgi ishlagan: ${cronText}`,
          trailing: h('span', { class: `chip chip--status ${s.cron_ok ? 's-confirmed' : 's-cancelled'}` }, s.cron_ok ? 'Ishlayapti' : 'To‘xtagan'),
        }),
        row('Sozlamalar', null, {
          iconName: 'settings',
          sub: [s.group_configured ? 'guruh ulangan' : 'guruh ulanmagan', s.mini_app_configured ? 'Mini App havolasi bor' : 'Mini App havolasi yo‘q'].join(' · '),
          trailing: h('span', { class: 'row__value' }, `PHP ${s.php_version}`),
        })));
    } catch (e) {
      if (ctx.isCurrent()) clear(systemSlot).appendChild(listBlock('Tizim holati', row('Holatni olib bo‘lmadi', null, { sub: e.message, onClick: drawSystem })));
    }
  }

  async function load() {
    const stop = delayedSkeleton(body, () => h('div', null, blockSkeleton(1), blockSkeleton(3), blockSkeleton(3)));
    try {
      [settings, members, reminders, group] = await Promise.all([
        api.get('/settings'),
        api.get('/members'),
        api.get('/reminders/status').catch(() => null),
        api.get('/group').catch(() => null),
      ]);
      stop();
      if (ctx.isCurrent()) draw();
    } catch (e) {
      stop();
      if (ctx.isCurrent()) clear(body).appendChild(errorState(e.message, load));
    }
  }

  async function updateMember(m, payload, successText, sheet) {
    try {
      await api.put(`/members/${m.id}`, payload);
      sheet?.close();
      toast.success(successText);
      members = await api.get('/members');
      draw();
    } catch (e) {
      toast.error(e.message);
    }
  }

  function openMember(m) {
    if (m.role === 'owner') return;
    const actions = [];
    if (m.status === 'pending') {
      actions.push(button('Tasdiqlash', { variant: 'primary', block: true, onClick: () => updateMember(m, { status: 'active' }, 'A’zo tasdiqlandi', sheet) }));
      actions.push(button('Rad etish', { variant: 'danger', block: true, onClick: () => updateMember(m, { status: 'disabled' }, 'So‘rov rad etildi', sheet) }));
    } else if (m.status === 'active') {
      actions.push(button('O‘chirish', { variant: 'danger', block: true, onClick: () => updateMember(m, { status: 'disabled' }, 'A’zo o‘chirildi', sheet) }));
    } else {
      actions.push(button('Faollashtirish', { variant: 'primary', block: true, onClick: () => updateMember(m, { status: 'active' }, 'A’zo faollashtirildi', sheet) }));
    }
    actions.push(button('Yopish', { variant: 'ghost', block: true, onClick: () => sheet.close() }));
    const sheet = openSheet({
      title: m.name,
      content: h('div', { class: 'block block--inset' },
        row('Holat', MEMBER_STATUS[m.status]),
        m.username ? row('Telegram', `@${m.username}`) : null,
        row('Telegram ID', String(m.telegram_user_id)),
        row('Guruhda', m.in_group ? 'Ha' : 'Yo‘q'),
        m.last_seen_at ? row('Oxirgi kirgan', timeAgo(m.last_seen_at)) : null),
      footer: h('div', { class: 'stack-buttons' }, actions),
    });
  }

  function openAddMember() {
    const tgId = textInput('telegram_user_id', '', { inputmode: 'numeric', placeholder: 'Masalan: 123456789' });
    const name = textInput('display_name', '', { maxlength: '128', placeholder: 'Ism' });
    const form = h('form', { id: 'member-form', novalidate: true, class: 'sheet-form' },
      field('Telegram ID', tgId, { name: 'telegram_user_id', hint: 'A’zo guruhda bo‘lishi kerak. ID’ni @userinfobot orqali bilish mumkin.' }),
      field('Ism', name, { name: 'display_name' }));
    const save = button('Qo‘shish', { variant: 'primary', block: true, type: 'submit' });
    save.setAttribute('form', 'member-form');
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = tgId.value.replace(/\D/g, '');
      const errors = {};
      if (!id) errors.telegram_user_id = 'Telegram ID kiritilishi shart.';
      if (!name.value.trim()) errors.display_name = 'Ism kiritilishi shart.';
      if (Object.keys(errors).length) {
        applyErrors(form, errors);
        return;
      }
      setLoading(save, true);
      try {
        await api.post('/members', { telegram_user_id: Number(id), display_name: name.value.trim() });
        sheet.close();
        toast.success('A’zo qo‘shildi');
        members = await api.get('/members');
        draw();
      } catch (err) {
        if (err.details) applyErrors(form, err.details);
        else toast.error(err.message);
      } finally {
        setLoading(save, false);
      }
    });
    const sheet = openSheet({ title: 'A’zo qo‘shish', content: form, footer: save });
  }

  async function publishButton() {
    const ok = await confirmSheet({
      title: 'Guruhga tugma yuborish',
      text: 'Bot guruhga “TO‘YLAR JADVALI” tugmali xabar yuboradi va pin qiladi. Eski pin qilingan xabar o‘z joyida qoladi.',
      confirmLabel: 'Yuborish',
    });
    if (!ok) return;
    try {
      const res = await api.post('/group/publish-button');
      tg.haptic.notify('success');
      toast.success(res.pinned ? 'Yuborildi va pin qilindi' : 'Yuborildi, lekin pin qilinmadi');
    } catch (e) {
      toast.error(e.message, { retry: publishButton });
    }
  }

  await load();
  drawSystem();
}
