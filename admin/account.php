<?php
require_once __DIR__ . '/../app/bootstrap.php';
$user = require_login();
$message = null;
$error = null;
$forceChange = isset($_GET['first_login']) || (int)($user['must_change_password'] ?? 0) === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $username = strtolower(trim((string)($_POST['username'] ?? '')));
        $email = normalize_email((string)($_POST['email'] ?? ''));
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        if (!preg_match('/^[a-z0-9_]{3,80}$/', $username)) {
            throw new RuntimeException('Username must use 3-80 lowercase letters, numbers, or underscores.');
        }
        if (!valid_email($email)) {
            throw new RuntimeException('Email is invalid.');
        }
        if ($fullName === '') {
            throw new RuntimeException('Full name is required.');
        }
        $dupe = db_one('SELECT id FROM users WHERE (email = ? OR username = ?) AND id <> ? LIMIT 1', [$email, $username, $user['id']]);
        if ($dupe) {
            throw new RuntimeException('Email or username already exists.');
        }

        $passwordChange = $forceChange || $new !== '' || $confirm !== '';
        if ($passwordChange) {
            $row = db_one('SELECT password_hash FROM users WHERE id = ? LIMIT 1', [$user['id']]);
            if (!$row || !password_verify($current, $row['password_hash'])) {
                throw new RuntimeException('Current password is required to change password.');
            }
            if ($new === '' || $new !== $confirm) {
                throw new RuntimeException('New password and confirmation must match.');
            }
            if (!password_meets_policy($new)) {
                throw new RuntimeException('New password must be at least 14 characters with uppercase, lowercase, number, and symbol.');
            }
            db_exec('UPDATE users SET username = ?, email = ?, full_name = ?, password_hash = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?', [$username, $email, $fullName, password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        } else {
            db_exec('UPDATE users SET username = ?, email = ?, full_name = ?, updated_at = NOW() WHERE id = ?', [$username, $email, $fullName, $user['id']]);
        }
        $message = 'Account updated.';
        $user = current_user();
        $forceChange = (int)($user['must_change_password'] ?? 0) === 1;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
$back = $user['role'] === 'admin' ? '/admin/dashboard.php' : '/customer/dashboard.php';
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Account Settings</title><style>body{margin:0;background:#f8faff;font-family:Arial,sans-serif;color:#111827}.wrap{width:min(680px,calc(100% - 40px));margin:40px auto}.card{background:#fff;border:1px solid #e5e7eb;border-radius:18px;padding:28px;box-shadow:0 18px 48px rgba(18,30,57,.08)}label{display:block;margin-top:14px;font-size:12px;font-weight:800;color:#667085}input{width:100%;padding:13px;border:1px solid #dfe5ef;border-radius:10px;font:inherit}.btn{margin-top:18px;border:0;border-radius:999px;background:#2f68ff;color:#fff;padding:13px 20px;font-weight:900;cursor:pointer}.ok{background:#ecfdf5;color:#065f46;padding:12px;border-radius:10px;margin-bottom:14px}.err{background:#fef2f2;color:#991b1b;padding:12px;border-radius:10px;margin-bottom:14px}.notice{background:#eff6ff;color:#1d4ed8;padding:14px;border-radius:12px;margin:16px 0;font-weight:700}a{color:#2f68ff}.muted{color:#667085;line-height:1.6}.row{display:grid;grid-template-columns:1fr 1fr;gap:14px}@media(max-width:640px){.row{grid-template-columns:1fr}}</style></head><body><main class="wrap"><p><?php if (!$forceChange): ?><a href="<?= e($back) ?>">Back</a><?php endif; ?></p><section class="card"><h1>Account Settings</h1><?php if ($forceChange): ?><div class="notice">Update your password before continuing.</div><?php endif; ?><?php if ($message): ?><div class="ok"><?= e($message) ?></div><?php endif; ?><?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><div class="row"><div><label>Username</label><input name="username" value="<?= e($user['username'] ?? '') ?>" required></div><div><label>Email</label><input type="email" name="email" value="<?= e($user['email']) ?>" required></div></div><label>Full name</label><input name="full_name" value="<?= e($user['full_name']) ?>" required><hr><p class="muted"><?= $forceChange ? 'Password update is required.' : 'Leave password fields blank to keep the current password.' ?></p><label>Current password</label><input type="password" name="current_password" autocomplete="current-password"><div class="row"><div><label>New password</label><input type="password" name="new_password" autocomplete="new-password"></div><div><label>Confirm new password</label><input type="password" name="confirm_password" autocomplete="new-password"></div></div><button class="btn" type="submit">Save Account</button><?php if (!$forceChange): ?> <a href="<?= e($back) ?>">Cancel</a><?php endif; ?></form></section></main></body></html>
