<?php
require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json');
try {
    if (agent_setting('tavus_video_enabled', '1') !== '1') {
        throw new RuntimeException('Video chat is disabled.');
    }
    $conversation = tavus_start_conversation();
    echo json_encode([
        'ok' => true,
        'conversation_id' => $conversation['conversation_id'] ?? null,
        'conversation_url' => $conversation['conversation_url'] ?? null,
        'status' => $conversation['status'] ?? null,
        'test_mode' => tavus_test_mode(),
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
