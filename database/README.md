# Database import

For a new install, import the core schema first:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/schema.sql
```

Then import the v1 chat and analytics tables:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/migrations/002_chat_analytics.sql
```

Then import the agent knowledge base tables:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/migrations/003_agent_knowledge_base.sql
```

Do **not** import the obsolete migration or bootstrap files. They are retained only as historical references.

The seeded admin account is:

- Username: `dave`
- Email: `bigriversocial74@gmail.com`
- Initial password: `123456`

After logging in, open `/admin/account.php` and update the username, email, full name, and password.
