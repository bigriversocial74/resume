<?php
require_once __DIR__ . '/../app/bootstrap.php';

$existing = current_user();
if ($existing) {
    redirect_to($existing['role'] === 'admin' ? '/admin/dashboard.php' : '/customer/dashboard.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $ok = authenticate((string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''));
    if ($ok) {
        $user = current_user();
        redirect_to(($user && $user['role'] === 'admin') ? '/admin/dashboard.php' : '/customer/dashboard.php');
    }
    $error = 'Invalid login or temporarily locked account.';
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login | David Evans CRM</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#07101e;font-family:Inter,Arial,sans-serif;color:#111827}.card{width:min(440px,calc(100% - 32px));background:#fff;border-radius:22px;padding:34px;box-shadow:0 28px 90px rgba(0,0,0,.32)}h1{margin:0 0 8px;font-size:30px;letter-spacing:-.04em}.muted{color:#667085;margin:0 0 26px;line-height:1.55}.field{display:grid;gap:8px;margin-bottom:16px}label{font-size:12px;font-weight:800;color:#475569}input{border:1px solid #dfe5ef;border-radius:10px;padding:14px 15px;font:inherit}.btn{width:100%;height:50px;border:0;border-radius:999px;background:linear-gradient(135deg,#4c82ff,#2162ff);color:#fff;font-size:12px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;cursor:pointer}.error{background:#fef2f2;color:#991b1b;border-radius:12px;padding:12px 14px;margin-bottom:16px;font-weight:700}.back{display:inline-block;margin-top:18px;color:#2f68ff;text-decoration:none;font-size:13px;font-weight:800}</style></head><body><main class="card"><h1>Account Login</h1><p class="muted">Sign in to review project requests, CRM records, or your customer workspace.</p><?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="field"><label>Username or Email</label><input type="text" name="email" required autocomplete="username"></div><div class="field"><label>Password</label><input type="password" name="password" required autocomplete="current-password"></div><button class="btn" type="submit">Sign In</button></form><a class="back" href="<?= e(app_url('/index.html')) ?>">Back to website</a></main></body></html>
