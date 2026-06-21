<?php
require_once __DIR__ . '/../app/bootstrap.php';
$user = require_login();
$message = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $username = strtolower(trim((string)($_POST['username'] ?? '')));
        $email = normalize_email((string)($_POST['email'] ?? ''));
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        if (!preg_match('/^[a-z0-9_]{3,80}$/', $username)) {
            throw new RuntimeException('Username must use lowercase letters, numbers, or underscores.');
        }
        if (!valid_email($email)) {
            throw new RuntimeException('Email is invalid.');
        }
        $dupe = db_one('SELECT id FROM users WHERE (email = ? OR username = ?) AND id <> ? LIMIT 1', [$email, $username, $user['id']]);
        if ($dupe) {
            throw new RuntimeException('That email or username is already used.');
        }
        db_exec('UPDATE users SET username = ?, email = ?, full_name = ?, updated_at = NOW() WHERE id = ?', [$username, $email, $fullName, $user['id']]);
        $newPass = (string)($_POST['new_pass'] ?? '');
        if ($newPass !== '') {
            if (!password_meets_policy($newPass)) {
                throw new RuntimeException('New password must be at least 14 characters with uppercase, lowercase, number, and symbol.');
            }
            db_exec('UPDATE users SET password_hash = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?', [password_hash($newPass, PASSWORD_DEFAULT), $user['id']]);
        }
        $message = 'Account updated.';
        $user = current_user();
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
$back = $user['role'] === 'admin' ? '/admin/dashboard.php' : '/customer/dashboard.php';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Account Settings</title><style>body{margin:0;background:#f8faff;font-family:Arial,sans-serif;color:#111827}.wrap{width:min(640px,calc(100% - 40px));margin:40px auto}.card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:28px;box-shadow:0 18px 48px rgba(18,30,57,.08)}label{display:block;margin-top:14px;font-size:12px;font-weight:700;color:#667085}input{width:100%;padding:13px;border:1px solid #dfe5ef;border-radius:10px;font:inherit}.btn{margin-top:18px;border:0;border-radius:999px;background:#2f68ff;color:#fff;padding:13px 20px;font-weight:800}.ok{background:#ecfdf5;color:#065f46;padding:12px;border-radius:10px}.err{background:#fef2f2;color:#991b1b;padding:12px;border-radius:10px}a{color:#2f68ff}</style></head><body><main class="wrap"><p><a href="<?= e($back) ?>">Back</a></p><section class="card"><h1>Account Settings</h1><?php if ($message): ?><div class="ok"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><label>Username</label><input name="username" value="<?= e($user['username'] ?? '') ?>" required><label>Email</label><input type="email" name="email" value="<?= e($user['email']) ?>" required><label>Full name</label><input name="full_name" value="<?= e($user['full_name']) ?>" required><label>New password</label><input type="password" name="new_pass" autocomplete="new-password"><button class="btn" type="submit">Save Account</button></form></section></main></body></html>
