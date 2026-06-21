<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');
try {
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $conversationId = get_or_create_chat_conversation((string)($payload['name'] ?? ''), (string)($payload['email'] ?? ''));
    $messages = read_chat_messages($conversationId);
    if (!$messages) {
        add_chat_message($conversationId, 'system', 'Hi, I am David\'s project assistant. Tell me what you are trying to build and I will help route the request.');
        $messages = read_chat_messages($conversationId);
    }
    echo json_encode(['ok' => true, 'conversation_id' => $conversationId, 'messages' => $messages]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
