-- Chat bubble and website analytics tables.
-- Import after database/schema.sql.

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
