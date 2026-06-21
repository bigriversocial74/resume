<?php
/**
 * CLI only. Usage:
 *   ADMIN_EMAIL=dave@example.com ADMIN_NAME="David Evans" ADMIN_PASS="StrongPass123!" php scripts/create_admin.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../app/bootstrap.php';
$email = normalize_email((string)getenv('ADMIN_EMAIL'));
$name = trim((string)(getenv('ADMIN_NAME') ?: 'Admin'));
$pass = (string)getenv('ADMIN_PASS');
if (!valid_email($email)) {
    fwrite(STDERR, "ADMIN_EMAIL is required and must be valid.\n");
    exit(1);
}
if (!password_meets_policy($pass)) {
    fwrite(STDERR, "ADMIN_PASS must be at least 14 chars with uppercase, lowercase, number, and symbol.\n");
    exit(1);
}
$exists = db_one('SELECT id FROM users WHERE email = ? LIMIT 1', [$email]);
if ($exists) {
    fwrite(STDERR, "User already exists.\n");
    exit(1);
}
db_exec('INSERT INTO users (email, password_hash, full_name, role, status, must_change_password, created_at, updated_at) VALUES (?, ?, ?, "admin", "active", 0, NOW(), NOW())', [$email, password_hash($pass, PASSWORD_DEFAULT), $name]);
echo "Admin created.\n";
