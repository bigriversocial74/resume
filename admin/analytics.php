<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/admin_shell.php';
$admin = require_role('admin');
$summary = analytics_summary();
$recentVisits = db_all('SELECT page_url, page_title, referrer, created_at FROM website_visits ORDER BY created_at DESC LIMIT 80');
$topPages = db_all('SELECT page_url, COUNT(*) AS visits, COUNT(DISTINCT visitor_key) AS unique_visitors FROM website_visits GROUP BY page_url ORDER BY visits DESC LIMIT 20');
$daily = db_all('SELECT DATE(created_at) AS day, COUNT(*) AS visits, COUNT(DISTINCT visitor_key) AS unique_visitors FROM website_visits GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 14');
admin_shell_open('Website Analytics', 'Analytics Dashboard', 'Website visits and chat activity', 'Track visits, unique visitors, open chats, and project request activity.');
?>
<style>.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}.card span{display:block;color:#667085;font-size:12px;font-weight:800;text-transform:uppercase}.card strong{display:block;font-size:38px;letter-spacing:-.055em;margin-top:8px}.analytics-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}table{width:100%;border-collapse:collapse}td,th{padding:11px;border-bottom:1px solid #edf0f6;text-align:left;font-size:13px;vertical-align:top}th{font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#667085}.url{max-width:420px;word-break:break-word;color:#2f68ff}@media(max-width:1000px){.cards,.analytics-grid{grid-template-columns:1fr}}</style>
<section class="cards">
    <div class="card"><span>Visits Today</span><strong><?= (int)$summary['visits_today'] ?></strong></div>
    <div class="card"><span>Unique Today</span><strong><?= (int)$summary['unique_today'] ?></strong></div>
    <div class="card"><span>Open Chats</span><strong><?= (int)$summary['chats_open'] ?></strong></div>
    <div class="card"><span>New Requests</span><strong><?= (int)$summary['new_requests'] ?></strong></div>
</section>
<section class="analytics-grid">
    <div class="panel"><h2>Top pages</h2><table><thead><tr><th>Page</th><th>Visits</th><th>Unique</th></tr></thead><tbody><?php foreach ($topPages as $row): ?><tr><td class="url"><?= e($row['page_url']) ?></td><td><?= (int)$row['visits'] ?></td><td><?= (int)$row['unique_visitors'] ?></td></tr><?php endforeach; ?></tbody></table></div>
    <div class="panel"><h2>Last 14 days</h2><table><thead><tr><th>Day</th><th>Visits</th><th>Unique</th></tr></thead><tbody><?php foreach ($daily as $row): ?><tr><td><?= e($row['day']) ?></td><td><?= (int)$row['visits'] ?></td><td><?= (int)$row['unique_visitors'] ?></td></tr><?php endforeach; ?></tbody></table></div>
</section>
<section class="panel" style="margin-top:18px"><h2>Recent visits</h2><table><thead><tr><th>Page</th><th>Referrer</th><th>Time</th></tr></thead><tbody><?php foreach ($recentVisits as $row): ?><tr><td class="url"><?= e($row['page_title'] ?: $row['page_url']) ?><br><small><?= e($row['page_url']) ?></small></td><td><?= e($row['referrer'] ?: 'Direct') ?></td><td><?= e($row['created_at']) ?></td></tr><?php endforeach; ?></tbody></table></section>
<?php admin_shell_close();
