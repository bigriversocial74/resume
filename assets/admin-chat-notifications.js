(() => {
  const topRight = document.querySelector('.admin-top-right');
  if (!topRight) return;

  const scriptUrl = new URL(document.currentScript?.src || 'assets/admin-chat-notifications.js', window.location.href);
  const appBasePath = scriptUrl.pathname.replace(/\/assets\/admin-chat-notifications\.js(?:\?.*)?$/, '').replace(/\/$/, '');
  const appUrl = (path) => `${appBasePath}${path.startsWith('/') ? path : `/${path}`}` || '/';

  if (location.pathname.includes('/admin/chat-thread.php') && !document.querySelector('#de-admin-chat-thread-layout')) {
    const threadStyle = document.createElement('style');
    threadStyle.id = 'de-admin-chat-thread-layout';
    threadStyle.textContent = `.admin-main{padding:14px 18px 18px!important;overflow:hidden}.admin-hero{display:none!important}.ok,.err{margin:0 0 10px!important}.chat-workspace{height:calc(100vh - 96px)!important;min-height:520px!important;display:grid!important;grid-template-columns:minmax(0,1fr) 300px!important;gap:14px!important;align-items:stretch!important;overflow:hidden!important}.chat-frame{height:100%!important;min-height:0!important;display:grid!important;grid-template-rows:auto minmax(0,1fr) auto!important;overflow:hidden!important}.chat-head{padding:13px 16px!important;flex:0 0 auto!important}.chat-canvas{height:100%!important;min-height:0!important;overflow-y:auto!important;overflow-x:hidden!important;padding:16px!important;scrollbar-gutter:stable!important}.chat-composer{position:relative!important;bottom:auto!important;z-index:5!important;background:#fff!important;flex:0 0 auto!important;padding:10px 12px!important}.composer-form{grid-template-columns:minmax(0,1fr) auto!important}.composer-form textarea{min-width:0!important;max-height:118px!important}.msg{overflow-wrap:anywhere!important;word-break:break-word!important;white-space:pre-wrap!important}.side-stack{height:100%!important;min-height:0!important;overflow-y:auto!important;overflow-x:hidden!important;display:grid!important;gap:10px!important;align-content:start!important;padding-right:2px!important}.side-card{padding:16px!important}.fact strong{min-width:0!important;text-align:right!important;overflow-wrap:anywhere!important}@media(max-width:1000px){.admin-main{overflow:visible!important}.chat-workspace{height:auto!important;min-height:0!important;grid-template-columns:1fr!important;overflow:visible!important}.chat-frame{height:72vh!important}.side-stack{height:auto!important;overflow:visible!important;grid-template-columns:1fr 1fr!important}}@media(max-width:650px){.admin-main{padding:12px!important}.composer-form{grid-template-columns:1fr!important}.side-stack{grid-template-columns:1fr!important}.chat-frame{height:76vh!important}.msg{max-width:92%!important}}`;
    document.head.appendChild(threadStyle);
  }

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
  let lastSignature = localStorage.getItem('de_admin_chat_signature') || '';
  let hasPolled = false;
  let audioReady = false;

  function playNotice() {
    if (!audioReady) return;
    try {
      const Ctx = window.AudioContext || window.webkitAudioContext;
      const ctx = new Ctx();
      const tone = ctx.createOscillator();
      const volume = ctx.createGain();
      tone.frequency.value = 820;
      volume.gain.value = 0.04;
      tone.connect(volume);
      volume.connect(ctx.destination);
      tone.start();
      setTimeout(() => { tone.stop(); ctx.close(); }, 160);
    } catch (e) {}
  }

  document.addEventListener('click', () => { audioReady = true; }, { once: true });
  document.addEventListener('keydown', () => { audioReady = true; }, { once: true });

  async function poll() {
    try {
      const res = await fetch(appUrl('/api/chat-notifications.php'), { headers: { 'Accept': 'application/json' }});
      const data = await res.json();
      if (!data.ok) return;
      const count = Number(data.count || 0);
      const items = data.items || [];
      const signature = items.map((item) => item.signature || `${item.id}:${item.unread}:${item.last_at}`).join('|');
      badge.textContent = String(count);
      badge.hidden = count <= 0;
      root.classList.toggle('has-alerts', count > 0);
      list.innerHTML = '';
      if (!items.length) {
        list.innerHTML = '<div class="admin-chat-alert-empty">No new chats.</div>';
      } else {
        items.forEach((item) => {
          const a = document.createElement('a');
          a.className = 'admin-chat-alert-item';
          a.href = appUrl(item.url || `/admin/chat-thread.php?id=${Number(item.id || 0)}`);
          a.innerHTML = `<strong>${escapeHtml(item.label)}</strong><span>${escapeHtml(item.last_message || 'New message')}</span><em>${Number(item.unread || 0)} unread · Accept chat →</em>`;
          list.appendChild(a);
        });
      }
      if (hasPolled && count > 0 && signature && signature !== lastSignature) playNotice();
      hasPolled = true;
      lastSignature = signature;
      localStorage.setItem('de_admin_chat_signature', signature);
    } catch (e) {}
  }

  function escapeHtml(value) {
    return String(value).replace(/[&<>'"]/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
  }

  poll();
  setInterval(poll, 3500);
})();
