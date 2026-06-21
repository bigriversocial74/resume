(() => {
  const root = document.querySelector('[data-chat-alert]');
  if (!root) return;
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
      osc.type = 'sine';
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
        list.innerHTML = '<div class="chat-alert-empty">No new chats.</div>';
      } else {
        data.items.forEach((item) => {
          const a = document.createElement('a');
          a.className = 'chat-alert-item';
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
