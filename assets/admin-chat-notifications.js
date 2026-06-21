(() => {
  const topRight = document.querySelector('.admin-top-right');
  if (!topRight) return;
  if (!document.querySelector('#de-admin-pro-chat-styles')) {
    const style = document.createElement('style');
    style.id = 'de-admin-pro-chat-styles';
    style.textContent = `.admin-layout{grid-template-columns:50px minmax(0,1fr)!important}.admin-sidebar{width:50px!important;padding:10px 7px!important;transition:width .2s ease,box-shadow .2s ease}.admin-sidebar:hover,.admin-sidebar.is-open{width:228px!important;box-shadow:18px 0 48px rgba(15,23,42,.08)}.admin-nav-link,.admin-nav-parent{width:36px!important;height:40px!important;justify-content:flex-start!important;padding:0 10px!important;overflow:hidden!important}.admin-sidebar:hover .admin-nav-link,.admin-sidebar:hover .admin-nav-parent,.admin-sidebar.is-open .admin-nav-link,.admin-sidebar.is-open .admin-nav-parent{width:214px!important}.admin-nav-link em,.admin-nav-parent em{opacity:0!important;display:inline!important;font-style:normal!important;font-size:12px!important;font-weight:800!important}.admin-sidebar:hover em,.admin-sidebar.is-open em{opacity:1!important}.nav-left{display:flex;align-items:center;gap:13px;white-space:nowrap}.admin-nav-link i,.admin-nav-parent i{min-width:16px;text-align:center}.admin-nav-parent b{display:none!important;margin-left:auto}.admin-sidebar:hover .admin-nav-parent b,.admin-sidebar.is-open .admin-nav-parent b{display:block!important}.admin-subnav{left:52px!important}.admin-chat-alert{position:relative}.admin-chat-alert-button{width:36px;height:36px;border:1px solid #e2e2e2;background:#fff;border-radius:4px;display:grid;place-items:center;position:relative;cursor:pointer}.admin-chat-alert-button b{position:absolute;right:-7px;top:-7px;background:#f13f67;color:#fff;min-width:18px;height:18px;border-radius:999px;font-size:10px;display:grid;place-items:center}.admin-chat-alert.has-alerts .admin-chat-alert-button{border-color:#f13f67;background:#fff0f4;color:#f13f67}.admin-chat-alert-panel{position:absolute;right:0;top:43px;width:280px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 20px 60px rgba(15,23,42,.12);padding:10px;display:none;gap:6px;z-index:140}.admin-chat-alert:hover .admin-chat-alert-panel,.admin-chat-alert:focus-within .admin-chat-alert-panel{display:grid}.admin-chat-alert-empty{font-size:12px;color:#667085;padding:10px}.admin-chat-alert-item{display:grid;gap:3px;color:#242424;text-decoration:none;padding:10px;border-radius:7px;border:1px solid #f0f0f0}.admin-chat-alert-item:hover{background:#fafafa}.admin-chat-alert-item strong{font-size:12px}.admin-chat-alert-item span{font-size:11px;color:#667085}.admin-chat-alert-item em{font-style:normal;font-size:11px;color:#f13f67;font-weight:900}`;
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
      const res = await fetch('/api/chat-notifications.php', { headers: { 'Accept': 'application/json' }});
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
          a.href = item.url;
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
