-- David Evans CRM foundation
-- MySQL 8+ / MariaDB 10.4+
-- Fresh install file: import this single file into an empty database.
-- Includes CRM, users, project requests, chat, analytics, knowledge base, AI settings, Tavus video chat, media profiles, and starter admin.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS video_conversations;
DROP TABLE IF EXISTS media_profiles;
DROP TABLE IF EXISTS knowledge_chunks;
DROP TABLE IF EXISTS knowledge_sources;
DROP TABLE IF EXISTS agent_settings;
DROP TABLE IF EXISTS chat_messages;
DROP TABLE IF EXISTS chat_conversations;
DROP TABLE IF EXISTS website_visits;
DROP TABLE IF EXISTS customer_projects;
DROP TABLE IF EXISTS project_request_notes;
DROP TABLE IF EXISTS project_requests;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  username VARCHAR(80) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(190) NOT NULL,
  role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  status ENUM('active','disabled') NOT NULL DEFAULT 'active',
  must_change_password TINYINT(1) NOT NULL DEFAULT 1,
  failed_login_count INT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login_at DATETIME NULL,
  last_login_ip_hash CHAR(64) NULL,
  created_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_username (username),
  INDEX idx_users_role_status (role, status),
  CONSTRAINT fk_users_created_by FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_requests (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_user_id BIGINT UNSIGNED NULL,
  full_name VARCHAR(190) NOT NULL,
  email VARCHAR(190) NOT NULL,
  phone VARCHAR(80) NULL,
  company VARCHAR(190) NULL,
  project_types JSON NULL,
  services JSON NULL,
  primary_goal VARCHAR(190) NULL,
  budget_range VARCHAR(80) NULL,
  target_timeline VARCHAR(80) NULL,
  website_url VARCHAR(500) NULL,
  brand_assets_url VARCHAR(500) NULL,
  social_links TEXT NULL,
  notes TEXT NULL,
  status ENUM('new','reviewing','qualified','proposal_sent','active','closed_won','closed_lost','archived') NOT NULL DEFAULT 'new',
  updated_by_user_id BIGINT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_project_requests_status_created (status, created_at),
  INDEX idx_project_requests_email (email),
  CONSTRAINT fk_project_requests_customer FOREIGN KEY (customer_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_project_requests_updated_by FOREIGN KEY (updated_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE project_request_notes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  project_request_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  note TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_project_request_notes_project (project_request_id, created_at),
  CONSTRAINT fk_project_request_notes_project FOREIGN KEY (project_request_id) REFERENCES project_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_project_request_notes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customer_projects (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_user_id BIGINT UNSIGNED NOT NULL,
  project_request_id BIGINT UNSIGNED NULL,
  title VARCHAR(190) NOT NULL,
  status ENUM('planning','active','review','completed','paused','cancelled') NOT NULL DEFAULT 'planning',
  summary TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customer_projects_user_status (customer_user_id, status),
  CONSTRAINT fk_customer_projects_user FOREIGN KEY (customer_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_customer_projects_request FOREIGN KEY (project_request_id) REFERENCES project_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE website_visits (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visitor_key CHAR(64) NOT NULL,
  session_key CHAR(64) NOT NULL,
  page_url VARCHAR(700) NOT NULL,
  page_title VARCHAR(255) NULL,
  referrer VARCHAR(700) NULL,
  user_agent VARCHAR(255) NULL,
  ip_hash CHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_website_visits_created (created_at),
  INDEX idx_website_visits_visitor_created (visitor_key, created_at),
  INDEX idx_website_visits_session_created (session_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_conversations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visitor_key CHAR(64) NOT NULL,
  name VARCHAR(190) NULL,
  email VARCHAR(190) NULL,
  status ENUM('open','pending','closed','archived') NOT NULL DEFAULT 'open',
  assigned_to_user_id BIGINT UNSIGNED NULL,
  linked_project_request_id BIGINT UNSIGNED NULL,
  last_message_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_chat_conversations_status_last (status, last_message_at),
  INDEX idx_chat_conversations_visitor (visitor_key),
  CONSTRAINT fk_chat_conversations_assigned FOREIGN KEY (assigned_to_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_chat_conversations_project FOREIGN KEY (linked_project_request_id) REFERENCES project_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE chat_messages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  sender_type ENUM('visitor','admin','system') NOT NULL,
  sender_user_id BIGINT UNSIGNED NULL,
  message TEXT NOT NULL,
  metadata JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_chat_messages_conversation_created (conversation_id, created_at),
  CONSTRAINT fk_chat_messages_conversation FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_chat_messages_user FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE media_profiles (
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

CREATE TABLE video_conversations (
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

INSERT INTO users (email, username, password_hash, full_name, role, status, must_change_password, created_at, updated_at)
VALUES ('bigriversocial74@gmail.com','dave','$2y$12$SgOrPOsThvL1Yyx2VjacY.DsxWB79LXyb.58muwr48DHic/3NAH/S','Dave','admin','active',1,NOW(),NOW());

INSERT INTO agent_settings (setting_key, setting_value, updated_at) VALUES
('chat_automation_enabled','0',NOW()),
('agent_model_provider','openai',NOW()),
('agent_system_prompt','You are the website chat agent for David Evans. Answer using only the provided knowledge base context. Be concise, helpful, and honest. If the answer is not in the context, say you do not have that answer yet and offer to route the question to Dave.',NOW()),
('tavus_video_enabled','1',NOW()),
('tavus_test_mode','1',NOW()),
('tavus_active_replica_id','',NOW());
