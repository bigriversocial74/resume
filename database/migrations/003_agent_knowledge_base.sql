-- Agent knowledge base and chat automation controls.
-- Import after database/schema.sql and database/migrations/002_chat_analytics.sql.

CREATE TABLE agent_settings (
  setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
  setting_value TEXT NULL,
  updated_by_user_id BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_agent_settings_user FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE knowledge_sources (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_type ENUM('manual','website','upload','transcript') NOT NULL,
  title VARCHAR(255) NOT NULL,
  source_url VARCHAR(700) NULL,
  original_filename VARCHAR(255) NULL,
  stored_path VARCHAR(700) NULL,
  mime_type VARCHAR(190) NULL,
  file_size BIGINT UNSIGNED NULL,
  status ENUM('draft','processing','ready','needs_review','failed','archived') NOT NULL DEFAULT 'draft',
  extraction_notes TEXT NULL,
  created_by_user_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_knowledge_sources_status (status),
  INDEX idx_knowledge_sources_type (source_type),
  CONSTRAINT fk_knowledge_sources_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE knowledge_chunks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_id BIGINT UNSIGNED NOT NULL,
  chunk_title VARCHAR(255) NULL,
  agent_html MEDIUMTEXT NOT NULL,
  plain_text MEDIUMTEXT NOT NULL,
  keywords TEXT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FULLTEXT KEY ft_knowledge_plain (plain_text, keywords),
  INDEX idx_knowledge_chunks_source (source_id, sort_order),
  INDEX idx_knowledge_chunks_active (is_active),
  CONSTRAINT fk_knowledge_chunks_source FOREIGN KEY (source_id) REFERENCES knowledge_sources(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO agent_settings (setting_key, setting_value, updated_at)
VALUES ('chat_automation_enabled', '0', NOW())
ON DUPLICATE KEY UPDATE setting_value = setting_value;
