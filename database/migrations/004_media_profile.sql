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

INSERT INTO agent_settings (setting_key, setting_value, updated_at) VALUES
('tavus_active_replica_id','',NOW())
ON DUPLICATE KEY UPDATE setting_value = setting_value;
