<?php
declare(strict_types=1);

function create_project_request(array $data): int
{
    $email = normalize_email((string)($data['email'] ?? ''));
    if (!valid_email($email)) {
        throw new InvalidArgumentException('A valid email is required.');
    }

    $stmt = db()->prepare('INSERT INTO project_requests (full_name, email, phone, company, project_types, services, primary_goal, budget_range, target_timeline, website_url, brand_assets_url, social_links, notes, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "new", NOW(), NOW())');
    $stmt->execute([
        trim((string)($data['full_name'] ?? '')),
        $email,
        trim((string)($data['phone'] ?? '')),
        trim((string)($data['company'] ?? '')),
        json_encode(array_values($data['project_types'] ?? []), JSON_THROW_ON_ERROR),
        json_encode(array_values($data['services'] ?? []), JSON_THROW_ON_ERROR),
        trim((string)($data['primary_goal'] ?? '')),
        trim((string)($data['budget_range'] ?? '')),
        trim((string)($data['target_timeline'] ?? '')),
        trim((string)($data['website_url'] ?? '')),
        trim((string)($data['brand_assets_url'] ?? '')),
        trim((string)($data['social_links'] ?? '')),
        trim((string)($data['notes'] ?? '')),
    ]);

    return (int)db()->lastInsertId();
}

function list_project_requests(int $limit = 50): array
{
    $limit = max(1, min(100, $limit));
    return db_all('SELECT * FROM project_requests ORDER BY created_at DESC LIMIT ' . $limit);
}

function read_project_request(int $id): ?array
{
    return db_one('SELECT * FROM project_requests WHERE id = ? LIMIT 1', [$id]);
}

function update_project_request_status(int $id, string $status, int $adminId): void
{
    $allowed = ['new', 'reviewing', 'qualified', 'proposal_sent', 'active', 'closed_won', 'closed_lost', 'archived'];
    if (!in_array($status, $allowed, true)) {
        throw new InvalidArgumentException('Invalid status.');
    }
    db_exec('UPDATE project_requests SET status = ?, updated_by_user_id = ?, updated_at = NOW() WHERE id = ?', [$status, $adminId, $id]);
}

function add_project_note(int $projectRequestId, int $userId, string $note): void
{
    $note = trim($note);
    if ($note === '') {
        return;
    }
    db_exec('INSERT INTO project_request_notes (project_request_id, user_id, note, created_at) VALUES (?, ?, ?, NOW())', [$projectRequestId, $userId, $note]);
}
