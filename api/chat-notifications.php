<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Content-Type: application/json');
try {
    require_role('admin');
    $rows = list_incoming_chat_notifications(8);
    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'id' => (int)$row['id'],
            'label' => $row['name'] ?: $row['email'] ?: 'Visitor #' . $row['id'],
            'last_message' => mb_strimwidth((string)($row['last_message'] ?: ''), 0, 120, '...'),
            'unread' => (int)$row['unread_admin_count'],
            'last_at' => $row['last_visitor_message_at'] ?: $row['last_message_at'] ?: $row['created_at'],
            'url' => '/admin/chat-thread.php?id=' . (int)$row['id'],
        ];
    }
    echo json_encode(['ok' => true, 'count' => unread_admin_chat_count(), 'items' => $items]);
} catch (Throwable $e) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
}
