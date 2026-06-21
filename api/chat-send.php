<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');
try {
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $conversationId = (int)($payload['conversation_id'] ?? 0);
    if ($conversationId <= 0) {
        $conversationId = get_or_create_chat_conversation();
    }
    $message = trim((string)($payload['message'] ?? ''));
    if ($message === '') {
        throw new RuntimeException('Message is required.');
    }

    add_chat_message($conversationId, 'visitor', $message, null, ['page_url' => (string)($payload['page_url'] ?? '')]);

    if (chat_automation_enabled()) {
        $reply = knowledge_agent_reply($message);
        add_chat_message($conversationId, 'system', $reply, null, ['automation' => true, 'knowledge_base' => true]);
    } else {
        add_chat_message($conversationId, 'system', 'Thanks — your message was received. Dave will respond from the chat dashboard.', null, ['automation' => false]);
    }

    echo json_encode(['ok' => true, 'conversation_id' => $conversationId, 'messages' => read_chat_messages($conversationId)]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Message could not be sent.']);
}
