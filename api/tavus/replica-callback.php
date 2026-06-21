<?php
require_once __DIR__ . '/../../app/bootstrap.php';
header('Content-Type: application/json');
try {
    $payload = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
    $replicaId = (string)($payload['replica_id'] ?? $payload['replicaId'] ?? '');
    $status = (string)($payload['status'] ?? $payload['replica_status'] ?? '');
    if ($replicaId !== '') {
        db_exec('UPDATE media_profiles SET status = ?, provider_response = ?, updated_at = NOW() WHERE provider = "tavus" AND provider_item_id = ?', [$status ?: 'updated', json_encode($payload, JSON_THROW_ON_ERROR), $replicaId]);
    }
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false]);
}
