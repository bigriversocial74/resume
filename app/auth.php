<?php
declare(strict_types=1);

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $user = db_one('SELECT id, email, username, full_name, role, status, must_change_password FROM users WHERE id = ? LIMIT 1', [$_SESSION['user_id']]);
    if (!$user || $user['status'] !== 'active') {
        logout_user();
        return null;
    }
    return $user;
}

function current_path(): string
{
    $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $base = function_exists('app_base_path') ? app_base_path() : '';
    if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
        $path = substr($path, strlen($base));
    }
    return $path !== '' ? $path : '/';
}

function must_change_password_allowed_path(): bool
{
    $path = current_path();
    return in_array($path, ['/admin/account.php', '/admin/logout.php'], true);
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect_to('/admin/login.php');
    }
    if ((int)$user['must_change_password'] === 1 && !must_change_password_allowed_path()) {
        redirect_to('/admin/account.php?first_login=1');
    }
    return $user;
}

function require_role(string $role): array
{
    $user = require_login();
    if ($user['role'] !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
    return $user;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role'] = $user['role'];
    db_exec(
        'UPDATE users SET last_login_at = NOW(), last_login_ip_hash = ?, failed_login_count = 0, locked_until = NULL WHERE id = ?',
        [client_ip_hash(), $user['id']]
    );
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function authenticate(string $email, string $password): bool
{
    $login = normalize_email($email);
    $user = db_one('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1', [$login, $login]);
    if (!$user || $user['status'] !== 'active') {
        usleep(250000);
        return false;
    }

    if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        $attempts = (int)$user['failed_login_count'] + 1;
        $max = (int)(app_config('security.login_max_attempts') ?? 8);
        $decay = (int)(app_config('security.login_decay_minutes') ?? 20);
        $lockedUntil = $attempts >= $max ? date('Y-m-d H:i:s', time() + ($decay * 60)) : null;
        db_exec('UPDATE users SET failed_login_count = ?, locked_until = ? WHERE id = ?', [$attempts, $lockedUntil, $user['id']]);
        return false;
    }

    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
        db_exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }

    login_user($user);
    return true;
}

function create_user_by_admin(int $adminId, string $fullName, string $email, string $role = 'customer'): array
{
    if (!in_array($role, ['admin', 'customer'], true)) {
        throw new InvalidArgumentException('Invalid role.');
    }
    $email = normalize_email($email);
    if (!valid_email($email)) {
        throw new InvalidArgumentException('Invalid email.');
    }
    $baseUsername = preg_replace('/[^a-z0-9_]/', '', str_replace(['.', '-'], '_', strstr($email, '@', true))) ?: 'customer';
    $username = $baseUsername;
    $suffix = 1;
    while (db_one('SELECT id FROM users WHERE username = ? LIMIT 1', [$username])) {
        $username = $baseUsername . $suffix;
        $suffix++;
    }
    $password = random_temp_password();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare('INSERT INTO users (email, username, password_hash, full_name, role, status, must_change_password, created_by_user_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, "active", 1, ?, NOW(), NOW())');
    $stmt->execute([$email, $username, $hash, trim($fullName), $role, $adminId]);
    return [
        'id' => (int)db()->lastInsertId(),
        'email' => $email,
        'username' => $username,
        'temporary_password' => $password,
        'role' => $role,
    ];
}
