-- Single fresh-install import entry point.
-- Run this one file from the repository root with:
-- mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/import_all.sql

SOURCE database/schema.sql;
SOURCE database/migrations/002_chat_analytics.sql;
SOURCE database/migrations/003_agent_knowledge_base.sql;
