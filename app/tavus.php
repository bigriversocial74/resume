<?php
declare(strict_types=1);

function tavus_config(): array
{
    return app_config('api_keys.tavus') ?: [];
}

function tavus_ready(): bool
{
    $config = tavus_config();
    return !empty($config['api_key']) && (!empty($config['persona_id']) || !empty($config['replica_id']));
}

function tavus_test_mode(): bool
{
    $setting = agent_setting('tavus_test_mode', '');
    if ($setting !== '') {
        return $setting === '1';
    }
    return (string)(tavus_config()['test_mode'] ?? '1') === '1';
}

function tavus_http_json(string $method, string $path, ?array $payload = null): array
{
    $config = tavus_config();
    if (empty($config['api_key'])) {
        throw new RuntimeException('Missing Tavus API key.');
    }
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL is required for Tavus calls.');
    }
    $endpoint = rtrim((string)($config['endpoint'] ?? 'https://tavusapi.com/v2'), '/') . $path;
    $headers = ['x-api-key: ' . $config['api_key']];
    if ($payload !== null) {
        $headers[] = 'Content-Type: application/json';
    }
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 24,
    ]);
    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
    }
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        throw new RuntimeException('Tavus request failed: ' . $err);
    }
    if ($status === 204) {
        return ['ok' => true, 'status_code' => 204];
    }
    $json = json_decode((string)$raw, true);
    if (!is_array($json)) {
        throw new RuntimeException('Tavus returned invalid JSON.');
    }
    if ($status < 200 || $status >= 300) {
        $message = $json['error'] ?? $json['message'] ?? 'Tavus returned HTTP ' . $status;
        throw new RuntimeException(is_string($message) ? $message : 'Tavus request failed.');
    }
    return $json;
}

function tavus_conversation_context(): string
{
    $summary = analytics_summary();
    $base = "Visitor is on David Evans' portfolio site. Dave offers AI-integrated websites, landing pages, social content systems, agentic workflows, CRM dashboards, and consulting.";
    $base .= " Current CRM stats: visits today " . $summary['visits_today'] . ", open chats " . $summary['chats_open'] . ", new project requests " . $summary['new_requests'] . ".";
    return $base;
}

function tavus_start_conversation(): array
{
    $config = tavus_config();
    if (!tavus_ready()) {
        throw new RuntimeException('Tavus is not configured. Add TAVUS_API_KEY and TAVUS_PERSONA_ID or TAVUS_REPLICA_ID.');
    }
    $payload = [
        'conversation_name' => 'Dave AI Hero Chat - ' . date('Y-m-d H:i:s'),
        'test_mode' => tavus_test_mode(),
        'conversational_context' => tavus_conversation_context(),
    ];
    if (!empty($config['persona_id'])) {
        $payload['persona_id'] = $config['persona_id'];
    }
    if (!empty($config['replica_id'])) {
        $payload['replica_id'] = $config['replica_id'];
    }
    $data = tavus_http_json('POST', '/conversations', $payload);
    $conversationId = (string)($data['conversation_id'] ?? '');
    db_exec(
        'INSERT INTO video_conversations (visitor_key, provider, provider_conversation_id, conversation_url, persona_id, replica_id, status, test_mode, started_at, metadata, created_at, updated_at) VALUES (?, "tavus", ?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), NOW())',
        [
            visitor_key_from_request(),
            $conversationId,
            (string)($data['conversation_url'] ?? ''),
            (string)($payload['persona_id'] ?? ''),
            (string)($payload['replica_id'] ?? ''),
            (string)($data['status'] ?? 'created'),
            tavus_test_mode() ? 1 : 0,
            json_encode($data, JSON_THROW_ON_ERROR),
        ]
    );
    return $data;
}

function tavus_end_conversation(string $conversationId): void
{
    if ($conversationId === '') {
        return;
    }
    try {
        tavus_http_json('POST', '/conversations/' . rawurlencode($conversationId) . '/end');
    } finally {
        db_exec('UPDATE video_conversations SET status = "ended", ended_at = NOW(), updated_at = NOW() WHERE provider = "tavus" AND provider_conversation_id = ?', [$conversationId]);
    }
}
