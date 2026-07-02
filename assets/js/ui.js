export function setLoading(btn, loading) {
  if (!btn) return;
  btn.disabled = loading;
  const spinner = btn.querySelector('.spinner-border');
  if (loading && !spinner) {
    const s = document.createElement('span');
    s.className = 'spinner-border spinner-border-sm me-1';
    s.setAttribute('role', 'status');
    s.setAttribute('aria-hidden', 'true');
    btn.prepend(s);
  } else if (!loading && spinner) {
    spinner.remove();
  }
}

export function showFeedback(msg, type) {
  const el = document.getElementById('feedback');
  el.textContent = msg;
  el.className = `alert alert-${type === 'success' ? 'success' : 'danger'}`;
  clearTimeout(el._timer);
  el._timer = setTimeout(() => {
    el.className = 'visually-hidden';
    el.textContent = '';
  }, 4000);
}
