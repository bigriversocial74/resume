(() => {
  const trigger = document.querySelector('[data-hero-video-chat]');
  const hero = document.querySelector('.hero');
  if (!trigger || !hero) return;

  let conversationId = '';
  let panel = null;
  let loading = false;

  const css = `
    .hero-video-shell{position:absolute;inset:118px 42px 42px auto;width:min(620px,46vw);z-index:8;border-radius:24px;overflow:hidden;background:#07101e;box-shadow:0 28px 90px rgba(0,0,0,.42);display:grid;grid-template-rows:auto 1fr;border:1px solid rgba(255,255,255,.14)}
    .hero-video-head{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:16px 18px;color:#fff;background:rgba(7,16,30,.96);font:700 13px Inter,Arial,sans-serif}.hero-video-head span{color:rgba(255,255,255,.72);font-size:12px;font-weight:500}.hero-video-close{border:0;background:rgba(255,255,255,.12);color:#fff;width:34px;height:34px;border-radius:999px;cursor:pointer;font-size:20px}.hero-video-frame{width:100%;height:100%;min-height:520px;border:0;background:#000}.hero-video-loading{display:grid;place-items:center;min-height:520px;color:#fff;font:700 14px Inter,Arial,sans-serif;text-align:center;padding:28px}.hero-video-error{display:grid;place-items:center;min-height:360px;color:#fff;font:700 14px Inter,Arial,sans-serif;text-align:center;padding:28px}.hero-video-error a{color:#7dd3fc}.hero-video-shell.is-loading iframe{display:none}@media(max-width:1020px){.hero-video-shell{inset:108px 21px 32px 21px;width:auto}.hero-video-frame,.hero-video-loading{min-height:480px}}@media(max-width:680px){.hero-video-shell{inset:88px 14px 20px 14px;border-radius:18px}.hero-video-frame,.hero-video-loading{min-height:520px}}`;
  const style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);

  function buildPanel() {
    panel = document.createElement('section');
    panel.className = 'hero-video-shell is-loading';
    panel.innerHTML = `<div class="hero-video-head"><div><strong>Chat with Dave's AI video assistant</strong><br><span>Camera and microphone permissions are requested only after you start.</span></div><button class="hero-video-close" type="button" aria-label="Close video chat">×</button></div><div class="hero-video-loading">Creating a secure video conversation...</div>`;
    hero.appendChild(panel);
    panel.querySelector('.hero-video-close').addEventListener('click', closeConversation);
    return panel;
  }

  async function startConversation(event) {
    event.preventDefault();
    if (loading) return;
    loading = true;
    const shell = panel || buildPanel();
    shell.classList.add('is-loading');
    shell.querySelector('.hero-video-loading')?.remove();
    shell.insertAdjacentHTML('beforeend', '<div class="hero-video-loading">Creating a secure video conversation...</div>');
    try {
      const res = await fetch('/api/tavus/start.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ page_url: location.href }) });
      const data = await res.json();
      if (!data.ok || !data.conversation_url) throw new Error(data.error || 'Could not start video chat.');
      conversationId = data.conversation_id || '';
      shell.classList.remove('is-loading');
      shell.querySelector('.hero-video-loading')?.remove();
      const iframe = document.createElement('iframe');
      iframe.className = 'hero-video-frame';
      iframe.title = 'Tavus conversation';
      iframe.src = data.conversation_url;
      iframe.allow = 'camera; microphone; fullscreen; display-capture; autoplay';
      shell.appendChild(iframe);
    } catch (err) {
      shell.classList.remove('is-loading');
      shell.innerHTML = `<div class="hero-video-head"><div><strong>Video chat is not ready</strong><br><span>Falling back to text chat and project intake.</span></div><button class="hero-video-close" type="button" aria-label="Close video chat">×</button></div><div class="hero-video-error"><div>${String(err.message || err)}<br><br><a href="project-questions.php">Open project questions instead →</a></div></div>`;
      shell.querySelector('.hero-video-close').addEventListener('click', closeConversation);
    } finally {
      loading = false;
    }
  }

  async function closeConversation() {
    const id = conversationId;
    conversationId = '';
    if (panel) {
      panel.remove();
      panel = null;
    }
    if (id) {
      fetch('/api/tavus/end.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ conversation_id: id }) }).catch(() => {});
    }
  }

  window.addEventListener('beforeunload', () => {
    if (!conversationId) return;
    navigator.sendBeacon?.('/api/tavus/end.php', JSON.stringify({ conversation_id: conversationId }));
  });

  trigger.addEventListener('click', startConversation);
})();
