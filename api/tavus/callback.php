<?php
require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json');
try {
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $conversationId = (string)($payload['conversation_id'] ?? $payload['conversationId'] ?? '');
    $status = (string)($payload['status'] ?? '');
    if ($conversationId !== '') {
        $mapped = in_array($status, ['created', 'active', 'ended', 'failed'], true) ? $status : null;
        if ($mapped) {
            db_exec('UPDATE video_conversations SET status = ?, ended_at = IF(? = "ended", NOW(), ended_at), metadata = ?, updated_at = NOW() WHERE provider = "tavus" AND provider_conversation_id = ?', [$mapped, $mapped, json_encode($payload, JSON_THROW_ON_ERROR), $conversationId]);
        } else {
            db_exec('UPDATE video_conversations SET metadata = ?, updated_at = NOW() WHERE provider = "tavus" AND provider_conversation_id = ?', [json_encode($payload, JSON_THROW_ON_ERROR), $conversationId]);
        }
    }
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
}
