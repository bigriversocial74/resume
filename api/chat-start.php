<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');
try {
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $conversationId = get_or_create_chat_conversation((string)($payload['name'] ?? ''), (string)($payload['email'] ?? ''), ['page_url' => (string)($payload['page_url'] ?? ''), 'referrer' => (string)($payload['referrer'] ?? '')]);
    $messages = read_chat_messages($conversationId);
    if (!$messages) {
        add_chat_message($conversationId, 'system', 'Hi, I am David\'s project assistant. Tell me what you are trying to build and I will help route the request.');
        $messages = read_chat_messages($conversationId);
    }
    mark_chat_read_for_visitor($conversationId);
    echo json_encode(['ok' => true, 'conversation_id' => $conversationId, 'messages' => $messages, 'unread' => 0]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
