(() => {
  const topRight = document.querySelector('.admin-top-right');
  if (!topRight) return;

  const scriptUrl = new URL(document.currentScript?.src || 'assets/admin-chat-notifications.js', window.location.href);
  const appBasePath = scriptUrl.pathname.replace(/\/assets\/admin-chat-notifications\.js(?:\?.*)?$/, '').replace(/\/$/, '');
  const appUrl = (path) => `${appBasePath}${path.startsWith('/') ? path : `/${path}`}` || '/';

  if (!document.querySelector('#de-admin-chat-alert-styles')) {
    const style = document.createElement('style');
    style.id = 'de-admin-chat-alert-styles';
    style.textContent = `.admin-chat-alert{position:relative}.admin-chat-alert-button{width:36px;height:36px;border:1px solid #e2e2e2;background:#fff;border-radius:4px;display:grid;place-items:center;position:relative;cursor:pointer}.admin-chat-alert-button b{position:absolute;right:-7px;top:-7px;background:#f13f67;color:#fff;min-width:18px;height:18px;border-radius:999px;font-size:10px;display:grid;place-items:center}.admin-chat-alert.has-alerts .admin-chat-alert-button{border-color:#f13f67;background:#fff0f4;color:#f13f67}.admin-chat-alert-panel{position:absolute;right:0;top:43px;width:280px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 20px 60px rgba(15,23,42,.12);padding:10px;display:none;gap:6px;z-index:140}.admin-chat-alert:hover .admin-chat-alert-panel,.admin-chat-alert:focus-within .admin-chat-alert-panel{display:grid}.admin-chat-alert-empty{font-size:12px;color:#667085;padding:10px}.admin-chat-alert-item{display:grid;gap:3px;color:#242424;text-decoration:none;padding:10px;border-radius:7px;border:1px solid #f0f0f0}.admin-chat-alert-item:hover{background:#fafafa}.admin-chat-alert-item strong{font-size:12px}.admin-chat-alert-item span{font-size:11px;color:#667085}.admin-chat-alert-item em{font-style:normal;font-size:11px;color:#f13f67;font-weight:900}`;
    document.head.appendChild(style);
  }

  let root = document.querySelector('[data-chat-alert]');
  if (!root) {
    root = document.createElement('div');
    root.className = 'admin-chat-alert';
    root.setAttribute('data-chat-alert', 'true');
    root.innerHTML = `<button class="admin-chat-alert-button" type="button" aria-label="Incoming chats"><span>☏</span><b data-chat-count hidden>0</b></button><div class="admin-chat-alert-panel"><strong>Incoming chats</strong><div data-chat-list><div class="admin-chat-alert-empty">No new chats.</div></div></div>`;
    topRight.insertBefore(root, topRight.firstChild);
  }

  const badge = root.querySelector('[data-chat-count]');
  const list = root.querySelector('[data-chat-list]');
  let lastCount = Number(localStorage.getItem('de_last_chat_count') || '0');
  let audioReady = false;

  function beep() {
    if (!audioReady) return;
    try {
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      const ctx = new AudioCtx();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.frequency.value = 740;
      gain.gain.value = 0.035;
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start();
      setTimeout(() => { osc.stop(); ctx.close(); }, 140);
    } catch (e) {}
  }

  document.addEventListener('click', () => { audioReady = true; }, { once: true });

  async function poll() {
    try {
      const res = await fetch(appUrl('/api/chat-notifications.php'), { headers: { 'Accept': 'application/json' }});
      const data = await res.json();
      if (!data.ok) return;
      const count = Number(data.count || 0);
      badge.textContent = String(count);
      badge.hidden = count <= 0;
      root.classList.toggle('has-alerts', count > 0);
      list.innerHTML = '';
      if (!data.items || data.items.length === 0) {
        list.innerHTML = '<div class="admin-chat-alert-empty">No new chats.</div>';
      } else {
        data.items.forEach((item) => {
          const a = document.createElement('a');
          a.className = 'admin-chat-alert-item';
          a.href = appUrl(item.url || `/admin/chat-thread.php?id=${Number(item.id || 0)}`);
          a.innerHTML = `<strong>${escapeHtml(item.label)}</strong><span>${escapeHtml(item.last_message || 'New message')}</span><em>Accept chat →</em>`;
          list.appendChild(a);
        });
      }
      if (count > lastCount) beep();
      lastCount = count;
      localStorage.setItem('de_last_chat_count', String(count));
    } catch (e) {}
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>'"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
  }

  poll();
  setInterval(poll, 5000);
})();
