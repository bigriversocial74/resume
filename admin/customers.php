<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/admin_shell.php';
$admin = require_role('admin');
$message = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $created = create_user_by_admin((int)$admin['id'], (string)($_POST['full_name'] ?? ''), (string)($_POST['email'] ?? ''), 'customer');
        $message = 'Customer account created for ' . $created['email'] . '. Use the server-side account setup process to deliver credentials securely.';
    } catch (Throwable $e) {
        $error = 'Could not create the account. Confirm the email is valid and not already used.';
    }
}
$customers = db_all('SELECT id, full_name, email, status, created_at, last_login_at FROM users WHERE role = "customer" ORDER BY created_at DESC LIMIT 100');
admin_shell_open('Customer Accounts', 'Customer Accounts', 'Customer accounts', 'Create and review customer portal accounts.');
?>
<style>.fields{display:grid;grid-template-columns:1fr 1fr auto;gap:12px;align-items:end}.field{display:grid;gap:7px}.field label{font-size:12px;font-weight:800;color:#667085}.input{border:1px solid #dfe5ef;border-radius:10px;padding:13px 14px;font:inherit}.btn{border:0;border-radius:999px;background:#2f68ff;color:#fff;min-height:44px;padding:0 18px;font-size:12px;font-weight:900;cursor:pointer}.success{background:#ecfdf5;color:#065f46;padding:16px;border-radius:12px;margin-bottom:16px}.error{background:#fef2f2;color:#991b1b;padding:16px;border-radius:12px;margin-bottom:16px}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:13px;border-bottom:1px solid #edf0f6;font-size:13px}th{color:#667085;font-size:11px;text-transform:uppercase;letter-spacing:.08em}@media(max-width:760px){.fields{grid-template-columns:1fr}}</style>
<section class="panel"><h2>Create customer account</h2><p>Admin-created accounts only. Customers cannot self-register.</p><?php if ($message): ?><div class="success"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?><form method="post" class="fields"><?= csrf_field() ?><div class="field"><label>Full name</label><input class="input" name="full_name" required></div><div class="field"><label>Email</label><input class="input" type="email" name="email" required></div><button class="btn" type="submit">Create</button></form></section>
<section class="panel" style="margin-top:24px"><h2>Recent customers</h2><table><thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Created</th><th>Last Login</th></tr></thead><tbody><?php foreach ($customers as $customer): ?><tr><td><?= e($customer['full_name']) ?></td><td><?= e($customer['email']) ?></td><td><?= e($customer['status']) ?></td><td><?= e($customer['created_at']) ?></td><td><?= e($customer['last_login_at'] ?: 'Never') ?></td></tr><?php endforeach; ?></tbody></table></section>
<?php admin_shell_close();
