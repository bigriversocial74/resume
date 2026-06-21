<?php
declare(strict_types=1);

function tavus_config(): array
{
    return app_config('api_keys.tavus') ?: [];
}

function tavus_active_replica_id(): string
{
    $stored = agent_setting('tavus_active_replica_id', '');
    if ($stored !== '') {
        return $stored;
    }
    return (string)(tavus_config()['replica_id'] ?? '');
}

function tavus_ready(): bool
{
    $config = tavus_config();
    return !empty($config['api_key']) && (!empty($config['persona_id']) || tavus_active_replica_id() !== '');
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
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers, CURLOPT_TIMEOUT => 30]);
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

function tavus_public_base_url(): string
{
    $base = rtrim((string)(app_config('app.base_url') ?: ''), '/');
    if ($base === '' || $base === 'https://example.com') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $base = $scheme . '://' . $host;
    }
    return $base;
}

function tavus_media_dir(): string
{
    $dir = dirname(__DIR__) . '/storage/media-profiles';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    return $dir;
}

function tavus_media_url(array $profile): string
{
    if (!empty($profile['file_url'])) {
        return (string)$profile['file_url'];
    }
    if (!empty($profile['file_token'])) {
        return tavus_public_base_url() . '/api/tavus/asset.php?token=' . rawurlencode((string)$profile['file_token']);
    }
    if (!empty($profile['file_path'])) {
        return tavus_public_base_url() . '/' . ltrim((string)$profile['file_path'], '/');
    }
    return '';
}

function tavus_save_media_profile(array $data): int
{
    db_exec(
        'INSERT INTO media_profiles (source_type, display_name, provider, status, file_path, file_url, file_token, mime_type, file_size, option_one, option_two, option_three, is_active, created_by_user_id, created_at, updated_at) VALUES (?, ?, "tavus", "draft", ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, NOW(), NOW())',
        [$data['source_type'], $data['display_name'], $data['file_path'] ?? null, $data['file_url'] ?? null, $data['file_token'] ?? null, $data['mime_type'] ?? null, $data['file_size'] ?? null, $data['voice_name'] ?? null, $data['model_name'] ?? 'phoenix-4', $data['asset_type'] ?? 'image', $data['user_id']]
    );
    return (int)db()->lastInsertId();
}

function tavus_create_hero_profile(int $userId, string $displayName, string $voiceName, string $modelName = 'phoenix-4'): int
{
    return tavus_save_media_profile([
        'source_type' => 'hero',
        'display_name' => trim($displayName) ?: 'Dave Hero Image',
        'file_path' => 'images/dave_main.png',
        'file_url' => tavus_public_base_url() . '/images/dave_main.png',
        'mime_type' => 'image/png',
        'voice_name' => trim($voiceName),
        'model_name' => trim($modelName) ?: 'phoenix-4',
        'asset_type' => 'image',
        'user_id' => $userId,
    ]);
}

function tavus_upload_profile(int $userId, array $file, string $displayName, string $voiceName, string $modelName = 'phoenix-4'): int
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
    }
    $maxBytes = 350 * 1024 * 1024;
    if ((int)$file['size'] > $maxBytes) {
        throw new RuntimeException('File is too large. Max 350MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: (string)($file['type'] ?? 'application/octet-stream');
    $isImage = str_starts_with($mime, 'image/');
    $isVideo = str_starts_with($mime, 'video/');
    if (!$isImage && !$isVideo) {
        throw new RuntimeException('Upload must be an image or video file.');
    }
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string)$file['name']);
    $token = bin2hex(random_bytes(32));
    $storedName = date('Ymd_His') . '_' . $token . '_' . $safeName;
    $storedPath = tavus_media_dir() . '/' . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
        throw new RuntimeException('Could not save upload.');
    }
    return tavus_save_media_profile([
        'source_type' => $isImage ? 'upload' : 'upload',
        'display_name' => trim($displayName) ?: pathinfo($safeName, PATHINFO_FILENAME),
        'file_path' => 'storage/media-profiles/' . $storedName,
        'file_token' => $token,
        'mime_type' => $mime,
        'file_size' => (int)$file['size'],
        'voice_name' => trim($voiceName),
        'model_name' => trim($modelName) ?: 'phoenix-4',
        'asset_type' => $isImage ? 'image' : 'video',
        'user_id' => $userId,
    ]);
}

function tavus_create_replica_from_profile(int $profileId, int $userId): array
{
    $profile = db_one('SELECT * FROM media_profiles WHERE id = ? AND provider = "tavus" LIMIT 1', [$profileId]);
    if (!$profile) {
        throw new RuntimeException('Media profile not found.');
    }
    $sourceUrl = tavus_media_url($profile);
    if ($sourceUrl === '') {
        throw new RuntimeException('Media profile does not have a public URL.');
    }
    $voiceName = trim((string)($profile['option_one'] ?? ''));
    if ($voiceName === '' && ($profile['option_three'] ?? 'image') === 'image') {
        throw new RuntimeException('Voice name is required for image-based replica creation.');
    }
    $payload = [
        'replica_name' => $profile['display_name'],
        'callback_url' => tavus_public_base_url() . '/api/tavus/replica-callback.php',
        'model_name' => $profile['option_two'] ?: 'phoenix-4',
    ];
    if (($profile['option_three'] ?? 'image') === 'video') {
        $payload['train_video_url'] = $sourceUrl;
    } else {
        $payload['train_image_url'] = $sourceUrl;
        $payload['voice_name'] = $voiceName;
        $payload['auto_fix_training_image'] = true;
    }
    $data = tavus_http_json('POST', '/replicas', $payload);
    $replicaId = (string)($data['replica_id'] ?? $data['replicaId'] ?? '');
    db_exec('UPDATE media_profiles SET provider_item_id = ?, status = ?, provider_response = ?, is_active = 1, updated_at = NOW() WHERE id = ?', [$replicaId, (string)($data['status'] ?? 'started'), json_encode($data, JSON_THROW_ON_ERROR), $profileId]);
    if ($replicaId !== '') {
        set_agent_setting('tavus_active_replica_id', $replicaId, $userId);
    }
    return $data;
}

function tavus_list_media_profiles(): array
{
    return db_all('SELECT mp.*, u.full_name AS created_by FROM media_profiles mp JOIN users u ON u.id = mp.created_by_user_id WHERE mp.provider = "tavus" ORDER BY mp.created_at DESC LIMIT 100');
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
        throw new RuntimeException('Tavus is not configured. Add TAVUS_API_KEY and TAVUS_PERSONA_ID or an active replica id.');
    }
    $payload = ['conversation_name' => 'Dave AI Hero Chat - ' . date('Y-m-d H:i:s'), 'test_mode' => tavus_test_mode(), 'conversational_context' => tavus_conversation_context()];
    if (!empty($config['persona_id'])) {
        $payload['persona_id'] = $config['persona_id'];
    }
    $activeReplica = tavus_active_replica_id();
    if ($activeReplica !== '') {
        $payload['replica_id'] = $activeReplica;
    }
    $data = tavus_http_json('POST', '/conversations', $payload);
    $conversationId = (string)($data['conversation_id'] ?? '');
    db_exec('INSERT INTO video_conversations (visitor_key, provider, provider_conversation_id, conversation_url, persona_id, replica_id, status, test_mode, started_at, metadata, created_at, updated_at) VALUES (?, "tavus", ?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), NOW())', [visitor_key_from_request(), $conversationId, (string)($data['conversation_url'] ?? ''), (string)($payload['persona_id'] ?? ''), (string)($payload['replica_id'] ?? ''), (string)($data['status'] ?? 'created'), tavus_test_mode() ? 1 : 0, json_encode($data, JSON_THROW_ON_ERROR)]);
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
