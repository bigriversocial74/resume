<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/admin_shell.php';
$admin = require_role('admin');
$id = (int)($_GET['id'] ?? $_POST['conversation_id'] ?? 0);
if ($id <= 0) {
    redirect_to('/admin/chat.php');
}
$message = null;
$error = null;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $action = (string)($_POST['action'] ?? 'reply');
        if ($action === 'reply') {
            add_chat_message($id, 'admin', (string)($_POST['message'] ?? ''), (int)$admin['id']);
            db_exec('UPDATE chat_conversations SET status = "pending", assigned_to_user_id = ?, updated_at = NOW() WHERE id = ?', [$admin['id'], $id]);
            $message = 'Reply added.';
        } elseif ($action === 'status') {
            $status = (string)($_POST['status'] ?? 'open');
            if (!in_array($status, ['open','pending','closed','archived'], true)) {
                throw new RuntimeException('Invalid status.');
            }
            db_exec('UPDATE chat_conversations SET status = ?, updated_at = NOW() WHERE id = ?', [$status, $id]);
            $message = 'Status updated.';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
$conversation = read_chat_conversation($id);
if (!$conversation) {
    http_response_code(404);
    exit('Conversation not found.');
}
$messages = read_chat_messages($id);
admin_shell_open('Chat Thread', 'Chat Thread', $conversation['name'] ?: $conversation['email'] ?: 'Visitor conversation #' . $conversation['id'], 'Status: ' . $conversation['status'] . ' · Visitor: ' . substr($conversation['visitor_key'], 0, 12));
?>
<style>.msg{max-width:76%;padding:13px 15px;border-radius:16px;margin:12px 0}.visitor{background:#2f68ff;color:#fff;margin-left:auto;border-bottom-right-radius:4px}.admin,.system{background:#fff;border:1px solid #e5e7eb;border-bottom-left-radius:4px}.meta{font-size:12px;color:#667085;margin-bottom:5px}.visitor .meta{color:rgba(255,255,255,.7)}textarea,.select{width:100%;border:1px solid #dfe5ef;border-radius:10px;padding:13px;font:inherit}textarea{min-height:120px}.btn{border:0;border-radius:999px;background:#2f68ff;color:#fff;padding:12px 18px;font-size:12px;font-weight:900;cursor:pointer}.ok{background:#ecfdf5;color:#065f46;padding:14px;border-radius:12px;margin-bottom:18px}.err{background:#fef2f2;color:#991b1b;padding:14px;border-radius:12px;margin-bottom:18px}</style>
<?php if ($message): ?><div class="ok"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<section class="card"><?php foreach ($messages as $row): ?><div class="msg <?= e($row['sender_type']) ?>"><div class="meta"><?= e($row['sender_type']) ?><?= $row['sender_name'] ? ' · ' . e($row['sender_name']) : '' ?> · <?= e($row['created_at']) ?></div><?= nl2br(e($row['message'])) ?></div><?php endforeach; ?></section>
<section class="card"><h2>Reply</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="conversation_id" value="<?= (int)$id ?>"><input type="hidden" name="action" value="reply"><textarea name="message" placeholder="Type your reply..."></textarea><p><button class="btn" type="submit">Send Reply</button></p></form></section>
<section class="card"><h2>Status</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="conversation_id" value="<?= (int)$id ?>"><input type="hidden" name="action" value="status"><select class="select" name="status"><option value="open" <?= $conversation['status']==='open'?'selected':'' ?>>Open</option><option value="pending" <?= $conversation['status']==='pending'?'selected':'' ?>>Pending</option><option value="closed" <?= $conversation['status']==='closed'?'selected':'' ?>>Closed</option><option value="archived" <?= $conversation['status']==='archived'?'selected':'' ?>>Archived</option></select><p><button class="btn" type="submit">Update Status</button></p></form></section>
<?php admin_shell_close();
