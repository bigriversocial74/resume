<?php
require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json');
try {
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $conversationId = trim((string)($payload['conversation_id'] ?? ''));
    if ($conversationId === '') {
        throw new RuntimeException('Missing conversation id.');
    }
    tavus_end_conversation($conversationId);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
