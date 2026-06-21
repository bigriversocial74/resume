<?php
require_once __DIR__ . '/../../app/bootstrap.php';
$token = (string)($_GET['token'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    http_response_code(404);
    exit;
}
$row = db_one('SELECT file_path, mime_type FROM media_profiles WHERE file_token = ? LIMIT 1', [$token]);
if (!$row || empty($row['file_path'])) {
    http_response_code(404);
    exit;
}
$root = realpath(dirname(__DIR__, 2));
$path = realpath($root . '/' . ltrim((string)$row['file_path'], '/'));
$allowedRoot = realpath($root . '/storage/media-profiles');
if (!$path || !$allowedRoot || !str_starts_with($path, $allowedRoot) || !is_file($path)) {
    http_response_code(404);
    exit;
}
header('Content-Type: ' . ($row['mime_type'] ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=3600');
readfile($path);
