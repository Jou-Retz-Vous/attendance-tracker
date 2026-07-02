import { setLoading, showFeedback } from './ui.js';
import { MapManager } from './map.js';

const body          = document.body;
const lang          = body.dataset.lang || 'fr';
const t             = JSON.parse(body.dataset.i18n || '{}');
let sessionUid      = body.dataset.sessionUid || '';
const sessionCoords = JSON.parse(body.dataset.sessionCoords || '{}');
const showLocation  = JSON.parse(body.dataset.showLocation || '"with_map"');

const sessionSel = document.getElementById('session');
const mapManager = new MapManager({ sessionCoords, showLocation });
mapManager.setup(sessionSel, sessionUid);

function makeCheckinRow(c, highlight = false) {
  const date = new Date(c.created_at).toLocaleDateString(lang, { day: '2-digit', month: '2-digit', year: 'numeric' });

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/admin/';
  form.style.display = 'inline';
  const inId  = document.createElement('input'); inId.type  = 'hidden'; inId.name  = 'checkin_id';  inId.value = c.id;
  const inSid = document.createElement('input'); inSid.type = 'hidden'; inSid.name = 'session_uid'; inSid.value = sessionUid;
  const btn   = document.createElement('button'); btn.type = 'button';
  btn.dataset.deleteId = c.id;
  btn.className = 'btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1';
  btn.setAttribute('aria-label', t.delete || 'Supprimer');
  const iconEl = document.createElement('i'); iconEl.className = 'bi bi-trash'; iconEl.setAttribute('aria-hidden', 'true');
  const lblDesktop = document.createElement('span'); lblDesktop.className = 'd-none d-sm-inline'; lblDesktop.textContent = t.delete || 'Supprimer';
  btn.append(iconEl, lblDesktop);
  form.append(inId, inSid, btn);

  const tr = document.createElement('tr');
  if (highlight) {
    tr.className = 'row-highlight';
    tr.addEventListener('animationend', () => tr.classList.remove('row-highlight'), { once: true });
  }
  const tdName = document.createElement('td'); tdName.className = 'text-truncate'; tdName.textContent = c.nickname;
  const tdDate = document.createElement('td'); tdDate.className = 'd-none d-sm-table-cell'; tdDate.textContent = date;
  const tdAct  = document.createElement('td'); tdAct.className = 'text-end'; tdAct.appendChild(form);
  tr.append(tdName, tdDate, tdAct);
  return tr;
}

// Upgrade server-rendered delete buttons (type=submit → type=button + data-delete-id)
// so the two-step confirmation handler applies uniformly to all rows.
document.querySelectorAll('#tbody [name="checkin_id"]').forEach(input => {
  const btn = input.closest('form')?.querySelector('[type="submit"]');
  if (!btn) return;
  btn.type = 'button';
  btn.dataset.deleteId = input.value;
});

function renderCheckins(checkins) {
  const tbody = document.getElementById('tbody');
  tbody.innerHTML = '';
  if (!checkins.length) {
    const tr = document.createElement('tr'); tr.id = 'tr-empty';
    const td = document.createElement('td'); td.colSpan = 3; td.className = 'text-center text-muted py-3';
    td.textContent = t.no_checkins || '';
    tr.appendChild(td); tbody.appendChild(tr);
  } else {
    checkins.forEach(c => tbody.appendChild(makeCheckinRow(c)));
  }
}

document.getElementById('btn-voir').classList.add('d-none');

function loadCheckins(uid) {
  const tbody = document.getElementById('tbody');
  tbody.classList.add('opacity-50');
  return fetch(`/api/admin/checkins.php?session_uid=${encodeURIComponent(uid)}`)
    .then(r => r.json())
    .then(({ checkins = [] }) => { renderCheckins(checkins); })
    .finally(() => tbody.classList.remove('opacity-50'));
}

document.getElementById('session').addEventListener('change', ({ target }) => {
  sessionUid = target.value;
  const sidInput = document.querySelector('#checkin-form [name="session_uid"]');
  if (sidInput) sidInput.value = sessionUid;
  const nicknameInput = document.getElementById('checkin-nickname');
  if (nicknameInput) nicknameInput.disabled = !sessionUid;
  history.pushState({ sessionUid }, '', `/admin/?session_uid=${encodeURIComponent(sessionUid)}`);
  loadCheckins(sessionUid);
  mapManager.onSessionChange(sessionSel, sessionUid);
});

window.addEventListener('popstate', ({ state }) => {
  if (!state?.sessionUid) return;
  sessionUid = state.sessionUid;
  document.getElementById('session').value = sessionUid;
  loadCheckins(sessionUid);
});

function setInputInvalid(input, feedback, msg) {
  input.classList.add('is-invalid');
  if (feedback) feedback.textContent = msg;
}

function clearInputInvalid(input) {
  input.classList.remove('is-invalid');
}

document.getElementById('checkin-form').addEventListener('submit', async e => {
  e.preventDefault();
  const input    = document.getElementById('checkin-nickname');
  const feedback = document.getElementById('checkin-nickname-feedback');
  const submitBtn = e.currentTarget.querySelector('[type="submit"]');
  const nickname = input.value.trim();

  if (!nickname) {
    setInputInvalid(input, feedback, t.fill_nickname || '');
    return;
  }

  const existing = Array.from(document.querySelectorAll('#tbody td:first-child'))
    .map(td => td.textContent.trim().toLowerCase());
  if (existing.includes(nickname.toLowerCase())) {
    setInputInvalid(input, feedback, t.already || '');
    return;
  }

  clearInputInvalid(input);
  setLoading(submitBtn, true);

  try {
    const res = await fetch('/api/admin/checkin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ session_uid: sessionUid, nickname }),
    }).then(r => r.json());

    if (res.ok) {
      input.value = '';
      const tbody = document.getElementById('tbody');
      document.getElementById('tr-empty')?.remove();
      tbody.appendChild(makeCheckinRow(res.checkin, true));
      const sel = document.getElementById('session');
      sel.options[sel.selectedIndex].text =
        sel.options[sel.selectedIndex].text.replace(/\((\d+)\)/, (_, n) => `(${+n + 1})`);
      showFeedback((t.checked_in || '{name}').replace('{name}', res.checkin.nickname), 'success');
    } else {
      const msg = res.error?.includes('Already') ? (t.already || res.error) : (t.err_generic || res.error);
      setInputInvalid(input, feedback, msg);
    }
  } finally {
    setLoading(submitBtn, false);
  }
});

document.getElementById('tbody').addEventListener('click', async e => {
  const btn = e.target.closest('[data-delete-id], [data-confirm-delete]');
  if (!btn) return;

  if (btn.dataset.confirmDelete) {
    const checkinId = btn.dataset.confirmDelete;
    setLoading(btn, true);
    try {
      const res = await fetch('/api/admin/delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ checkin_id: checkinId }),
      }).then(r => r.json());
      if (res.ok) {
        btn.closest('tr').remove();
        const tbody = document.getElementById('tbody');
        if (!tbody.querySelector('tr:not(#tr-empty)')) renderCheckins([]);
        const sel = document.getElementById('session');
        sel.options[sel.selectedIndex].text =
          sel.options[sel.selectedIndex].text.replace(/\((\d+)\)/, (_, n) => `(${Math.max(0, n - 1)})`);
      } else {
        showFeedback(res.error || t.err_generic || 'Erreur.', 'error');
        setLoading(btn, false);
      }
    } catch {
      setLoading(btn, false);
    }
    return;
  }

  const checkinId = btn.dataset.deleteId;
  btn.removeAttribute('data-delete-id');
  btn.dataset.confirmDelete = checkinId;
  btn.textContent = t.confirm_delete || 'Confirmer ?';
  btn.classList.replace('btn-outline-danger', 'btn-danger');

  const cancelBtn = document.createElement('button');
  cancelBtn.type = 'button';
  cancelBtn.className = 'btn btn-outline-secondary btn-sm ms-1';
  cancelBtn.textContent = t.cancel_action || 'Annuler';
  cancelBtn.onclick = () => {
    btn.removeAttribute('data-confirm-delete');
    btn.dataset.deleteId = checkinId;
    btn.textContent = t.delete || 'Supprimer';
    btn.classList.replace('btn-danger', 'btn-outline-danger');
    cancelBtn.remove();
  };
  btn.after(cancelBtn);
});
