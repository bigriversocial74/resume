<?php
declare(strict_types=1);

function list_project_requests(int $limit = 50): array
{
    $limit = max(1, min(100, $limit));
    return db_all('SELECT * FROM project_requests ORDER BY created_at DESC LIMIT ' . $limit);
}

function read_project_request(int $id): ?array
{
    return db_one('SELECT * FROM project_requests WHERE id = ? LIMIT 1', [$id]);
}
