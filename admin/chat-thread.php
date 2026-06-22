<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/admin_shell.php';
$admin = require_role('admin');
$id = (int)($_GET['id'] ?? $_POST['conversation_id'] ?? 0);
if ($id <= 0) {
    redirect_to('/admin/chat.php');
}

if (($_GET['ajax'] ?? '') === '1') {
    header('Content-Type: application/json');
    $conversation = read_chat_conversation($id);
    if (!$conversation) {
        http_response_code(404);
        echo json_encode(['ok' => false]);
        exit;
    }
    mark_chat_read_for_admin($id);
    echo json_encode(['ok' => true, 'conversation' => $conversation, 'messages' => read_chat_messages($id)]);
    exit;
}

$message = null;
$error = null;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $action = (string)($_POST['action'] ?? 'reply');
        if ($action === 'reply') {
            add_chat_message($id, 'admin', (string)($_POST['message'] ?? ''), (int)$admin['id']);
            db_exec('UPDATE chat_conversations SET status = "pending", assigned_to_user_id = ?, accepted_at = COALESCE(accepted_at, NOW()), unread_admin_count = 0, updated_at = NOW() WHERE id = ?', [$admin['id'], $id]);
            $message = 'Reply added.';
        } elseif ($action === 'status') {
            $status = (string)($_POST['status'] ?? 'open');
            if (!in_array($status, ['open','pending','closed','archived'], true)) {
                throw new RuntimeException('Invalid status.');
            }
            db_exec('UPDATE chat_conversations SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $id]);
            $message = 'Status updated.';
        }
        if (($_POST['ajax'] ?? '') === '1') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'messages' => read_chat_messages($id)]);
            exit;
        }
    }
} catch (Throwable $e) {
    if (($_POST['ajax'] ?? '') === '1') {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        exit;
    }
    $error = $e->getMessage();
}
$conversation = read_chat_conversation($id);
if (!$conversation) {
    http_response_code(404);
    exit('Conversation not found.');
}
mark_chat_read_for_admin($id);
$messages = read_chat_messages($id);
admin_shell_open('Chat Thread', 'Live Chat', $conversation['name'] ?: $conversation['email'] ?: 'Visitor conversation #' . $conversation['id'], 'Professional live chat with auto-refresh, visitor history, and sticky reply composer.');
?>
<style>
.chat-workspace{height:calc(100vh - 190px);min-height:560px;display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:18px}.chat-frame{border:1px solid #dedfe3;border-radius:8px;background:#f7f8fb;display:grid;grid-template-rows:auto 1fr auto;min-height:0}.chat-head{display:flex;justify-content:space-between;gap:14px;align-items:center;padding:15px 18px;background:#fff;border-bottom:1px solid #e6e7eb}.chat-head strong{font-size:14px}.chat-head span{color:#667085;font-size:12px}.chat-canvas{overflow:auto;padding:18px;display:grid;gap:12px;align-content:start;min-height:0}.msg{max-width:min(72%,720px);padding:12px 14px;border-radius:16px;font-size:13px;line-height:1.48;box-shadow:0 8px 22px rgba(15,23,42,.04)}.msg.visitor{justify-self:start;background:#fff;border:1px solid #e5e7eb;border-bottom-left-radius:4px}.msg.admin{justify-self:end;background:#2f68ff;color:#fff;border-bottom-right-radius:4px}.msg.system{justify-self:center;background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;max-width:86%;font-size:12px}.meta{font-size:11px;color:#667085;margin-bottom:5px}.admin .meta{color:rgba(255,255,255,.7)}.chat-composer{position:sticky;bottom:0;background:#fff;border-top:1px solid #e6e7eb;padding:12px}.composer-form{display:grid;grid-template-columns:1fr auto;gap:10px;align-items:end}.composer-form textarea{width:100%;min-height:46px;max-height:150px;resize:vertical;border:1px solid #d8d9dd;border-radius:8px;padding:12px;font:inherit}.btn{border:0;border-radius:6px;background:#f13f67;color:#fff;padding:12px 16px;font-size:12px;font-weight:900;cursor:pointer;text-decoration:none}.btn.dark{background:#27231f}.side-card{border:1px solid #dedfe3;border-radius:8px;background:#fff;padding:18px}.side-card h2{font-size:14px;margin:0 0 12px}.fact{display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid #eee;padding:9px 0;font-size:12px}.fact:last-child{border-bottom:0}.fact span{color:#667085}.select{width:100%;border:1px solid #d8d9dd;border-radius:6px;padding:10px;font:inherit}.ok{background:#ecfdf5;color:#065f46;padding:12px;border-radius:8px;margin-bottom:12px}.err{background:#fef2f2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:12px}@media(max-width:1000px){.chat-workspace{grid-template-columns:1fr;height:auto}.chat-frame{height:70vh}.side-stack{display:grid;grid-template-columns:1fr 1fr;gap:12px}}@media(max-width:650px){.composer-form{grid-template-columns:1fr}.side-stack{grid-template-columns:1fr}.msg{max-width:92%}}
</style>
<?php if ($message): ?><div class="ok"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<section class="chat-workspace" data-conversation-id="<?= (int)$id ?>">
    <div class="chat-frame">
        <div class="chat-head"><div><strong><?= e($conversation['name'] ?: $conversation['email'] ?: 'Website visitor') ?></strong><br><span>Status: <span data-chat-status><?= e($conversation['status']) ?></span></span></div><a class="btn dark" href="<?= e(app_url('/admin/chat.php')) ?>">All chats</a></div>
        <div class="chat-canvas" data-chat-canvas><?php foreach ($messages as $row): ?><div class="msg <?= e($row['sender_type']) ?>"><div class="meta"><?= e($row['sender_type']) ?><?= $row['sender_name'] ? ' · ' . e($row['sender_name']) : '' ?> · <?= e($row['created_at']) ?></div><?= nl2br(e($row['message'])) ?></div><?php endforeach; ?></div>
        <div class="chat-composer"><form method="post" class="composer-form" data-chat-reply-form><?= csrf_field() ?><input type="hidden" name="conversation_id" value="<?= (int)$id ?>"><input type="hidden" name="action" value="reply"><input type="hidden" name="ajax" value="1"><textarea name="message" placeholder="Write a reply..." required></textarea><button class="btn" type="submit">Send</button></form></div>
    </div>
    <aside class="side-stack">
        <section class="side-card"><h2>Visitor</h2><div class="fact"><span>IP hash</span><strong><?= e(substr((string)($conversation['ip_hash'] ?? ''), 0, 12) ?: 'n/a') ?></strong></div><div class="fact"><span>Time on site</span><strong><?= (int)($conversation['total_time_on_site_seconds'] ?? 0) ?>s</strong></div><div class="fact"><span>Last seen</span><strong><?= e($conversation['last_seen_at'] ?: 'n/a') ?></strong></div><div class="fact"><span>First page</span><strong><?= e(mb_strimwidth((string)($conversation['first_page_url'] ?? ''), 0, 34, '...')) ?></strong></div></section>
        <section class="side-card"><h2>Status</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="conversation_id" value="<?= (int)$id ?>"><input type="hidden" name="action" value="status"><select class="select" name="status"><option value="open" <?= $conversation['status']==='open'?'selected':'' ?>>Open</option><option value="pending" <?= $conversation['status']==='pending'?'selected':'' ?>>Pending</option><option value="closed" <?= $conversation['status']==='closed'?'selected':'' ?>>Closed</option><option value="archived" <?= $conversation['status']==='archived'?'selected':'' ?>>Archived</option></select><p><button class="btn" type="submit">Update</button></p></form></section>
    </aside>
</section>
<script>
(function(){
const appBase=<?= json_encode(app_base_path()) ?>;const appUrl=p=>appBase+p;const root=document.querySelector('[data-conversation-id]');if(!root)return;const id=root.dataset.conversationId;const canvas=root.querySelector('[data-chat-canvas]');const form=root.querySelector('[data-chat-reply-form]');let lastHtml='';
function esc(v){return String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
function render(messages){const html=messages.map(m=>`<div class="msg ${esc(m.sender_type)}"><div class="meta">${esc(m.sender_type)}${m.sender_name?' · '+esc(m.sender_name):''} · ${esc(m.created_at)}</div>${esc(m.message).replace(/\n/g,'<br>')}</div>`).join('');if(html!==lastHtml){const nearBottom=canvas.scrollTop+canvas.clientHeight>=canvas.scrollHeight-80;canvas.innerHTML=html;lastHtml=html;if(nearBottom)canvas.scrollTop=canvas.scrollHeight;}}
async function poll(){try{const r=await fetch(appUrl(`/admin/chat-thread.php?id=${encodeURIComponent(id)}&ajax=1`));const d=await r.json();if(d.ok){render(d.messages||[]);if(d.conversation&&d.conversation.status){document.querySelector('[data-chat-status]').textContent=d.conversation.status;}}}catch(e){}}
form.addEventListener('submit',async e=>{e.preventDefault();const fd=new FormData(form);const ta=form.querySelector('textarea');if(!ta.value.trim())return;try{const r=await fetch(appUrl('/admin/chat-thread.php?id='+encodeURIComponent(id)),{method:'POST',body:fd});const d=await r.json();if(d.ok){ta.value='';render(d.messages||[]);canvas.scrollTop=canvas.scrollHeight;}}catch(err){form.submit();}});
canvas.scrollTop=canvas.scrollHeight;poll();setInterval(poll,2500);
})();
</script>
<?php admin_shell_close();
