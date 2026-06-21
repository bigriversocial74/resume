<?php
/**
 * Copy this file to app/config.php and edit values for your server.
 * Never commit app/config.php with real credentials.
 */
function de_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

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
    'agent' => [
        'default_provider' => de_env('AGENT_PROVIDER', 'openai'),
        'temperature' => 0.2,
        'max_tokens' => 700,
        'timeout_seconds' => 24,
        'providers' => [
            'openai' => [
                'label' => 'OpenAI',
                'api_key' => de_env('OPENAI_API_KEY'),
                'model' => de_env('OPENAI_MODEL'),
                'endpoint' => de_env('OPENAI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
            ],
            'claude' => [
                'label' => 'Claude',
                'api_key' => de_env('ANTHROPIC_API_KEY'),
                'model' => de_env('ANTHROPIC_MODEL'),
                'endpoint' => de_env('ANTHROPIC_ENDPOINT', 'https://api.anthropic.com/v1/messages'),
                'version' => de_env('ANTHROPIC_VERSION', '2023-06-01'),
            ],
            'gemini' => [
                'label' => 'Gemini',
                'api_key' => de_env('GEMINI_API_KEY'),
                'model' => de_env('GEMINI_MODEL'),
                'endpoint' => de_env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent'),
            ],
            'kimi' => [
                'label' => 'Kimi',
                'api_key' => de_env('KIMI_API_KEY'),
                'model' => de_env('KIMI_MODEL'),
                'endpoint' => de_env('KIMI_ENDPOINT', 'https://api.moonshot.ai/v1/chat/completions'),
            ],
        ],
    ],
    'api_keys' => [
        'liveavatar' => [
            'label' => 'LiveAvatar',
            'api_key' => de_env('LIVEAVATAR_API_KEY'),
            'endpoint' => de_env('LIVEAVATAR_ENDPOINT'),
            'project_id' => de_env('LIVEAVATAR_PROJECT_ID'),
        ],
    ],
];
