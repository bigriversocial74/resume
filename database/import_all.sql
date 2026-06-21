-- Single fresh-install import entry point.
-- Run this one file from the repository root with:
-- mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/import_all.sql
-- Includes CRM, chat, analytics, knowledge base, AI settings, Tavus video chat, media profile assets, and starter admin.

SOURCE database/schema.sql;
SOURCE database/migrations/004_media_profile.sql;
