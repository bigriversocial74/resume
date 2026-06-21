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
admin_shell_open('CRM Dashboard', 'Admin Dashboard', 'Project requests', 'Review new questionnaire submissions, open full request details, add notes, update status, and create customer accounts when a project moves forward.');
?>
<style>.request{display:grid;grid-template-columns:1fr auto;gap:20px}.meta{color:#667085;font-size:13px;line-height:1.6}.chips{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}.chip{font-size:11px;background:#eef4ff;color:#2f68ff;border-radius:999px;padding:6px 9px;font-weight:800}.actions{display:grid;gap:10px;align-content:start;min-width:180px}.select{border:1px solid #dfe5ef;border-radius:10px;padding:10px 12px}.btn{border:0;border-radius:999px;background:#2f68ff;color:#fff;padding:11px 16px;font-size:12px;font-weight:900;cursor:pointer;text-align:center;text-decoration:none}.btn.dark{background:#07101e}.detail{color:#2f68ff;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em}@media(max-width:760px){.request{grid-template-columns:1fr}.actions{min-width:0}}</style>
<section class="grid">
<?php foreach ($requests as $row): ?>
    <article class="card request">
        <div>
            <h2><a href="<?= e(app_url('/admin/request.php?id=' . (int)$row['id'])) ?>"><?= e($row['full_name'] ?: 'Unnamed request') ?></a></h2>
            <div class="meta"><strong><?= e($row['email']) ?></strong><?php if ($row['company']): ?> · <?= e($row['company']) ?><?php endif; ?><br>Budget: <?= e($row['budget_range'] ?: 'Not set') ?> · Timeline: <?= e($row['target_timeline'] ?: 'Not set') ?><br>Goal: <?= e($row['primary_goal'] ?: 'Not set') ?><br>Status: <?= e($row['status']) ?><br>Notes: <?= e(mb_strimwidth((string)$row['notes'], 0, 220, '...')) ?></div>
            <div class="chips"><?php foreach (json_decode($row['services'] ?: '[]', true) ?: [] as $service): ?><span class="chip"><?= e($service) ?></span><?php endforeach; ?></div>
            <p><a class="detail" href="<?= e(app_url('/admin/request.php?id=' . (int)$row['id'])) ?>">Open full request →</a></p>
        </div>
        <form method="post" class="actions"><?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>"><select class="select" name="status"><option value="new" <?= $row['status']==='new'?'selected':'' ?>>New</option><option value="reviewing" <?= $row['status']==='reviewing'?'selected':'' ?>>Reviewing</option><option value="qualified" <?= $row['status']==='qualified'?'selected':'' ?>>Qualified</option><option value="proposal_sent" <?= $row['status']==='proposal_sent'?'selected':'' ?>>Proposal Sent</option><option value="active" <?= $row['status']==='active'?'selected':'' ?>>Active</option><option value="closed_won" <?= $row['status']==='closed_won'?'selected':'' ?>>Closed Won</option><option value="closed_lost" <?= $row['status']==='closed_lost'?'selected':'' ?>>Closed Lost</option><option value="archived" <?= $row['status']==='archived'?'selected':'' ?>>Archived</option></select><button class="btn" type="submit">Update</button><a class="btn dark" href="<?= e(app_url('/admin/request.php?id=' . (int)$row['id'])) ?>">Details</a></form>
    </article>
<?php endforeach; ?>
<?php if (!$requests): ?><div class="card">No project requests yet.</div><?php endif; ?>
</section>
<?php admin_shell_close();
