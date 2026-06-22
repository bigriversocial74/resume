<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/admin_shell.php';
$admin = require_role('admin');
$conversations = list_chat_conversations(100);
if (($_GET['ajax'] ?? '') === '1') {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'conversations' => $conversations]);
    exit;
}
admin_shell_open('Chat Dashboard', 'Live Chat', 'Website conversations', 'Monitor active website conversations, accept new chats, and review visitor history.');
?>
<style>.conversation{display:grid;grid-template-columns:1fr auto;gap:18px}.meta{color:#667085;font-size:12px;line-height:1.6}.status{font-size:10px;text-transform:uppercase;letter-spacing:.08em;background:#eef4ff;color:#2f68ff;border-radius:999px;padding:6px 9px;font-weight:900}.unread{background:#fff0f4;color:#f13f67;border-radius:999px;padding:6px 9px;font-size:10px;font-weight:900}.btn{align-self:start;border-radius:4px;background:#27231f;color:#fff;padding:10px 14px;font-size:11px;font-weight:900;text-decoration:none}.chat-list{display:grid;gap:12px}@media(max-width:720px){.conversation{grid-template-columns:1fr}}</style>
<section class="chat-list" data-chat-list>
<?php foreach ($conversations as $row): ?>
    <article class="card conversation">
        <div>
            <h2><a href="<?= e(app_url('/admin/chat-thread.php?id=' . (int)$row['id'])) ?>"><?= e($row['name'] ?: $row['email'] ?: 'Visitor conversation #' . $row['id']) ?></a> <?php if ((int)($row['unread_admin_count'] ?? 0) > 0): ?><span class="unread"><?= (int)$row['unread_admin_count'] ?> new</span><?php endif; ?></h2>
            <div class="meta">Status: <span class="status"><?= e($row['status']) ?></span><br>Last message: <?= e($row['last_message'] ?: 'No messages') ?><br>Time on site: <?= (int)($row['total_time_on_site_seconds'] ?? 0) ?>s · Updated: <?= e($row['updated_at']) ?></div>
        </div>
        <a class="btn" href="<?= e(app_url('/admin/chat-thread.php?id=' . (int)$row['id'])) ?>"><?= (int)($row['unread_admin_count'] ?? 0) > 0 ? 'Accept Chat' : 'Open Thread' ?></a>
    </article>
<?php endforeach; ?>
<?php if (!$conversations): ?><div class="card">No chat conversations yet.</div><?php endif; ?>
</section>
<script>
(function(){const appBase=<?= json_encode(app_base_path()) ?>;const appUrl=p=>appBase+p;const list=document.querySelector('[data-chat-list]');if(!list)return;function esc(v){return String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}function render(rows){if(!rows.length){list.innerHTML='<div class="card">No chat conversations yet.</div>';return;}list.innerHTML=rows.map(r=>`<article class="card conversation"><div><h2><a href="${appUrl('/admin/chat-thread.php?id='+Number(r.id))}">${esc(r.name||r.email||('Visitor conversation #'+r.id))}</a> ${Number(r.unread_admin_count||0)>0?`<span class="unread">${Number(r.unread_admin_count)} new</span>`:''}</h2><div class="meta">Status: <span class="status">${esc(r.status)}</span><br>Last message: ${esc(r.last_message||'No messages')}<br>Time on site: ${Number(r.total_time_on_site_seconds||0)}s · Updated: ${esc(r.updated_at)}</div></div><a class="btn" href="${appUrl('/admin/chat-thread.php?id='+Number(r.id))}">${Number(r.unread_admin_count||0)>0?'Accept Chat':'Open Thread'}</a></article>`).join('');}async function poll(){try{const r=await fetch(appUrl('/admin/chat.php?ajax=1'));const d=await r.json();if(d.ok)render(d.conversations||[]);}catch(e){}}setInterval(poll,5000);})();
</script>
<?php admin_shell_close();
