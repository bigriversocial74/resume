<?php
/**
 * Copy this file to app/config.php and edit values for your server.
 * Never commit app/config.php with real credentials.
 */
return [
    'app' => [
        'name' => 'David Evans CRM',
        'base_url' => 'https://example.com',
        'env' => 'production',
        'debug' => false,
    ],
    'database' => [
        'dsn' => 'mysql:host=localhost;dbname=resume_crm;charset=utf8mb4',
        'username' => 'resume_crm_user',
        'password' => 'change-me',
    ],
    'security' => [
        'session_name' => 'de_crm_session',
        'csrf_key' => 'replace-with-a-long-random-secret-64-chars-minimum',
        'password_min_length' => 14,
        'login_max_attempts' => 8,
        'login_decay_minutes' => 20,
    ],
];
