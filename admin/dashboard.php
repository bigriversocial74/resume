<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/admin_shell.php';
$user = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['request_id'] ?? 0);
    $status = (string)($_POST['status'] ?? 'new');
    if ($id > 0) {
        update_project_request_status($id, $status, (int)$user['id']);
    }
    redirect_to('/admin/dashboard.php');
}

$requests = list_project_requests(60);
$analytics = analytics_summary();
$totalRequests = count($requests);
$newRequests = 0;
$activeRequests = 0;
$closedRequests = 0;
$statusCounts = [];
foreach ($requests as $request) {
    $status = (string)$request['status'];
    $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;
    if ($status === 'new') {
        $newRequests++;
    }
    if (in_array($status, ['qualified', 'proposal_sent', 'active'], true)) {
        $activeRequests++;
    }
    if (in_array($status, ['closed_won', 'closed_lost'], true)) {
        $closedRequests++;
    }
}

admin_shell_open('CRM Dashboard', 'Developer Portal', 'Project requests', 'Review project requests, chat activity, video agent status, and customer activity from one dashboard.');
?>
<style>
.dashboard-grid{display:grid;grid-template-columns:minmax(0,1fr) 330px;gap:22px;align-items:start}.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px}.stat-card{border:1px solid #dedfe3;border-radius:4px;padding:18px;background:#fff}.stat-card span{display:block;color:#6b7280;font-size:10px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.stat-card strong{display:block;font-size:26px;line-height:1;margin-top:12px;letter-spacing:-.04em}.request-list{display:grid;gap:12px}.request-card{display:grid;grid-template-columns:1fr auto;gap:18px;border:1px solid #dedfe3;border-radius:4px;background:#fff;padding:18px}.request-card h2{font-size:15px;margin:0 0 8px;letter-spacing:-.02em}.request-card h2 a{text-decoration:none;color:#242424}.meta{color:#626872;font-size:12px;line-height:1.55}.chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:11px}.chip{font-size:10px;background:#f2f4f7;color:#475467;border-radius:999px;padding:5px 8px;font-weight:800}.status-pill{display:inline-flex;align-items:center;border-radius:999px;padding:5px 8px;background:#fff0f4;color:#f13f67;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.06em}.actions{display:grid;gap:8px;align-content:start;min-width:154px}.select{border:1px solid #d8d9dd;border-radius:4px;padding:9px 10px;font-size:12px;background:#fff}.btn{border:0;border-radius:4px;background:#f13f67;color:#fff;padding:10px 13px;font-size:11px;font-weight:900;cursor:pointer;text-align:center;text-decoration:none}.btn.dark{background:#27231f}.btn.light{background:#fff;color:#27231f;border:1px solid #dedfe3}.side-stack{display:grid;gap:14px}.panel h2{font-size:15px;margin:0 0 13px;letter-spacing:-.02em}.mini-list{display:grid;gap:10px}.mini-row{display:flex;align-items:center;justify-content:space-between;gap:14px;color:#60646c;font-size:12px;border-bottom:1px solid #eee;padding-bottom:9px}.mini-row:last-child{border-bottom:0;padding-bottom:0}.mini-row strong{color:#242424}.quick-actions{display:grid;gap:9px}.quick-actions a{display:flex;align-items:center;justify-content:space-between;border:1px solid #dedfe3;border-radius:4px;padding:12px;color:#242424;text-decoration:none;font-weight:800;font-size:12px}.quick-actions a:hover{background:#fafafa}.empty{border:1px dashed #d7d8dc;border-radius:4px;padding:24px;color:#60646c;background:#fff}@media(max-width:1100px){.dashboard-grid{grid-template-columns:1fr}.stat-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:640px){.stat-grid,.request-card{grid-template-columns:1fr}.actions{min-width:0}}
</style>
<section class="stat-grid">
    <article class="stat-card"><span>Total Requests</span><strong><?= (int)$totalRequests ?></strong></article>
    <article class="stat-card"><span>New Requests</span><strong><?= (int)$newRequests ?></strong></article>
    <article class="stat-card"><span>Active Pipeline</span><strong><?= (int)$activeRequests ?></strong></article>
    <article class="stat-card"><span>Visits Today</span><strong><?= (int)$analytics['visits_today'] ?></strong></article>
</section>
<section class="dashboard-grid">
    <div class="request-list">
        <?php foreach ($requests as $row): ?>
            <article class="request-card">
                <div>
                    <h2><a href="<?= e(app_url('/admin/request.php?id=' . (int)$row['id'])) ?>"><?= e($row['full_name'] ?: 'Unnamed request') ?></a></h2>
                    <div class="meta"><strong><?= e($row['email']) ?></strong><?php if ($row['company']): ?> · <?= e($row['company']) ?><?php endif; ?><br>Budget: <?= e($row['budget_range'] ?: 'Not set') ?> · Timeline: <?= e($row['target_timeline'] ?: 'Not set') ?><br>Goal: <?= e($row['primary_goal'] ?: 'Not set') ?><br><span class="status-pill"><?= e($row['status']) ?></span></div>
                    <div class="chips"><?php foreach (json_decode($row['services'] ?: '[]', true) ?: [] as $service): ?><span class="chip"><?= e($service) ?></span><?php endforeach; ?></div>
                </div>
                <form method="post" class="actions"><?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>"><select class="select" name="status"><option value="new" <?= $row['status']==='new'?'selected':'' ?>>New</option><option value="reviewing" <?= $row['status']==='reviewing'?'selected':'' ?>>Reviewing</option><option value="qualified" <?= $row['status']==='qualified'?'selected':'' ?>>Qualified</option><option value="proposal_sent" <?= $row['status']==='proposal_sent'?'selected':'' ?>>Proposal Sent</option><option value="active" <?= $row['status']==='active'?'selected':'' ?>>Active</option><option value="closed_won" <?= $row['status']==='closed_won'?'selected':'' ?>>Closed Won</option><option value="closed_lost" <?= $row['status']==='closed_lost'?'selected':'' ?>>Closed Lost</option><option value="archived" <?= $row['status']==='archived'?'selected':'' ?>>Archived</option></select><button class="btn" type="submit">Update</button><a class="btn dark" href="<?= e(app_url('/admin/request.php?id=' . (int)$row['id'])) ?>">Open</a></form>
            </article>
        <?php endforeach; ?>
        <?php if (!$requests): ?><div class="empty">No project requests yet.</div><?php endif; ?>
    </div>
    <aside class="side-stack">
        <section class="panel"><h2>Quick actions</h2><div class="quick-actions"><a href="/admin/knowledge.php">Knowledge Base <span>→</span></a><a href="/admin/tavus.php#build">Build Tavus Avatar <span>→</span></a><a href="/admin/chat.php">Open Chats <span>→</span></a><a href="/admin/customers.php">Customers <span>→</span></a></div></section>
        <section class="panel"><h2>Status breakdown</h2><div class="mini-list"><?php foreach ($statusCounts as $status => $count): ?><div class="mini-row"><span><?= e($status) ?></span><strong><?= (int)$count ?></strong></div><?php endforeach; ?><?php if (!$statusCounts): ?><div class="mini-row"><span>No statuses yet</span><strong>0</strong></div><?php endif; ?></div></section>
        <section class="panel"><h2>Automation</h2><div class="mini-list"><div class="mini-row"><span>Open chats</span><strong><?= (int)$analytics['chats_open'] ?></strong></div><div class="mini-row"><span>Unique visitors today</span><strong><?= (int)$analytics['unique_today'] ?></strong></div><div class="mini-row"><span>Tavus video</span><strong><?= agent_setting('tavus_video_enabled', '1') === '1' ? 'On' : 'Off' ?></strong></div><div class="mini-row"><span>Text automation</span><strong><?= chat_automation_enabled() ? 'On' : 'Off' ?></strong></div></div></section>
    </aside>
</section>
<?php admin_shell_close();
