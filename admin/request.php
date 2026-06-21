<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/admin_shell.php';
$admin = require_role('admin');
$id = (int)($_GET['id'] ?? $_POST['request_id'] ?? 0);
if ($id <= 0) {
    redirect_to('/admin/dashboard.php');
}
$message = null;
$error = null;
$createdLogin = null;
function json_list(?string $json): array
{
    $decoded = json_decode($json ?: '[]', true);
    return is_array($decoded) ? $decoded : [];
}
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $action = (string)($_POST['action'] ?? '');
        if ($action === 'status') {
            update_project_request_status($id, (string)($_POST['status'] ?? 'new'), (int)$admin['id']);
            $message = 'Status updated.';
        } elseif ($action === 'note') {
            add_project_note($id, (int)$admin['id'], (string)($_POST['note'] ?? ''));
            $message = 'Note added.';
        } elseif ($action === 'create_customer') {
            $request = read_project_request($id);
            if (!$request) {
                throw new RuntimeException('Request not found.');
            }
            $existing = db_one('SELECT * FROM users WHERE email = ? LIMIT 1', [normalize_email($request['email'])]);
            if ($existing) {
                $customerId = (int)$existing['id'];
                $createdLogin = ['email' => $existing['email'], 'username' => $existing['username'] ?? '', 'password' => null, 'existing' => true];
            } else {
                $created = create_user_by_admin((int)$admin['id'], (string)$request['full_name'], (string)$request['email'], 'customer');
                $customerId = (int)$created['id'];
                $createdLogin = ['email' => $created['email'], 'username' => $created['username'], 'password' => $created['temporary_password'], 'existing' => false];
            }
            db_exec('UPDATE project_requests SET customer_user_id = ?, status = "active", updated_by_user_id = ?, updated_at = NOW() WHERE id = ?', [$customerId, $admin['id'], $id]);
            $title = trim((string)($request['company'] ?: $request['primary_goal'] ?: 'Client Project'));
            $alreadyProject = db_one('SELECT id FROM customer_projects WHERE customer_user_id = ? AND project_request_id = ? LIMIT 1', [$customerId, $id]);
            if (!$alreadyProject) {
                db_exec('INSERT INTO customer_projects (customer_user_id, project_request_id, title, status, summary, created_at, updated_at) VALUES (?, ?, ?, "planning", ?, NOW(), NOW())', [$customerId, $id, $title, $request['notes']]);
            }
            $message = 'Customer account and project workspace are connected.';
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
$request = read_project_request($id);
if (!$request) {
    http_response_code(404);
    exit('Project request not found.');
}
$notes = db_all('SELECT n.*, u.full_name FROM project_request_notes n JOIN users u ON u.id = n.user_id WHERE n.project_request_id = ? ORDER BY n.created_at DESC', [$id]);
$customer = !empty($request['customer_user_id']) ? db_one('SELECT id, email, username, full_name FROM users WHERE id = ? LIMIT 1', [$request['customer_user_id']]) : null;
$statuses = ['new','reviewing','qualified','proposal_sent','active','closed_won','closed_lost','archived'];
admin_shell_open('Project Request #' . $id, 'Project Request', $request['full_name'] ?: 'Unnamed request', ($request['email'] ?: '') . ($request['company'] ? ' · ' . $request['company'] : '') . ' · Status: ' . $request['status']);
?>
<style>.detail-layout{display:grid;grid-template-columns:1fr 340px;gap:22px;padding-bottom:70px}.meta{color:#667085;line-height:1.7}.chips{display:flex;flex-wrap:wrap;gap:8px}.chip{background:#eef4ff;color:#2f68ff;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:800}.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field strong{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:#667085;margin-bottom:5px}.field div{line-height:1.55}.select,input,textarea{width:100%;border:1px solid #dfe5ef;border-radius:10px;padding:12px 13px;font:inherit}textarea{min-height:120px}.btn{border:0;border-radius:999px;background:#2f68ff;color:#fff;padding:12px 18px;font-size:12px;font-weight:900;cursor:pointer}.btn.secondary{background:#07101e}.ok{background:#ecfdf5;color:#065f46;padding:14px;border-radius:12px;margin-bottom:18px}.err{background:#fef2f2;color:#991b1b;padding:14px;border-radius:12px;margin-bottom:18px}.credential{background:#07101e;color:#fff;border-radius:14px;padding:18px;margin-bottom:18px}.credential code{display:block;background:rgba(255,255,255,.12);padding:9px;border-radius:8px;margin-top:6px}.note{border-top:1px solid #edf0f6;padding-top:14px;margin-top:14px}.note:first-child{border-top:0;margin-top:0;padding-top:0}@media(max-width:980px){.detail-layout,.detail-grid{grid-template-columns:1fr}}</style>
<?php if ($message): ?><div class="ok"><?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
<?php if ($createdLogin): ?><div class="credential"><strong><?= $createdLogin['existing'] ? 'Existing customer linked' : 'Customer account created' ?></strong><code>Username: <?= e($createdLogin['username']) ?></code><code>Email: <?= e($createdLogin['email']) ?></code><?php if ($createdLogin['password']): ?><code>Temporary password: <?= e($createdLogin['password']) ?></code><p>Share this through a secure channel and have the customer update it on first login.</p><?php endif; ?></div><?php endif; ?>
<div class="detail-layout"><section><div class="card"><h2>Intake details</h2><div class="detail-grid"><div class="field"><strong>Phone</strong><div><?= e($request['phone'] ?: 'Not provided') ?></div></div><div class="field"><strong>Budget</strong><div><?= e($request['budget_range'] ?: 'Not set') ?></div></div><div class="field"><strong>Timeline</strong><div><?= e($request['target_timeline'] ?: 'Not set') ?></div></div><div class="field"><strong>Goal</strong><div><?= e($request['primary_goal'] ?: 'Not set') ?></div></div><div class="field"><strong>Website</strong><div><?= $request['website_url'] ? '<a href="'.e($request['website_url']).'" target="_blank" rel="noopener">'.e($request['website_url']).'</a>' : 'Not provided' ?></div></div><div class="field"><strong>Brand assets</strong><div><?= $request['brand_assets_url'] ? '<a href="'.e($request['brand_assets_url']).'" target="_blank" rel="noopener">'.e($request['brand_assets_url']).'</a>' : 'Not provided' ?></div></div><div class="field"><strong>Social links</strong><div><?= nl2br(e($request['social_links'] ?: 'Not provided')) ?></div></div><div class="field"><strong>Created</strong><div><?= e($request['created_at']) ?></div></div></div></div><div class="card"><h2>Project types</h2><div class="chips"><?php foreach (json_list($request['project_types']) as $item): ?><span class="chip"><?= e($item) ?></span><?php endforeach; ?><?php if (!json_list($request['project_types'])): ?>None selected<?php endif; ?></div></div><div class="card"><h2>Services requested</h2><div class="chips"><?php foreach (json_list($request['services']) as $item): ?><span class="chip"><?= e($item) ?></span><?php endforeach; ?><?php if (!json_list($request['services'])): ?>None selected<?php endif; ?></div></div><div class="card"><h2>Client notes</h2><p class="meta"><?= nl2br(e($request['notes'] ?: 'No notes provided.')) ?></p></div><div class="card"><h2>Internal notes</h2><?php foreach ($notes as $note): ?><div class="note"><strong><?= e($note['full_name']) ?></strong> <span class="meta"><?= e($note['created_at']) ?></span><p><?= nl2br(e($note['note'])) ?></p></div><?php endforeach; ?><?php if (!$notes): ?><p class="meta">No internal notes yet.</p><?php endif; ?><form method="post"><?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int)$id ?>"><input type="hidden" name="action" value="note"><textarea name="note" placeholder="Add an internal note"></textarea><p><button class="btn" type="submit">Add Note</button></p></form></div></section><aside><div class="card"><h2>Status</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int)$id ?>"><input type="hidden" name="action" value="status"><select class="select" name="status"><?php foreach ($statuses as $status): ?><option value="<?= e($status) ?>" <?= $request['status']===$status?'selected':'' ?>><?= e(ucwords(str_replace('_',' ', $status))) ?></option><?php endforeach; ?></select><p><button class="btn" type="submit">Update Status</button></p></form></div><div class="card"><h2>Customer account</h2><?php if ($customer): ?><p class="meta">Linked to <?= e($customer['full_name']) ?><br>Username: <?= e($customer['username']) ?><br>Email: <?= e($customer['email']) ?></p><?php else: ?><p class="meta">Create or link a customer account from this request, then open a project workspace.</p><form method="post"><?= csrf_field() ?><input type="hidden" name="request_id" value="<?= (int)$id ?>"><input type="hidden" name="action" value="create_customer"><button class="btn secondary" type="submit">Create Customer</button></form><?php endif; ?></div></aside></div>
<?php admin_shell_close();
