<?php
require_once __DIR__ . '/../app/bootstrap.php';
$admin = require_role('admin');
$message = null;
$error = null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'toggle_automation') {
            set_agent_setting('chat_automation_enabled', !empty($_POST['chat_automation_enabled']) ? '1' : '0', (int)$admin['id']);
            $message = 'Chat automation setting updated.';
        } elseif ($action === 'manual') {
            create_manual_knowledge((string)($_POST['title'] ?? 'Manual Knowledge'), (string)($_POST['content'] ?? ''), (int)$admin['id']);
            $message = 'Manual knowledge entry added and normalized.';
        } elseif ($action === 'website') {
            create_website_knowledge((string)($_POST['website_url'] ?? ''), (int)$admin['id']);
            $message = 'Website scanned and added to the knowledge base.';
        } elseif ($action === 'upload') {
            extract_uploaded_knowledge($_FILES['knowledge_file'] ?? [], (string)($_POST['upload_title'] ?? ''), (int)$admin['id']);
            $message = 'File uploaded. If text extraction was available, it was normalized into agent HTML.';
        } elseif ($action === 'archive') {
            $id = (int)($_POST['source_id'] ?? 0);
            if ($id > 0) {
                db_exec('UPDATE knowledge_sources SET status = "archived", updated_at = NOW() WHERE id = ?', [$id]);
                $message = 'Knowledge source archived.';
            }
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}

$enabled = chat_automation_enabled();
$sources = list_knowledge_sources(100);
$sourceCount = (int)(db_one('SELECT COUNT(*) AS total FROM knowledge_sources WHERE status = "ready"')['total'] ?? 0);
$chunkCount = (int)(db_one('SELECT COUNT(*) AS total FROM knowledge_chunks WHERE is_active = 1')['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Agent Knowledge Base</title><style>body{margin:0;background:#f8faff;font-family:Inter,Arial,sans-serif;color:#111827}.top{background:#07101e;color:#fff;padding:22px 0}.wrap{width:min(1180px,calc(100% - 40px));margin:auto}.top .wrap{display:flex;justify-content:space-between;gap:18px}.nav{display:flex;gap:18px;flex-wrap:wrap}a{color:inherit;text-decoration:none}.hero{padding:42px 0}.kicker{color:#2f68ff;font-size:11px;font-weight:900;letter-spacing:.18em;text-transform:uppercase}h1{font-size:42px;letter-spacing:-.055em;margin:10px 0}.muted{color:#667085;line-height:1.65}.cards{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:22px}.card,.panel{background:#fff;border:1px solid #edf0f6;border-radius:16px;padding:22px;box-shadow:0 18px 48px rgba(18,30,57,.055)}.card span{display:block;color:#667085;font-size:12px;font-weight:800;text-transform:uppercase}.card strong{display:block;font-size:34px;letter-spacing:-.055em;margin-top:8px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.field{display:grid;gap:7px;margin-bottom:14px}.field label{font-size:12px;font-weight:800;color:#667085}.input,textarea{width:100%;border:1px solid #dfe5ef;border-radius:10px;padding:13px;font:inherit}textarea{min-height:140px}.btn{border:0;border-radius:999px;background:#2f68ff;color:#fff;padding:12px 18px;font-size:12px;font-weight:900;cursor:pointer}.btn.dark{background:#07101e}.ok{background:#ecfdf5;color:#065f46;padding:14px;border-radius:12px;margin-bottom:18px}.err{background:#fef2f2;color:#991b1b;padding:14px;border-radius:12px;margin-bottom:18px}.toggle{display:flex;align-items:center;gap:14px}.toggle input{width:22px;height:22px}.source{display:grid;grid-template-columns:1fr auto;gap:18px;border-top:1px solid #edf0f6;padding:18px 0}.source:first-child{border-top:0}.pill{display:inline-flex;border-radius:999px;background:#eef4ff;color:#2f68ff;font-size:11px;font-weight:900;padding:6px 9px;text-transform:uppercase;letter-spacing:.06em}.notes{font-size:13px;color:#667085;line-height:1.55}.danger{background:#991b1b}.agent-syntax{background:#07101e;color:#fff;border-radius:16px;padding:22px;margin-top:18px}.agent-syntax code{white-space:pre-wrap;color:#bfdbfe}@media(max-width:900px){.cards,.grid{grid-template-columns:1fr}.top .wrap,.source{display:grid}}</style></head><body><header class="top"><div class="wrap"><strong>David Evans CRM</strong><nav class="nav"><a href="/admin/dashboard.php">Requests</a><a href="/admin/chat.php">Chats</a><a href="/admin/analytics.php">Analytics</a><a href="/admin/knowledge.php">Knowledge</a><a href="/admin/account.php">Account</a><a href="/admin/logout.php">Logout</a></nav></div></header><main class="wrap"><section class="hero"><div class="kicker">Agent Knowledge Base</div><h1>The brain of the chat agent</h1><p class="muted">Add websites, documents, media files, and manual entries. Clean text is normalized into agent-friendly semantic HTML so the automated chat layer has source material to answer from.</p></section><?php if ($message): ?><div class="ok"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?><section class="cards"><div class="card"><span>Chat Automation</span><strong><?= $enabled ? 'On' : 'Off' ?></strong></div><div class="card"><span>Ready Sources</span><strong><?= $sourceCount ?></strong></div><div class="card"><span>Active Chunks</span><strong><?= $chunkCount ?></strong></div></section><section class="panel"><h2>Automation toggle</h2><p class="muted">When enabled, the chat bubble answers with the knowledge base. When disabled, chat remains human-response based.</p><form method="post" class="toggle"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_automation"><input type="checkbox" name="chat_automation_enabled" value="1" <?= $enabled ? 'checked' : '' ?>><strong>Enable automated chat answers</strong><button class="btn" type="submit">Save</button></form></section><section class="grid" style="margin-top:18px"><div class="panel"><h2>Scan a website</h2><p class="muted">Reads public website text and converts it into agent-friendly HTML.</p><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="website"><div class="field"><label>Website URL</label><input class="input" name="website_url" placeholder="https://example.com" required></div><button class="btn" type="submit">Scan Website</button></form></div><div class="panel"><h2>Upload source file</h2><p class="muted">Supports TXT, HTML, Markdown, DOCX, PDF, MP3, WAV, MP4, MOV, and WEBM. Media files are stored for transcription worker integration.</p><form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="upload"><div class="field"><label>Title</label><input class="input" name="upload_title" placeholder="Optional title"></div><div class="field"><label>File</label><input class="input" type="file" name="knowledge_file" required></div><button class="btn" type="submit">Upload File</button></form></div></section><section class="panel" style="margin-top:18px"><h2>Manual knowledge entry</h2><p class="muted">Paste service details, pricing notes, FAQs, policies, project process, or brand voice instructions.</p><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="manual"><div class="field"><label>Title</label><input class="input" name="title" required></div><div class="field"><label>Content</label><textarea name="content" required placeholder="Write the facts the agent should know. Use headings, bullets, short answers, and clear source details."></textarea></div><button class="btn" type="submit">Add Knowledge</button></form><div class="agent-syntax"><strong>Agent-friendly syntax target</strong><p>The system stores cleaned knowledge as semantic HTML:</p><code>&lt;article class="agent-knowledge"&gt;
  &lt;h1&gt;Topic Title&lt;/h1&gt;
  &lt;dl class="agent-meta"&gt;source, type, date&lt;/dl&gt;
  &lt;section&gt;
    &lt;h2&gt;Question / Topic&lt;/h2&gt;
    &lt;p&gt;Direct answer with facts.&lt;/p&gt;
    &lt;ul&gt;&lt;li&gt;Clear supporting detail&lt;/li&gt;&lt;/ul&gt;
  &lt;/section&gt;
&lt;/article&gt;</code></div></section><section class="panel" style="margin-top:18px"><h2>Knowledge sources</h2><?php foreach ($sources as $source): ?><article class="source"><div><h3><?= e($source['title']) ?> <span class="pill"><?= e($source['status']) ?></span></h3><p class="notes">Type: <?= e($source['source_type']) ?> · Created by <?= e($source['created_by']) ?> · <?= e($source['created_at']) ?><br><?= e($source['extraction_notes'] ?: '') ?></p><?php if ($source['source_url']): ?><p><a href="<?= e($source['source_url']) ?>" target="_blank" rel="noopener"><?= e($source['source_url']) ?></a></p><?php endif; ?></div><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="archive"><input type="hidden" name="source_id" value="<?= (int)$source['id'] ?>"><button class="btn danger" type="submit">Archive</button></form></article><?php endforeach; ?><?php if (!$sources): ?><p class="muted">No knowledge sources yet.</p><?php endif; ?></section></main></body></html>
