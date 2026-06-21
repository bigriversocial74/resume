<?php
declare(strict_types=1);

function visitor_key_from_request(): string
{
    $raw = (string)($_COOKIE['de_visitor'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $raw)) {
        $raw = bin2hex(random_bytes(32));
        setcookie('de_visitor', $raw, [
            'expires' => time() + 31536000,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }
    return $raw;
}

function session_key_from_request(): string
{
    $raw = (string)($_COOKIE['de_visit_session'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $raw)) {
        $raw = bin2hex(random_bytes(32));
        setcookie('de_visit_session', $raw, [
            'expires' => time() + 1800,
            'path' => '/',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }
    return $raw;
}

function track_visit(array $data): void
{
    db_exec(
        'INSERT INTO website_visits (visitor_key, session_key, page_url, page_title, referrer, user_agent, ip_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
        [
            visitor_key_from_request(),
            session_key_from_request(),
            substr((string)($data['page_url'] ?? ''), 0, 700),
            substr((string)($data['page_title'] ?? ''), 0, 255),
            substr((string)($data['referrer'] ?? ''), 0, 700),
            current_user_agent(),
            client_ip_hash(),
        ]
    );
}

function get_or_create_chat_conversation(?string $name = null, ?string $email = null): int
{
    $visitorKey = visitor_key_from_request();
    $existing = db_one('SELECT id FROM chat_conversations WHERE visitor_key = ? AND status IN ("open", "pending") ORDER BY updated_at DESC LIMIT 1', [$visitorKey]);
    if ($existing) {
        if ($name || $email) {
            db_exec('UPDATE chat_conversations SET name = COALESCE(NULLIF(?, ""), name), email = COALESCE(NULLIF(?, ""), email), updated_at = NOW() WHERE id = ?', [trim((string)$name), normalize_email((string)$email), $existing['id']]);
        }
        return (int)$existing['id'];
    }
    db_exec('INSERT INTO chat_conversations (visitor_key, name, email, status, last_message_at, created_at, updated_at) VALUES (?, ?, ?, "open", NOW(), NOW(), NOW())', [$visitorKey, trim((string)$name), normalize_email((string)$email)]);
    return (int)db()->lastInsertId();
}

function add_chat_message(int $conversationId, string $senderType, string $message, ?int $userId = null, ?array $metadata = null): void
{
    $message = trim($message);
    if ($message === '') {
        return;
    }
    db_exec('INSERT INTO chat_messages (conversation_id, sender_type, sender_user_id, message, metadata, created_at) VALUES (?, ?, ?, ?, ?, NOW())', [$conversationId, $senderType, $userId, $message, $metadata ? json_encode($metadata, JSON_THROW_ON_ERROR) : null]);
    db_exec('UPDATE chat_conversations SET last_message_at = NOW(), updated_at = NOW(), status = IF(status = "closed", "pending", status) WHERE id = ?', [$conversationId]);
}

function list_chat_conversations(int $limit = 80): array
{
    $limit = max(1, min(150, $limit));
    return db_all('SELECT c.*, (SELECT message FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message FROM chat_conversations c ORDER BY COALESCE(c.last_message_at, c.created_at) DESC LIMIT ' . $limit);
}

function read_chat_conversation(int $id): ?array
{
    return db_one('SELECT * FROM chat_conversations WHERE id = ? LIMIT 1', [$id]);
}

function read_chat_messages(int $conversationId): array
{
    return db_all('SELECT m.*, u.full_name AS sender_name FROM chat_messages m LEFT JOIN users u ON u.id = m.sender_user_id WHERE m.conversation_id = ? ORDER BY m.created_at ASC', [$conversationId]);
}

function analytics_summary(): array
{
    return [
        'visits_today' => (int)(db_one('SELECT COUNT(*) AS total FROM website_visits WHERE created_at >= CURDATE()')['total'] ?? 0),
        'unique_today' => (int)(db_one('SELECT COUNT(DISTINCT visitor_key) AS total FROM website_visits WHERE created_at >= CURDATE()')['total'] ?? 0),
        'chats_open' => (int)(db_one('SELECT COUNT(*) AS total FROM chat_conversations WHERE status IN ("open", "pending")')['total'] ?? 0),
        'new_requests' => (int)(db_one('SELECT COUNT(*) AS total FROM project_requests WHERE status = "new"')['total'] ?? 0),
    ];
}
