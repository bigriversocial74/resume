<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/admin_shell.php';
$admin = require_role('admin');
$conversations = list_chat_conversations(100);
admin_shell_open('Chat Dashboard', 'Chat Dashboard', 'Website conversations', 'Multiple visitors can open chat conversations from the website bubble. This dashboard lists active and historical threads.');
?>
<style>.conversation{display:grid;grid-template-columns:1fr auto;gap:18px}.meta{color:#667085;font-size:13px;line-height:1.6}.status{font-size:11px;text-transform:uppercase;letter-spacing:.08em;background:#eef4ff;color:#2f68ff;border-radius:999px;padding:7px 10px;font-weight:900}.btn{align-self:start;border-radius:999px;background:#07101e;color:#fff;padding:11px 16px;font-size:12px;font-weight:900;text-decoration:none}@media(max-width:720px){.conversation{grid-template-columns:1fr}}</style>
<section class="grid">
<?php foreach ($conversations as $row): ?>
    <article class="card conversation">
        <div>
            <h2><a href="<?= e(app_url('/admin/chat-thread.php?id=' . (int)$row['id'])) ?>"><?= e($row['name'] ?: $row['email'] ?: 'Visitor conversation #' . $row['id']) ?></a></h2>
            <div class="meta">Status: <span class="status"><?= e($row['status']) ?></span><br>Last message: <?= e($row['last_message'] ?: 'No messages') ?><br>Updated: <?= e($row['updated_at']) ?></div>
        </div>
        <a class="btn" href="<?= e(app_url('/admin/chat-thread.php?id=' . (int)$row['id'])) ?>">Open Thread</a>
    </article>
<?php endforeach; ?>
<?php if (!$conversations): ?><div class="card">No chat conversations yet.</div><?php endif; ?>
</section>
<?php admin_shell_close();
