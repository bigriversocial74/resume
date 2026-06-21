-- David Evans CRM foundation
-- MySQL 8+ / MariaDB 10.4+
-- Fresh install file: import this single file into an empty database.
-- Includes username support and the initial admin seed account.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

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

-- Initial admin account
-- username: dave
-- email: bigriversocial74@gmail.com
-- password: 123456
-- Change this immediately at /admin/account.php after first login.
INSERT INTO users (
  email,
  username,
  password_hash,
  full_name,
  role,
  status,
  must_change_password,
  created_at,
  updated_at
) VALUES (
  'bigriversocial74@gmail.com',
  'dave',
  '$2y$12$SgOrPOsThvL1Yyx2VjacY.DsxWB79LXyb.58muwr48DHic/3NAH/S',
  'Dave',
  'admin',
  'active',
  1,
  NOW(),
  NOW()
);
