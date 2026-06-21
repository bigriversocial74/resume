<?php
declare(strict_types=1);

function agent_setting(string $key, string $default = ''): string
{
    $row = db_one('SELECT setting_value FROM agent_settings WHERE setting_key = ? LIMIT 1', [$key]);
    return $row ? (string)$row['setting_value'] : $default;
}

function set_agent_setting(string $key, string $value, int $userId): void
{
    db_exec(
        'INSERT INTO agent_settings (setting_key, setting_value, updated_by_user_id, updated_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by_user_id = VALUES(updated_by_user_id), updated_at = NOW()',
        [$key, $value, $userId]
    );
}

function chat_automation_enabled(): bool
{
    return agent_setting('chat_automation_enabled', '0') === '1';
}

function normalize_agent_html(string $title, string $plainText, array $meta = []): string
{
    $title = trim($title) ?: 'Knowledge Entry';
    $plainText = trim(preg_replace("/\r\n|\r/", "\n", $plainText));
    $paragraphs = preg_split('/\n{2,}/', $plainText) ?: [];
    $html = '<article class="agent-knowledge">' . "\n";
    $html .= '<h1>' . e($title) . '</h1>' . "\n";
    if ($meta) {
        $html .= '<dl class="agent-meta">' . "\n";
        foreach ($meta as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $html .= '<dt>' . e((string)$key) . '</dt><dd>' . e((string)$value) . '</dd>' . "\n";
        }
        $html .= '</dl>' . "\n";
    }
    $html .= '<section>' . "\n";
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if ($paragraph === '') {
            continue;
        }
        if (preg_match('/^[-*]\s+/m', $paragraph)) {
            $html .= '<ul>' . "\n";
            foreach (preg_split('/\n+/', $paragraph) ?: [] as $line) {
                $line = preg_replace('/^[-*]\s+/', '', trim($line));
                if ($line !== '') {
                    $html .= '<li>' . e($line) . '</li>' . "\n";
                }
            }
            $html .= '</ul>' . "\n";
        } else {
            $html .= '<p>' . e($paragraph) . '</p>' . "\n";
        }
    }
    $html .= '</section>' . "\n";
    $html .= '</article>';
    return $html;
}

function plain_text_from_html(string $html): string
{
    return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function create_knowledge_source(array $data, int $userId): int
{
    db_exec(
        'INSERT INTO knowledge_sources (source_type, title, source_url, original_filename, stored_path, mime_type, file_size, status, extraction_notes, created_by_user_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
        [
            $data['source_type'],
            $data['title'],
            $data['source_url'] ?? null,
            $data['original_filename'] ?? null,
            $data['stored_path'] ?? null,
            $data['mime_type'] ?? null,
            $data['file_size'] ?? null,
            $data['status'] ?? 'draft',
            $data['extraction_notes'] ?? null,
            $userId,
        ]
    );
    return (int)db()->lastInsertId();
}

function add_knowledge_chunk(int $sourceId, string $title, string $agentHtml, int $sortOrder = 0): void
{
    $plain = plain_text_from_html($agentHtml);
    $keywords = implode(' ', array_slice(array_unique(preg_split('/\W+/', mb_strtolower($plain)) ?: []), 0, 80));
    db_exec(
        'INSERT INTO knowledge_chunks (source_id, chunk_title, agent_html, plain_text, keywords, sort_order, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())',
        [$sourceId, $title, $agentHtml, $plain, $keywords, $sortOrder]
    );
}

function create_manual_knowledge(string $title, string $content, int $userId): int
{
    $agentHtml = normalize_agent_html($title, $content, ['source' => 'manual admin entry']);
    $sourceId = create_knowledge_source([
        'source_type' => 'manual',
        'title' => $title,
        'status' => 'ready',
        'extraction_notes' => 'Manual content normalized into agent HTML.',
    ], $userId);
    add_knowledge_chunk($sourceId, $title, $agentHtml);
    return $sourceId;
}

function ensure_storage_dir(): string
{
    $dir = dirname(__DIR__) . '/storage/knowledge';
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
    return $dir;
}

function extract_docx_text(string $path): string
{
    if (!class_exists('ZipArchive')) {
        return '';
    }
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) {
        return '';
    }
    $xml = $zip->getFromName('word/document.xml') ?: '';
    $zip->close();
    $xml = preg_replace('/<w:p[^>]*>/', "\n", $xml);
    $text = strip_tags((string)$xml);
    return trim(html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8'));
}

function extract_pdf_text_if_available(string $path): string
{
    $command = trim((string)shell_exec('command -v pdftotext 2>/dev/null'));
    if ($command === '') {
        return '';
    }
    $tmp = tempnam(sys_get_temp_dir(), 'kb_pdf_');
    $cmd = escapeshellcmd($command) . ' -layout ' . escapeshellarg($path) . ' ' . escapeshellarg($tmp) . ' 2>/dev/null';
    shell_exec($cmd);
    $text = is_file($tmp) ? (string)file_get_contents($tmp) : '';
    @unlink($tmp);
    return trim($text);
}

function extract_uploaded_knowledge(array $file, string $title, int $userId): int
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed.');
    }
    $maxBytes = 250 * 1024 * 1024;
    if ((int)$file['size'] > $maxBytes) {
        throw new RuntimeException('File is too large. Max 250MB.');
    }
    $allowed = [
        'text/plain', 'text/html', 'text/markdown', 'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword', 'audio/mpeg', 'audio/mp4', 'audio/wav', 'video/mp4', 'video/quicktime', 'video/webm'
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: (string)($file['type'] ?? 'application/octet-stream');
    if (!in_array($mime, $allowed, true)) {
        throw new RuntimeException('Unsupported file type: ' . $mime);
    }
    $dir = ensure_storage_dir();
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string)$file['name']);
    $storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '_' . $safeName;
    $storedPath = $dir . '/' . $storedName;
    if (!move_uploaded_file($file['tmp_name'], $storedPath)) {
        throw new RuntimeException('Could not save uploaded file.');
    }

    $text = '';
    $notes = '';
    if (str_starts_with($mime, 'text/')) {
        $text = (string)file_get_contents($storedPath);
        $notes = 'Text-based file extracted directly and normalized into agent HTML.';
    } elseif ($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
        $text = extract_docx_text($storedPath);
        $notes = $text !== '' ? 'DOCX extracted from document XML and normalized into agent HTML.' : 'DOCX uploaded, but server ZipArchive extraction is unavailable or failed.';
    } elseif ($mime === 'application/pdf') {
        $text = extract_pdf_text_if_available($storedPath);
        $notes = $text !== '' ? 'PDF text extracted using pdftotext and normalized into agent HTML.' : 'PDF uploaded. Install pdftotext on the server or add a PDF extraction service to process this file.';
    } elseif (str_starts_with($mime, 'audio/') || str_starts_with($mime, 'video/')) {
        $notes = 'Media uploaded. Add Whisper/OpenAI transcription or another speech-to-text worker to generate transcript text.';
    } else {
        $notes = 'File uploaded for later processing.';
    }

    $status = trim($text) !== '' ? 'ready' : 'needs_review';
    $sourceId = create_knowledge_source([
        'source_type' => str_starts_with($mime, 'audio/') || str_starts_with($mime, 'video/') ? 'transcript' : 'upload',
        'title' => $title ?: $safeName,
        'original_filename' => $file['name'],
        'stored_path' => 'storage/knowledge/' . $storedName,
        'mime_type' => $mime,
        'file_size' => (int)$file['size'],
        'status' => $status,
        'extraction_notes' => $notes,
    ], $userId);

    if (trim($text) !== '') {
        $agentHtml = normalize_agent_html($title ?: $safeName, $text, ['source' => $safeName, 'mime_type' => $mime]);
        add_knowledge_chunk($sourceId, $title ?: $safeName, $agentHtml);
    }
    return $sourceId;
}

function validate_public_url(string $url): string
{
    $url = trim($url);
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('Invalid URL.');
    }
    $parts = parse_url($url);
    if (!in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
        throw new RuntimeException('Only http and https URLs are allowed.');
    }
    $host = $parts['host'] ?? '';
    $ip = gethostbyname($host);
    if ($ip === $host || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        throw new RuntimeException('URL host is not allowed.');
    }
    return $url;
}

function create_website_knowledge(string $url, int $userId): int
{
    $url = validate_public_url($url);
    $context = stream_context_create(['http' => ['timeout' => 8, 'user_agent' => 'DavidEvansKnowledgeBot/1.0']]);
    $html = @file_get_contents($url, false, $context);
    if ($html === false || trim($html) === '') {
        throw new RuntimeException('Could not read website.');
    }
    $html = substr($html, 0, 750000);
    preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches);
    $title = trim(html_entity_decode(strip_tags($matches[1] ?? $url), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?: $url;
    $text = plain_text_from_html($html);
    $text = preg_replace('/\s+/', ' ', $text);
    $sourceId = create_knowledge_source([
        'source_type' => 'website',
        'title' => mb_substr($title, 0, 250),
        'source_url' => $url,
        'status' => 'ready',
        'extraction_notes' => 'Website scanned, stripped to readable text, and normalized into agent HTML.',
    ], $userId);
    $agentHtml = normalize_agent_html($title, $text, ['source_url' => $url]);
    add_knowledge_chunk($sourceId, $title, $agentHtml);
    return $sourceId;
}

function list_knowledge_sources(int $limit = 80): array
{
    return db_all('SELECT ks.*, u.full_name AS created_by FROM knowledge_sources ks JOIN users u ON u.id = ks.created_by_user_id ORDER BY ks.created_at DESC LIMIT ' . max(1, min(150, $limit)));
}

function knowledge_search(string $question, int $limit = 4): array
{
    $tokens = array_values(array_filter(array_unique(preg_split('/\W+/', mb_strtolower($question)) ?: []), fn($t) => mb_strlen($t) >= 3));
    if (!$tokens) {
        return [];
    }
    $where = [];
    $params = [];
    foreach (array_slice($tokens, 0, 8) as $token) {
        $where[] = '(kc.plain_text LIKE ? OR kc.keywords LIKE ?)';
        $params[] = '%' . $token . '%';
        $params[] = '%' . $token . '%';
    }
    $sql = 'SELECT kc.*, ks.title AS source_title, ks.source_url FROM knowledge_chunks kc JOIN knowledge_sources ks ON ks.id = kc.source_id WHERE kc.is_active = 1 AND ks.status = "ready" AND (' . implode(' OR ', $where) . ') ORDER BY kc.updated_at DESC LIMIT ' . max(1, min(8, $limit));
    return db_all($sql, $params);
}

function knowledge_agent_reply(string $question): string
{
    $matches = knowledge_search($question, 3);
    if (!$matches) {
        return 'I do not have that answer in the knowledge base yet. I have saved your question so Dave can respond and add the right source material.';
    }
    $parts = [];
    foreach ($matches as $match) {
        $text = trim((string)$match['plain_text']);
        $parts[] = mb_strimwidth($text, 0, 520, '...');
    }
    return "Based on the current knowledge base:\n\n" . implode("\n\n", $parts) . "\n\nIf you want, I can also route this as a project request.";
}
