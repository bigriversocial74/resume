(() => {
  const scriptUrl = new URL(document.currentScript?.src || 'assets/chat-widget.js', window.location.href);
  const appBasePath = scriptUrl.pathname.replace(/\/assets\/chat-widget\.js(?:\?.*)?$/, '').replace(/\/$/, '');
  const appUrl = (path) => `${appBasePath}${path.startsWith('/') ? path : `/${path}`}` || '/';

  const css = `
  .de-chat-bubble{position:fixed;right:24px;bottom:24px;z-index:9999;width:62px;height:62px;border:0;border-radius:999px;background:linear-gradient(135deg,#4c82ff,#2162ff);color:#fff;box-shadow:0 18px 50px rgba(47,104,255,.38);font:900 24px Inter,Arial,sans-serif;cursor:pointer}.de-chat-bubble b{position:absolute;right:-5px;top:-5px;min-width:20px;height:20px;border-radius:999px;background:#f13f67;color:#fff;font-size:11px;display:grid;place-items:center}.de-chat-panel{position:fixed;right:24px;bottom:98px;z-index:9999;width:min(410px,calc(100vw - 32px));height:590px;max-height:calc(100vh - 130px);background:#fff;border:1px solid #e5e7eb;border-radius:22px;box-shadow:0 28px 90px rgba(15,23,42,.22);overflow:hidden;display:none;font-family:Inter,Arial,sans-serif;color:#111827}.de-chat-panel.open{display:grid;grid-template-rows:auto 1fr auto}.de-chat-head{background:#07101e;color:#fff;padding:18px 20px}.de-chat-head-row{display:flex;align-items:center;justify-content:space-between;gap:12px}.de-chat-agent{display:flex;gap:11px;align-items:center}.de-chat-avatar{width:36px;height:36px;border-radius:999px;background:#2f68ff;display:grid;place-items:center;font-weight:900}.de-chat-head strong{display:block;font-size:15px}.de-chat-head span{display:block;color:rgba(255,255,255,.7);font-size:12px;margin-top:4px}.de-chat-status{font-size:11px;color:#86efac}.de-chat-link{display:block;color:#7dd3fc;margin-top:10px;font-size:12px}.de-chat-messages{padding:18px;overflow:auto;background:#f8faff;display:grid;gap:10px;align-content:start}.de-msg-wrap{display:grid;gap:4px;max-width:84%}.de-msg-wrap.visitor{justify-self:end}.de-msg-wrap.admin,.de-msg-wrap.system{justify-self:start}.de-msg{padding:11px 13px;border-radius:15px;font-size:13px;line-height:1.45}.de-msg.visitor{background:#2f68ff;color:#fff;border-bottom-right-radius:4px}.de-msg.admin{background:#fff;border:1px solid #e5e7eb;border-bottom-left-radius:4px}.de-msg.system{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-bottom-left-radius:4px}.de-msg-time{font-size:10px;line-height:1;color:#8a94a6}.de-msg-wrap.visitor .de-msg-time{text-align:right}.de-chat-form{position:sticky;bottom:0;display:grid;grid-template-columns:1fr auto;gap:10px;padding:14px;background:#fff;border-top:1px solid #e5e7eb}.de-chat-input{border:1px solid #dfe5ef;border-radius:999px;padding:12px 14px;font:inherit}.de-chat-send{border:0;border-radius:999px;background:#2f68ff;color:#fff;font-weight:900;padding:0 16px;cursor:pointer}.de-chat-send[disabled]{opacity:.6;cursor:not-allowed}`;

  let conversationId = localStorage.getItem('de_chat_conversation_id') || '';
  let lastMessageKey = localStorage.getItem('de_chat_last_key') || '';
  let unread = 0;
  let audioReady = false;
  const startedAt = Date.now();
  const style = document.createElement('style');
  style.textContent = css;
  document.head.appendChild(style);

  function formatTime(value) { const date = new Date(String(value || '').replace(' ', 'T')); return Number.isNaN(date.getTime()) ? '' : date.toLocaleString([], {hour:'numeric', minute:'2-digit'}); }
  function beep(){ if(!audioReady)return; try{const C=window.AudioContext||window.webkitAudioContext;const c=new C();const o=c.createOscillator();const g=c.createGain();o.frequency.value=620;g.gain.value=.035;o.connect(g);g.connect(c.destination);o.start();setTimeout(()=>{o.stop();c.close();},130);}catch(e){} }

  function wireHeroVideoButton() {
    const heroButton = Array.from(document.querySelectorAll('.hero .btn-circle')).find((button) => button.textContent.toLowerCase().includes('watch intro'));
    if (!heroButton) return;
    heroButton.href = '#video-chat';
    heroButton.setAttribute('data-hero-video-chat', 'true');
    const label = heroButton.querySelector('strong');
    if (label) label.textContent = 'Chat With Dave';
    const script = document.createElement('script');
    script.src = appUrl('/assets/hero-video-chat.js');
    script.defer = true;
    document.body.appendChild(script);
  }

  fetch(appUrl('/api/track-visit.php'), {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({page_url: location.href, page_title: document.title, referrer: document.referrer})}).catch(() => {});
  function heartbeat(ended=false){fetch(appUrl('/api/track-heartbeat.php'),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({page_url:location.href,time_on_page_seconds:Math.round((Date.now()-startedAt)/1000),ended})}).catch(()=>{});}
  setInterval(()=>heartbeat(false),15000); window.addEventListener('beforeunload',()=>heartbeat(true));

  const bubble = document.createElement('button');
  bubble.className = 'de-chat-bubble';
  bubble.type = 'button';
  bubble.setAttribute('aria-label', 'Open chat');
  bubble.innerHTML = '✦<b hidden>0</b>';
  const badge = bubble.querySelector('b');

  const panel = document.createElement('section');
  panel.className = 'de-chat-panel';
  panel.innerHTML = `<div class="de-chat-head"><div class="de-chat-head-row"><div class="de-chat-agent"><div class="de-chat-avatar">DE</div><div><strong>Dave's Assistant</strong><span>Usually replies in a few minutes.</span></div></div><div class="de-chat-status">● Live</div></div><a class="de-chat-link" href="${appUrl('/project-questions.php')}">Open project questions →</a></div><div class="de-chat-messages" id="de-chat-messages"></div><form class="de-chat-form" id="de-chat-form"><input class="de-chat-input" id="de-chat-input" autocomplete="off" placeholder="Type your message..."><button class="de-chat-send" type="submit">Send</button></form>`;
  document.body.appendChild(panel);
  document.body.appendChild(bubble);
  wireHeroVideoButton();

  const messagesEl = panel.querySelector('#de-chat-messages');
  const form = panel.querySelector('#de-chat-form');
  const input = panel.querySelector('#de-chat-input');
  const sendButton = panel.querySelector('.de-chat-send');

  function messageKey(messages){const last=messages[messages.length-1];return last?`${last.id || ''}:${last.sender_type}:${last.created_at}:${last.message}`:'';}
  function render(messages, notify=false) {
    const key = messageKey(messages);
    const last = messages[messages.length-1];
    if (notify && key && key !== lastMessageKey && last && last.sender_type !== 'visitor' && !panel.classList.contains('open')) { unread++; badge.textContent=String(unread); badge.hidden=false; beep(); }
    lastMessageKey = key || lastMessageKey; localStorage.setItem('de_chat_last_key', lastMessageKey);
    const nearBottom = messagesEl.scrollTop + messagesEl.clientHeight >= messagesEl.scrollHeight - 80;
    messagesEl.innerHTML = '';
    messages.forEach((msg) => { const type = msg.sender_type || 'system'; const wrap = document.createElement('div'); wrap.className = `de-msg-wrap ${type}`; const el = document.createElement('div'); el.className = `de-msg ${type}`; el.textContent = msg.message; wrap.appendChild(el); if (msg.created_at) { const time = document.createElement('div'); time.className = 'de-msg-time'; time.textContent = formatTime(msg.created_at); wrap.appendChild(time); } messagesEl.appendChild(wrap); });
    if (nearBottom || panel.classList.contains('open')) messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  async function start() { const res = await fetch(appUrl('/api/chat-start.php'), {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({page_url: location.href, referrer: document.referrer})}); const data = await res.json(); if (data.ok) { conversationId = String(data.conversation_id); localStorage.setItem('de_chat_conversation_id', conversationId); unread=0; badge.hidden=true; render(data.messages || []); } }
  async function poll() { if (!conversationId) return; try { const res = await fetch(appUrl(`/api/chat-messages.php?conversation_id=${encodeURIComponent(conversationId)}`)); const data = await res.json(); if (data.ok) render(data.messages || [], true); } catch(e){} }

  bubble.addEventListener('click', async () => { audioReady=true; panel.classList.toggle('open'); if (panel.classList.contains('open')) { bubble.firstChild.textContent = '×'; unread=0; badge.hidden=true; await start(); input.focus(); } else { bubble.firstChild.textContent = '✦'; } });
  document.addEventListener('click',()=>{audioReady=true;},{once:true});

  form.addEventListener('submit', async (e) => { e.preventDefault(); const message = input.value.trim(); if (!message) return; input.value = ''; sendButton.disabled = true; try { const res = await fetch(appUrl('/api/chat-send.php'), {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({conversation_id: conversationId, message, page_url: location.href, referrer: document.referrer})}); const data = await res.json(); if (data.ok) { conversationId = String(data.conversation_id); localStorage.setItem('de_chat_conversation_id', conversationId); render(data.messages || []); } } finally { sendButton.disabled = false; input.focus(); } });

  if (conversationId) poll();
  setInterval(poll, 3000);
})();
