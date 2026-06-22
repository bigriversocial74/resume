-- Upgrade after importing schema.sql and bootstrap_admin.sql.
-- Use this on an EXISTING database that already has the base CRM schema/admin user.
-- Import this one file in phpMyAdmin or your host database tool.
-- Do not run import_all.sql on an existing database unless you are rebuilding from scratch.

SET NAMES utf8mb4;

DELIMITER //

CREATE PROCEDURE de_add_col_if_missing(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_def TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_col
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_col, '` ', p_def);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END//

CREATE PROCEDURE de_add_index_if_missing(IN p_table VARCHAR(64), IN p_index VARCHAR(64), IN p_def TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND INDEX_NAME = p_index
  ) THEN
    SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD ', p_def);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END//

DELIMITER ;

-- Make sure the current auth code has the expected user columns.
CALL de_add_col_if_missing('users', 'username', 'VARCHAR(80) NULL AFTER email');
CALL de_add_col_if_missing('users', 'role', "ENUM('admin','customer') NOT NULL DEFAULT 'customer' AFTER full_name");
CALL de_add_col_if_missing('users', 'must_change_password', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER status');
CALL de_add_col_if_missing('users', 'failed_login_count', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER must_change_password');
CALL de_add_col_if_missing('users', 'locked_until', 'DATETIME NULL AFTER failed_login_count');
CALL de_add_col_if_missing('users', 'last_login_at', 'DATETIME NULL AFTER locked_until');
CALL de_add_col_if_missing('users', 'last_login_ip_hash', 'CHAR(64) NULL AFTER last_login_at');
CALL de_add_col_if_missing('users', 'created_by_user_id', 'BIGINT UNSIGNED NULL AFTER last_login_ip_hash');
CALL de_add_index_if_missing('users', 'uq_users_username', 'UNIQUE KEY uq_users_username (username)');
CALL de_add_index_if_missing('users', 'idx_users_role_status', 'INDEX idx_users_role_status (role, status)');

UPDATE users
SET username = 'dave', role = 'admin', status = 'active'
WHERE email = 'bigriversocial74@gmail.com' OR username = 'dave';

-- Visit tracking table and professional time-on-site fields.
CREATE TABLE IF NOT EXISTS website_visits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visitor_key CHAR(64) NOT NULL,
  session_key CHAR(64) NOT NULL,
  page_url VARCHAR(700) NOT NULL,
  page_title VARCHAR(255) NULL,
  referrer VARCHAR(700) NULL,
  user_agent VARCHAR(255) NULL,
  ip_hash CHAR(64) NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_ping_at DATETIME NULL,
  ended_at DATETIME NULL,
  time_on_page_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_website_visits_created (created_at),
  INDEX idx_website_visits_visitor_created (visitor_key, created_at),
  INDEX idx_website_visits_session_created (session_key, created_at),
  INDEX idx_website_visits_ip_created (ip_hash, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL de_add_col_if_missing('website_visits', 'started_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER ip_hash');
CALL de_add_col_if_missing('website_visits', 'last_ping_at', 'DATETIME NULL AFTER started_at');
CALL de_add_col_if_missing('website_visits', 'ended_at', 'DATETIME NULL AFTER last_ping_at');
CALL de_add_col_if_missing('website_visits', 'time_on_page_seconds', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER ended_at');
CALL de_add_index_if_missing('website_visits', 'idx_website_visits_ip_created', 'INDEX idx_website_visits_ip_created (ip_hash, created_at)');

-- Professional live chat tables and tracking fields.
CREATE TABLE IF NOT EXISTS chat_conversations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visitor_key CHAR(64) NOT NULL,
  name VARCHAR(190) NULL,
  email VARCHAR(190) NULL,
  status ENUM('open','pending','closed','archived') NOT NULL DEFAULT 'open',
  assigned_to_user_id BIGINT UNSIGNED NULL,
  linked_project_request_id BIGINT UNSIGNED NULL,
  ip_hash CHAR(64) NULL,
  user_agent VARCHAR(255) NULL,
  first_page_url VARCHAR(700) NULL,
  last_page_url VARCHAR(700) NULL,
  referrer VARCHAR(700) NULL,
  total_time_on_site_seconds INT UNSIGNED NOT NULL DEFAULT 0,
  last_seen_at DATETIME NULL,
  last_message_at DATETIME NULL,
  last_visitor_message_at DATETIME NULL,
  last_admin_message_at DATETIME NULL,
  accepted_at DATETIME NULL,
  fallback_sent_at DATETIME NULL,
  visitor_notified_at DATETIME NULL,
  admin_last_read_at DATETIME NULL,
  visitor_last_read_at DATETIME NULL,
  unread_admin_count INT UNSIGNED NOT NULL DEFAULT 0,
  unread_visitor_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_chat_conversations_status_last (status, last_message_at),
  INDEX idx_chat_conversations_visitor (visitor_key),
  INDEX idx_chat_conversations_unread_admin (unread_admin_count, last_visitor_message_at),
  INDEX idx_chat_conversations_ip_created (ip_hash, created_at),
  CONSTRAINT fk_chat_conversations_assigned FOREIGN KEY (assigned_to_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_chat_conversations_project FOREIGN KEY (linked_project_request_id) REFERENCES project_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL de_add_col_if_missing('chat_conversations', 'ip_hash', 'CHAR(64) NULL AFTER linked_project_request_id');
CALL de_add_col_if_missing('chat_conversations', 'user_agent', 'VARCHAR(255) NULL AFTER ip_hash');
CALL de_add_col_if_missing('chat_conversations', 'first_page_url', 'VARCHAR(700) NULL AFTER user_agent');
CALL de_add_col_if_missing('chat_conversations', 'last_page_url', 'VARCHAR(700) NULL AFTER first_page_url');
CALL de_add_col_if_missing('chat_conversations', 'referrer', 'VARCHAR(700) NULL AFTER last_page_url');
CALL de_add_col_if_missing('chat_conversations', 'total_time_on_site_seconds', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER referrer');
CALL de_add_col_if_missing('chat_conversations', 'last_seen_at', 'DATETIME NULL AFTER total_time_on_site_seconds');
CALL de_add_col_if_missing('chat_conversations', 'last_visitor_message_at', 'DATETIME NULL AFTER last_message_at');
CALL de_add_col_if_missing('chat_conversations', 'last_admin_message_at', 'DATETIME NULL AFTER last_visitor_message_at');
CALL de_add_col_if_missing('chat_conversations', 'accepted_at', 'DATETIME NULL AFTER last_admin_message_at');
CALL de_add_col_if_missing('chat_conversations', 'fallback_sent_at', 'DATETIME NULL AFTER accepted_at');
CALL de_add_col_if_missing('chat_conversations', 'visitor_notified_at', 'DATETIME NULL AFTER fallback_sent_at');
CALL de_add_col_if_missing('chat_conversations', 'admin_last_read_at', 'DATETIME NULL AFTER visitor_notified_at');
CALL de_add_col_if_missing('chat_conversations', 'visitor_last_read_at', 'DATETIME NULL AFTER admin_last_read_at');
CALL de_add_col_if_missing('chat_conversations', 'unread_admin_count', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER visitor_last_read_at');
CALL de_add_col_if_missing('chat_conversations', 'unread_visitor_count', 'INT UNSIGNED NOT NULL DEFAULT 0 AFTER unread_admin_count');
CALL de_add_index_if_missing('chat_conversations', 'idx_chat_conversations_unread_admin', 'INDEX idx_chat_conversations_unread_admin (unread_admin_count, last_visitor_message_at)');
CALL de_add_index_if_missing('chat_conversations', 'idx_chat_conversations_ip_created', 'INDEX idx_chat_conversations_ip_created (ip_hash, created_at)');

CREATE TABLE IF NOT EXISTS chat_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  sender_type ENUM('visitor','admin','system') NOT NULL,
  sender_user_id BIGINT UNSIGNED NULL,
  message TEXT NOT NULL,
  metadata JSON NULL,
  seen_by_admin_at DATETIME NULL,
  seen_by_visitor_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_chat_messages_conversation_created (conversation_id, created_at),
  INDEX idx_chat_messages_sender_created (sender_type, created_at),
  CONSTRAINT fk_chat_messages_conversation FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_chat_messages_user FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL de_add_col_if_missing('chat_messages', 'seen_by_admin_at', 'DATETIME NULL AFTER metadata');
CALL de_add_col_if_missing('chat_messages', 'seen_by_visitor_at', 'DATETIME NULL AFTER seen_by_admin_at');
CALL de_add_index_if_missing('chat_messages', 'idx_chat_messages_sender_created', 'INDEX idx_chat_messages_sender_created (sender_type, created_at)');

-- Agent settings and knowledge base tables.
CREATE TABLE IF NOT EXISTS agent_settings (
  setting_key VARCHAR(120) NOT NULL PRIMARY KEY,
  setting_value TEXT NULL,
  updated_by_user_id BIGINT UNSIGNED NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_agent_settings_user FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS knowledge_sources (
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

CREATE TABLE IF NOT EXISTS knowledge_chunks (
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

-- Tavus/media support.
CREATE TABLE IF NOT EXISTS media_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source_type ENUM('hero','upload','external') NOT NULL,
  display_name VARCHAR(190) NOT NULL,
  provider VARCHAR(80) NOT NULL DEFAULT 'tavus',
  provider_item_id VARCHAR(190) NULL,
  status VARCHAR(80) NULL,
  file_path VARCHAR(700) NULL,
  file_url VARCHAR(900) NULL,
  file_token CHAR(64) NULL,
  mime_type VARCHAR(190) NULL,
  file_size BIGINT UNSIGNED NULL,
  option_one VARCHAR(190) NULL,
  option_two VARCHAR(190) NULL,
  option_three VARCHAR(190) NULL,
  provider_response JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  created_by_user_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_media_profiles_token (file_token),
  INDEX idx_media_profiles_provider_item (provider_item_id),
  INDEX idx_media_profiles_active (is_active),
  CONSTRAINT fk_media_profiles_user FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS video_conversations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visitor_key CHAR(64) NOT NULL,
  provider ENUM('tavus') NOT NULL DEFAULT 'tavus',
  provider_conversation_id VARCHAR(190) NULL,
  conversation_url VARCHAR(900) NULL,
  persona_id VARCHAR(190) NULL,
  replica_id VARCHAR(190) NULL,
  status ENUM('created','active','ended','failed') NOT NULL DEFAULT 'created',
  test_mode TINYINT(1) NOT NULL DEFAULT 1,
  started_at DATETIME NULL,
  ended_at DATETIME NULL,
  metadata JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_video_conversations_visitor (visitor_key, created_at),
  INDEX idx_video_conversations_provider_status (provider, status),
  INDEX idx_video_conversations_provider_id (provider_conversation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO agent_settings (setting_key, setting_value, updated_at) VALUES
('chat_automation_enabled','0',NOW()),
('agent_model_provider','openai',NOW()),
('agent_system_prompt','You are the website chat agent for David Evans. Answer using only the provided knowledge base context. Be concise, helpful, and honest. If the answer is not in the context, say you do not have that answer yet and offer to route the question to Dave.',NOW()),
('tavus_video_enabled','1',NOW()),
('tavus_test_mode','1',NOW()),
('tavus_active_replica_id','',NOW())
ON DUPLICATE KEY UPDATE setting_value = setting_value;

DROP PROCEDURE IF EXISTS de_add_col_if_missing;
DROP PROCEDURE IF EXISTS de_add_index_if_missing;
