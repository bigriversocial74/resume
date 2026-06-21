<?php
declare(strict_types=1);

function visitor_key_from_request(): string
{
    $raw = (string)($_COOKIE['de_visitor'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $raw)) {
        $raw = bin2hex(random_bytes(32));
        setcookie('de_visitor', ['value' => $raw, 'expires' => time() + 31536000, 'path' => '/', 'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), 'httponly' => false, 'samesite' => 'Lax']);
    }
    return $raw;
}

function session_key_from_request(): string
{
    $raw = (string)($_COOKIE['de_visit_session'] ?? '');
    if (!preg_match('/^[a-f0-9]{64}$/', $raw)) {
        $raw = bin2hex(random_bytes(32));
        setcookie('de_visit_session', ['value' => $raw, 'expires' => time() + 1800, 'path' => '/', 'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), 'httponly' => false, 'samesite' => 'Lax']);
    }
    return $raw;
}

function track_visit(array $data): void
{
    db_exec(
        'INSERT INTO website_visits (visitor_key, session_key, page_url, page_title, referrer, user_agent, ip_hash, started_at, last_ping_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW())',
        [visitor_key_from_request(), session_key_from_request(), substr((string)($data['page_url'] ?? ''), 0, 700), substr((string)($data['page_title'] ?? ''), 0, 255), substr((string)($data['referrer'] ?? ''), 0, 700), current_user_agent(), client_ip_hash()]
    );
}

function track_visit_heartbeat(array $data): void
{
    $seconds = max(0, min(86400, (int)($data['time_on_page_seconds'] ?? 0)));
    db_exec(
        'UPDATE website_visits SET last_ping_at = NOW(), time_on_page_seconds = GREATEST(time_on_page_seconds, ?), ended_at = IF(? > 0, NOW(), ended_at) WHERE visitor_key = ? AND session_key = ? AND page_url = ? ORDER BY id DESC LIMIT 1',
        [$seconds, !empty($data['ended']) ? 1 : 0, visitor_key_from_request(), session_key_from_request(), substr((string)($data['page_url'] ?? ''), 0, 700)]
    );
    db_exec('UPDATE chat_conversations SET total_time_on_site_seconds = GREATEST(total_time_on_site_seconds, ?), last_seen_at = NOW(), last_page_url = ? WHERE visitor_key = ? AND status IN ("open", "pending")', [$seconds, substr((string)($data['page_url'] ?? ''), 0, 700), visitor_key_from_request()]);
}

function get_or_create_chat_conversation(?string $name = null, ?string $email = null, array $context = []): int
{
    $visitorKey = visitor_key_from_request();
    $pageUrl = substr((string)($context['page_url'] ?? ''), 0, 700);
    $referrer = substr((string)($context['referrer'] ?? ''), 0, 700);
    $existing = db_one('SELECT id FROM chat_conversations WHERE visitor_key = ? AND status IN ("open", "pending") ORDER BY updated_at DESC LIMIT 1', [$visitorKey]);
    if ($existing) {
        db_exec('UPDATE chat_conversations SET name = COALESCE(NULLIF(?, ""), name), email = COALESCE(NULLIF(?, ""), email), ip_hash = COALESCE(ip_hash, ?), user_agent = COALESCE(user_agent, ?), last_page_url = COALESCE(NULLIF(?, ""), last_page_url), last_seen_at = NOW(), updated_at = NOW() WHERE id = ?', [trim((string)$name), normalize_email((string)$email), client_ip_hash(), current_user_agent(), $pageUrl, $existing['id']]);
        return (int)$existing['id'];
    }
    db_exec('INSERT INTO chat_conversations (visitor_key, name, email, status, ip_hash, user_agent, first_page_url, last_page_url, referrer, last_seen_at, last_message_at, created_at, updated_at) VALUES (?, ?, ?, "open", ?, ?, ?, ?, ?, NOW(), NOW(), NOW(), NOW())', [$visitorKey, trim((string)$name), normalize_email((string)$email), client_ip_hash(), current_user_agent(), $pageUrl, $pageUrl, $referrer]);
    return (int)db()->lastInsertId();
}

function add_chat_message(int $conversationId, string $senderType, string $message, ?int $userId = null, ?array $metadata = null): void
{
    $message = trim($message);
    if ($message === '') {
        return;
    }
    db_exec('INSERT INTO chat_messages (conversation_id, sender_type, sender_user_id, message, metadata, created_at) VALUES (?, ?, ?, ?, ?, NOW())', [$conversationId, $senderType, $userId, $message, $metadata ? json_encode($metadata, JSON_THROW_ON_ERROR) : null]);
    if ($senderType === 'visitor') {
        db_exec('UPDATE chat_conversations SET last_message_at = NOW(), last_visitor_message_at = NOW(), unread_admin_count = unread_admin_count + 1, status = IF(status = "closed", "pending", status), updated_at = NOW() WHERE id = ?', [$conversationId]);
    } elseif ($senderType === 'admin') {
        db_exec('UPDATE chat_conversations SET last_message_at = NOW(), last_admin_message_at = NOW(), unread_visitor_count = unread_visitor_count + 1, assigned_to_user_id = COALESCE(assigned_to_user_id, ?), accepted_at = COALESCE(accepted_at, NOW()), status = "pending", updated_at = NOW() WHERE id = ?', [$userId, $conversationId]);
    } else {
        db_exec('UPDATE chat_conversations SET last_message_at = NOW(), updated_at = NOW() WHERE id = ?', [$conversationId]);
    }
}

function mark_chat_read_for_admin(int $conversationId): void
{
    db_exec('UPDATE chat_conversations SET unread_admin_count = 0, admin_last_read_at = NOW(), updated_at = NOW() WHERE id = ?', [$conversationId]);
    db_exec('UPDATE chat_messages SET seen_by_admin_at = COALESCE(seen_by_admin_at, NOW()) WHERE conversation_id = ? AND sender_type = "visitor"', [$conversationId]);
}

function mark_chat_read_for_visitor(int $conversationId): void
{
    db_exec('UPDATE chat_conversations SET unread_visitor_count = 0, visitor_last_read_at = NOW(), visitor_notified_at = NOW(), last_seen_at = NOW(), updated_at = NOW() WHERE id = ?', [$conversationId]);
    db_exec('UPDATE chat_messages SET seen_by_visitor_at = COALESCE(seen_by_visitor_at, NOW()) WHERE conversation_id = ? AND sender_type IN ("admin", "system")', [$conversationId]);
}

function should_send_human_fallback(int $conversationId): bool
{
    $row = db_one('SELECT fallback_sent_at, last_admin_message_at FROM chat_conversations WHERE id = ? LIMIT 1', [$conversationId]);
    return $row && empty($row['fallback_sent_at']) && empty($row['last_admin_message_at']);
}

function mark_human_fallback_sent(int $conversationId): void
{
    db_exec('UPDATE chat_conversations SET fallback_sent_at = NOW(), updated_at = NOW() WHERE id = ?', [$conversationId]);
}

function list_chat_conversations(int $limit = 80): array
{
    $limit = max(1, min(150, $limit));
    return db_all('SELECT c.*, (SELECT message FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message FROM chat_conversations c ORDER BY COALESCE(c.last_message_at, c.created_at) DESC LIMIT ' . $limit);
}

function list_incoming_chat_notifications(int $limit = 8): array
{
    $limit = max(1, min(20, $limit));
    return db_all('SELECT c.*, (SELECT message FROM chat_messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message FROM chat_conversations c WHERE c.status IN ("open", "pending") AND c.unread_admin_count > 0 ORDER BY c.last_visitor_message_at DESC LIMIT ' . $limit);
}

function unread_admin_chat_count(): int
{
    return (int)(db_one('SELECT COALESCE(SUM(unread_admin_count), 0) AS total FROM chat_conversations WHERE status IN ("open", "pending")')['total'] ?? 0);
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
        'chat_unread' => unread_admin_chat_count(),
        'avg_time_on_site' => (int)(db_one('SELECT COALESCE(AVG(time_on_page_seconds), 0) AS avg_time FROM website_visits WHERE created_at >= CURDATE()')['avg_time'] ?? 0),
    ];
}
