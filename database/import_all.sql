-- Single fresh-install import entry point.
-- Run this one file from the repository root with:
-- mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/import_all.sql
-- The main schema now contains CRM, chat, analytics, knowledge base, AI settings, Tavus video chat, and starter admin.

SOURCE database/schema.sql;
