<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');
try {
    $conversationId = (int)($_GET['conversation_id'] ?? 0);
    if ($conversationId <= 0) {
        throw new RuntimeException('Missing conversation.');
    }
    $conversation = read_chat_conversation($conversationId);
    if (!$conversation || !hash_equals($conversation['visitor_key'], visitor_key_from_request())) {
        http_response_code(403);
        echo json_encode(['ok' => false]);
        exit;
    }
    echo json_encode(['ok' => true, 'messages' => read_chat_messages($conversationId)]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
}
