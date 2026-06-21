ALTER TABLE users
  ADD COLUMN username VARCHAR(80) NULL AFTER email,
  ADD UNIQUE KEY uq_users_username (username);

UPDATE users
SET username = LOWER(SUBSTRING_INDEX(email, '@', 1))
WHERE username IS NULL;

ALTER TABLE users
  MODIFY username VARCHAR(80) NOT NULL;
