<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');
try {
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $conversationId = (int)($payload['conversation_id'] ?? 0);
    if ($conversationId <= 0) {
        $conversationId = get_or_create_chat_conversation(null, null, ['page_url' => (string)($payload['page_url'] ?? ''), 'referrer' => (string)($payload['referrer'] ?? '')]);
    }
    $message = trim((string)($payload['message'] ?? ''));
    if ($message === '') {
        throw new RuntimeException('Message is required.');
    }

    add_chat_message($conversationId, 'visitor', $message, null, ['page_url' => (string)($payload['page_url'] ?? '')]);

    if (chat_automation_enabled()) {
        $matches = knowledge_search($message, 5);
        $aiReply = ai_generate_knowledge_reply($message, $matches);
        $reply = $aiReply ?: knowledge_agent_reply($message);
        add_chat_message($conversationId, 'system', $reply, null, ['automation' => true, 'knowledge_base' => true, 'model_provider' => ai_active_provider(), 'ai_used' => $aiReply !== null, 'source_count' => count($matches)]);
    } elseif (should_send_human_fallback($conversationId)) {
        add_chat_message($conversationId, 'system', 'Thanks — your message was received. Dave will respond from the chat dashboard.', null, ['automation' => false, 'one_time_fallback' => true]);
        mark_human_fallback_sent($conversationId);
    }

    echo json_encode(['ok' => true, 'conversation_id' => $conversationId, 'messages' => read_chat_messages($conversationId)]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Message could not be sent.']);
}
