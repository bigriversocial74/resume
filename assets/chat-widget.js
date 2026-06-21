(() => {
  const css = `
  .de-chat-bubble{position:fixed;right:24px;bottom:24px;z-index:9999;width:62px;height:62px;border:0;border-radius:999px;background:linear-gradient(135deg,#4c82ff,#2162ff);color:#fff;box-shadow:0 18px 50px rgba(47,104,255,.38);font:900 24px Inter,Arial,sans-serif;cursor:pointer}
  .de-chat-panel{position:fixed;right:24px;bottom:98px;z-index:9999;width:min(380px,calc(100vw - 32px));height:540px;max-height:calc(100vh - 130px);background:#fff;border:1px solid #e5e7eb;border-radius:22px;box-shadow:0 28px 90px rgba(15,23,42,.22);overflow:hidden;display:none;font-family:Inter,Arial,sans-serif;color:#111827}
  .de-chat-panel.open{display:grid;grid-template-rows:auto 1fr auto}.de-chat-head{background:#07101e;color:#fff;padding:18px 20px}.de-chat-head strong{display:block;font-size:15px}.de-chat-head span{display:block;color:rgba(255,255,255,.7);font-size:12px;margin-top:5px}.de-chat-messages{padding:18px;overflow:auto;background:#f8faff;display:grid;gap:10px;align-content:start}.de-msg{max-width:82%;padding:10px 12px;border-radius:14px;font-size:13px;line-height:1.45}.de-msg.visitor{justify-self:end;background:#2f68ff;color:#fff;border-bottom-right-radius:4px}.de-msg.admin,.de-msg.system{justify-self:start;background:#fff;border:1px solid #e5e7eb;border-bottom-left-radius:4px}.de-chat-form{display:grid;grid-template-columns:1fr auto;gap:10px;padding:14px;background:#fff;border-top:1px solid #e5e7eb}.de-chat-input{border:1px solid #dfe5ef;border-radius:999px;padding:12px 14px;font:inherit}.de-chat-send{border:0;border-radius:999px;background:#2f68ff;color:#fff;font-weight:900;padding:0 16px;cursor:pointer}.de-chat-link{display:block;color:#7dd3fc;margin-top:8px;font-size:12px}.de-chat-foot{padding:0 14px 14px;background:#fff;font-size:12px}.de-chat-foot a{color:#2f68ff;font-weight:800;text-decoration:none}`;

  let conversationId = localStorage.getItem('de_chat_conversation_id') || '';
  const style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);

  function wireHeroVideoButton() {
    const heroButton = Array.from(document.querySelectorAll('.hero .btn-circle')).find((button) => button.textContent.toLowerCase().includes('watch intro'));
    if (!heroButton) return;
    heroButton.href = '#video-chat';
    heroButton.setAttribute('data-hero-video-chat', 'true');
    const label = heroButton.querySelector('strong');
    if (label) label.textContent = 'Chat With Dave';
    const icon = heroButton.querySelector('span:first-child');
    if (icon) icon.textContent = '▶';
    const script = document.createElement('script');
    script.src = 'assets/hero-video-chat.js';
    script.defer = true;
    document.body.appendChild(script);
  }

  fetch('/api/track-visit.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({page_url: location.href, page_title: document.title, referrer: document.referrer})
  }).catch(() => {});

  const bubble = document.createElement('button');
  bubble.className = 'de-chat-bubble';
  bubble.type = 'button';
  bubble.setAttribute('aria-label', 'Open chat');
  bubble.textContent = '✦';

  const panel = document.createElement('section');
  panel.className = 'de-chat-panel';
  panel.innerHTML = `
    <div class="de-chat-head"><strong>Project Assistant</strong><span>Ask a question or start a project.</span><a class="de-chat-link" href="/project-questions.php">Open project questions →</a></div>
    <div class="de-chat-messages" id="de-chat-messages"></div>
    <form class="de-chat-form" id="de-chat-form"><input class="de-chat-input" id="de-chat-input" autocomplete="off" placeholder="Type your message..."><button class="de-chat-send" type="submit">Send</button></form>
  `;
  document.body.appendChild(panel);
  document.body.appendChild(bubble);
  wireHeroVideoButton();

  const messagesEl = panel.querySelector('#de-chat-messages');
  const form = panel.querySelector('#de-chat-form');
  const input = panel.querySelector('#de-chat-input');

  function render(messages) {
    messagesEl.innerHTML = '';
    messages.forEach((msg) => {
      const el = document.createElement('div');
      el.className = `de-msg ${msg.sender_type || 'system'}`;
      el.textContent = msg.message;
      messagesEl.appendChild(el);
    });
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  async function start() {
    const res = await fetch('/api/chat-start.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({page_url: location.href})});
    const data = await res.json();
    if (data.ok) {
      conversationId = String(data.conversation_id);
      localStorage.setItem('de_chat_conversation_id', conversationId);
      render(data.messages || []);
    }
  }

  async function poll() {
    if (!conversationId || !panel.classList.contains('open')) return;
    const res = await fetch(`/api/chat-messages.php?conversation_id=${encodeURIComponent(conversationId)}`);
    const data = await res.json();
    if (data.ok) render(data.messages || []);
  }

  bubble.addEventListener('click', async () => {
    panel.classList.toggle('open');
    if (panel.classList.contains('open')) {
      bubble.textContent = '×';
      await start();
      input.focus();
    } else {
      bubble.textContent = '✦';
    }
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const message = input.value.trim();
    if (!message) return;
    input.value = '';
    const res = await fetch('/api/chat-send.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({conversation_id: conversationId, message, page_url: location.href})});
    const data = await res.json();
    if (data.ok) {
      conversationId = String(data.conversation_id);
      localStorage.setItem('de_chat_conversation_id', conversationId);
      render(data.messages || []);
    }
  });

  setInterval(poll, 8000);
})();
