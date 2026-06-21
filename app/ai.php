<?php
declare(strict_types=1);

function ai_provider_config(string $provider): array
{
    $providers = app_config('agent.providers') ?: [];
    return is_array($providers[$provider] ?? null) ? $providers[$provider] : [];
}

function ai_active_provider(): string
{
    $stored = agent_setting('agent_model_provider', '');
    $default = (string)(app_config('agent.default_provider') ?: 'openai');
    return $stored !== '' ? $stored : $default;
}

function ai_provider_ready(string $provider): bool
{
    $config = ai_provider_config($provider);
    return !empty($config['api_key']) && !empty($config['model']) && !empty($config['endpoint']);
}

function ai_provider_status(): array
{
    $providers = app_config('agent.providers') ?: [];
    $out = [];
    foreach ($providers as $key => $config) {
        $out[$key] = [
            'label' => $config['label'] ?? $key,
            'ready' => ai_provider_ready($key),
            'model' => $config['model'] ?? '',
            'endpoint' => $config['endpoint'] ?? '',
        ];
    }
    return $out;
}

function ai_agent_system_prompt(): string
{
    return agent_setting('agent_system_prompt', 'You are the website chat agent for David Evans. Answer using only the provided knowledge base context. Be concise, helpful, and honest. If the answer is not in the context, say you do not have that answer yet and offer to route the question to Dave.');
}

function ai_context_from_matches(array $matches): string
{
    if (!$matches) {
        return 'No knowledge base context matched this question.';
    }
    $blocks = [];
    foreach ($matches as $index => $match) {
        $title = trim((string)($match['source_title'] ?? $match['chunk_title'] ?? 'Knowledge Source'));
        $url = trim((string)($match['source_url'] ?? ''));
        $text = trim((string)($match['plain_text'] ?? ''));
        $blocks[] = "SOURCE " . ($index + 1) . ": " . $title . ($url ? "\nURL: " . $url : '') . "\n" . mb_strimwidth($text, 0, 2400, '...');
    }
    return implode("\n\n---\n\n", $blocks);
}

function ai_http_json(string $endpoint, array $headers, array $payload, int $timeout = 24): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL is required for AI provider calls.');
    }
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
        CURLOPT_TIMEOUT => $timeout,
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false || $raw === '') {
        throw new RuntimeException('AI provider request failed: ' . $err);
    }
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        throw new RuntimeException('AI provider returned invalid JSON.');
    }
    if ($status < 200 || $status >= 300) {
        $message = $json['error']['message'] ?? $json['message'] ?? 'AI provider returned HTTP ' . $status;
        throw new RuntimeException((string)$message);
    }
    return $json;
}

function ai_openai_compatible_reply(string $provider, string $system, string $question, string $context): string
{
    $config = ai_provider_config($provider);
    $timeout = (int)(app_config('agent.timeout_seconds') ?: 24);
    $payload = [
        'model' => $config['model'],
        'temperature' => (float)(app_config('agent.temperature') ?? 0.2),
        'max_tokens' => (int)(app_config('agent.max_tokens') ?: 700),
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "Knowledge base context:\n" . $context . "\n\nVisitor question:\n" . $question],
        ],
    ];
    $json = ai_http_json($config['endpoint'], [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $config['api_key'],
    ], $payload, $timeout);
    return trim((string)($json['choices'][0]['message']['content'] ?? ''));
}

function ai_claude_reply(string $system, string $question, string $context): string
{
    $config = ai_provider_config('claude');
    $timeout = (int)(app_config('agent.timeout_seconds') ?: 24);
    $payload = [
        'model' => $config['model'],
        'max_tokens' => (int)(app_config('agent.max_tokens') ?: 700),
        'temperature' => (float)(app_config('agent.temperature') ?? 0.2),
        'system' => $system,
        'messages' => [
            ['role' => 'user', 'content' => "Knowledge base context:\n" . $context . "\n\nVisitor question:\n" . $question],
        ],
    ];
    $json = ai_http_json($config['endpoint'], [
        'Content-Type: application/json',
        'x-api-key: ' . $config['api_key'],
        'anthropic-version: ' . ($config['version'] ?? '2023-06-01'),
    ], $payload, $timeout);
    return trim((string)($json['content'][0]['text'] ?? ''));
}

function ai_gemini_reply(string $system, string $question, string $context): string
{
    $config = ai_provider_config('gemini');
    $timeout = (int)(app_config('agent.timeout_seconds') ?: 24);
    $endpoint = str_replace('{model}', rawurlencode((string)$config['model']), (string)$config['endpoint']);
    $separator = str_contains($endpoint, '?') ? '&' : '?';
    $endpoint .= $separator . 'key=' . rawurlencode((string)$config['api_key']);
    $payload = [
        'systemInstruction' => ['parts' => [['text' => $system]]],
        'generationConfig' => [
            'temperature' => (float)(app_config('agent.temperature') ?? 0.2),
            'maxOutputTokens' => (int)(app_config('agent.max_tokens') ?: 700),
        ],
        'contents' => [[
            'role' => 'user',
            'parts' => [['text' => "Knowledge base context:\n" . $context . "\n\nVisitor question:\n" . $question]],
        ]],
    ];
    $json = ai_http_json($endpoint, ['Content-Type: application/json'], $payload, $timeout);
    return trim((string)($json['candidates'][0]['content']['parts'][0]['text'] ?? ''));
}

function ai_generate_knowledge_reply(string $question, array $matches): ?string
{
    $provider = ai_active_provider();
    if (!ai_provider_ready($provider)) {
        return null;
    }
    $system = ai_agent_system_prompt();
    $context = ai_context_from_matches($matches);
    try {
        $reply = match ($provider) {
            'openai' => ai_openai_compatible_reply('openai', $system, $question, $context),
            'kimi' => ai_openai_compatible_reply('kimi', $system, $question, $context),
            'claude' => ai_claude_reply($system, $question, $context),
            'gemini' => ai_gemini_reply($system, $question, $context),
            default => '',
        };
        return $reply !== '' ? $reply : null;
    } catch (Throwable $e) {
        error_log('AI agent reply failed: ' . $e->getMessage());
        return null;
    }
}
