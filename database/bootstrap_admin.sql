-- Bootstrap admin account for initial setup.
-- Login with username: dave
-- Login with email: bigriversocial74@gmail.com
-- Update the password immediately after first login.

INSERT INTO users (email, username, password_hash, full_name, role, status, must_change_password, created_at, updated_at)
VALUES (
  'bigriversocial74@gmail.com',
  'dave',
  '$2y$12$U4mLCm0gdmvB2P3FdsT9yOFrSHtyQaBPd9KvwK4iGnzaO8O0LaM0m',
  'Dave',
  'admin',
  'active',
  1,
  NOW(),
  NOW()
)
ON DUPLICATE KEY UPDATE
  username = VALUES(username),
  password_hash = VALUES(password_hash),
  full_name = VALUES(full_name),
  role = 'admin',
  status = 'active',
  must_change_password = 1,
  updated_at = NOW();
