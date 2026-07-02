import { setLoading, showFeedback } from './ui.js';
import { MapManager } from './map.js';

const body           = document.body;
const checkedUids    = JSON.parse(body.dataset.checkedUids || '[]');
const initialChecked = [...checkedUids];
const savedNickname  = JSON.parse(body.dataset.savedNickname || '""');
const sessionCoords  = JSON.parse(body.dataset.sessionCoords || '{}');
const showLocation   = JSON.parse(body.dataset.showLocation || '"with_map"');

const t          = JSON.parse(body.dataset.i18n || '{}');
const interp     = (tpl, name) => tpl.replace('{name}', name);
const sessionSel = document.getElementById('session');
const mapManager = new MapManager({ sessionCoords, showLocation });

function updateButtons(sessionUid) {
  const checked = checkedUids.includes(sessionUid);
  document.getElementById('btn-checkin').classList.toggle('d-none', checked);
  document.getElementById('btn-cancel').classList.toggle('d-none', !checked);
  const status = document.getElementById('session-status');
  if (status) status.textContent = checked ? (t.already || '') : '';
}

updateButtons(sessionSel.value);
mapManager.setup(sessionSel, sessionSel.value);

sessionSel.addEventListener('change', ({ target }) => {
  updateButtons(target.value);
  mapManager.onSessionChange(target, target.value);
});

const post = (path, data) => fetch(path, {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(data),
}).then(r => r.json());

const COOKIE_NAME  = 'jrv_nickname';
const getCookie    = () => document.cookie.split('; ').find(r => r.startsWith(COOKIE_NAME + '='))?.split('=')[1] ?? '';
const setCookie    = v => { document.cookie = `${COOKIE_NAME}=${encodeURIComponent(v)}; max-age=31536000; path=/; SameSite=Strict`; };
const deleteCookie = () => { document.cookie = `${COOKIE_NAME}=; max-age=0; path=/`; };

function syncCheckedState(nickname) {
  const uids = nickname === savedNickname ? initialChecked : [];
  checkedUids.length = 0;
  uids.forEach(u => checkedUids.push(u));
  const sel = document.getElementById('session');
  for (const opt of sel.options) {
    const has    = opt.text.startsWith('✅ ');
    const should = checkedUids.includes(opt.value);
    if (has && !should) opt.text = opt.text.slice(2);
    if (!has && should)  opt.text = '✅ ' + opt.text;
  }
  updateButtons(sel.value);
}

function syncRemember(val) {
  const cookie   = getCookie();
  const decoded  = cookie ? decodeURIComponent(cookie) : '';
  const checkbox = document.getElementById('remember');
  const hint     = document.getElementById('saved-nickname-hint');
  const matches  = decoded !== '' && val === decoded;
  checkbox.indeterminate = decoded !== '' && !matches;
  checkbox.checked = matches;
  if (decoded !== '') {
    document.getElementById('saved-nickname-value').textContent = decoded;
    hint.classList.remove('d-none');
  } else {
    hint.classList.add('d-none');
  }
}

function forgetNickname() {
  deleteCookie();
  const checkbox = document.getElementById('remember');
  checkbox.checked = false;
  checkbox.indeterminate = false;
  document.getElementById('saved-nickname-hint').classList.add('d-none');
}

document.getElementById('remember').addEventListener('change', ({ target }) => {
  const val = document.getElementById('nickname').value.trim();
  if (target.checked && val) {
    setCookie(val);
    document.getElementById('saved-nickname-value').textContent = val;
    document.getElementById('saved-nickname-hint').classList.remove('d-none');
    target.indeterminate = false;
  } else if (!target.checked) {
    forgetNickname();
  }
});

document.getElementById('btn-forget').addEventListener('click', forgetNickname);

let timer;
document.getElementById('nickname').addEventListener('input', ({ target }) => {
  const val = target.value.trim();
  syncRemember(val);
  syncCheckedState(val);

  clearTimeout(timer);
  if (val.length < 2) return;
  timer = setTimeout(() => {
    fetch(`/api/attendees.php?q=${encodeURIComponent(val)}`)
      .then(r => r.json())
      .then(({ attendees = [] }) => {
        const dl = document.getElementById('suggestions');
        dl.innerHTML = '';
        attendees.forEach(({ nickname }) => {
          const opt = document.createElement('option');
          opt.value = nickname;
          dl.appendChild(opt);
        });
      });
  }, 200);
});

document.getElementById('checkin-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn        = e.submitter;
  const action     = btn?.value ?? 'checkin';
  const nickname   = document.getElementById('nickname').value.trim();
  const sel        = document.getElementById('session');
  const sessionUid = sel.value;

  if (!nickname) {
    const input = document.getElementById('nickname');
    input.classList.add('is-invalid');
    showFeedback(t.fill_nickname, 'error');
    return;
  }
  document.getElementById('nickname').classList.remove('is-invalid');

  setLoading(btn, true);
  try {
    if (action === 'cancel') {
      const res = await post('/api/cancel.php', { session_uid: sessionUid, nickname });
      if (res.ok) {
        showFeedback(interp(t.cancelled, res.nickname), 'success');
        const idx = checkedUids.indexOf(sessionUid);
        if (idx !== -1) checkedUids.splice(idx, 1);
        if (sel.options[sel.selectedIndex].text.startsWith('✅ ')) {
          sel.options[sel.selectedIndex].text = sel.options[sel.selectedIndex].text.slice(2);
        }
        updateButtons(sessionUid);
      } else if (res.error?.includes('No check-in')) {
        showFeedback(t.not_checked_in, 'error');
      } else {
        showFeedback(t.err_generic, 'error');
      }
      return;
    }

    const res = await post('/api/checkin.php', { session_uid: sessionUid, nickname });
    if (res.ok) {
      const rememberCb = document.getElementById('remember');
      if (!rememberCb.checked && !rememberCb.indeterminate) forgetNickname();
      showFeedback(interp(t.checked_in, res.nickname), 'success');
      checkedUids.push(sessionUid);
      if (!sel.options[sel.selectedIndex].text.startsWith('✅ ')) {
        sel.options[sel.selectedIndex].text = '✅ ' + sel.options[sel.selectedIndex].text;
      }
      updateButtons(sessionUid);
    } else if (res.error?.includes('Already')) {
      showFeedback(t.already, 'error');
    } else {
      showFeedback(t.err_generic, 'error');
    }
  } finally {
    setLoading(btn, false);
  }
});
