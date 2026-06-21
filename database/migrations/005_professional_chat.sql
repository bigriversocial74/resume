-- Professional chat tracking and notification fields.
-- Included by database/import_all.sql for fresh installs.

ALTER TABLE website_visits
  ADD COLUMN started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER ip_hash,
  ADD COLUMN last_ping_at DATETIME NULL AFTER started_at,
  ADD COLUMN ended_at DATETIME NULL AFTER last_ping_at,
  ADD COLUMN time_on_page_seconds INT UNSIGNED NOT NULL DEFAULT 0 AFTER ended_at,
  ADD INDEX idx_website_visits_ip_created (ip_hash, created_at);

ALTER TABLE chat_conversations
  ADD COLUMN ip_hash CHAR(64) NULL AFTER linked_project_request_id,
  ADD COLUMN user_agent VARCHAR(255) NULL AFTER ip_hash,
  ADD COLUMN first_page_url VARCHAR(700) NULL AFTER user_agent,
  ADD COLUMN last_page_url VARCHAR(700) NULL AFTER first_page_url,
  ADD COLUMN referrer VARCHAR(700) NULL AFTER last_page_url,
  ADD COLUMN total_time_on_site_seconds INT UNSIGNED NOT NULL DEFAULT 0 AFTER referrer,
  ADD COLUMN last_seen_at DATETIME NULL AFTER total_time_on_site_seconds,
  ADD COLUMN last_visitor_message_at DATETIME NULL AFTER last_message_at,
  ADD COLUMN last_admin_message_at DATETIME NULL AFTER last_visitor_message_at,
  ADD COLUMN accepted_at DATETIME NULL AFTER last_admin_message_at,
  ADD COLUMN fallback_sent_at DATETIME NULL AFTER accepted_at,
  ADD COLUMN visitor_notified_at DATETIME NULL AFTER fallback_sent_at,
  ADD COLUMN admin_last_read_at DATETIME NULL AFTER visitor_notified_at,
  ADD COLUMN visitor_last_read_at DATETIME NULL AFTER admin_last_read_at,
  ADD COLUMN unread_admin_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER visitor_last_read_at,
  ADD COLUMN unread_visitor_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER unread_admin_count,
  ADD INDEX idx_chat_conversations_unread_admin (unread_admin_count, last_visitor_message_at),
  ADD INDEX idx_chat_conversations_ip_created (ip_hash, created_at);

ALTER TABLE chat_messages
  ADD COLUMN seen_by_admin_at DATETIME NULL AFTER metadata,
  ADD COLUMN seen_by_visitor_at DATETIME NULL AFTER seen_by_admin_at,
  ADD INDEX idx_chat_messages_sender_created (sender_type, created_at);
