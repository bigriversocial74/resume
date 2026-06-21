# CRM Setup

## 1. Configure PHP

Copy `app/config.example.php` to `app/config.php` and add the real database credentials.

`app/config.php` is ignored by git and should never be committed.

## 2. Create database tables and starter admin

For a new empty database, import the core schema first:

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

Starter admin:

- Username: `dave`
- Email: `bigriversocial74@gmail.com`
- Initial password: `123456`

After logging in, open `/admin/account.php` and update the username, email, full name, and password.

## 3. Authentication notes

- Public customer self-registration is intentionally not included.
- Admins create customer accounts.
- Passwords are stored with PHP `password_hash()`.
- Login uses `password_verify()`.
- Session cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` when served over HTTPS.
- Forms use CSRF tokens.
- Login attempts lock after repeated failures.

## 4. Chat, analytics, and agent notes

- Public website chat widget: `assets/chat-widget.js`.
- Visit tracking endpoint: `/api/track-visit.php`.
- Admin chat dashboard: `/admin/chat.php`.
- Analytics dashboard: `/admin/analytics.php`.
- Agent knowledge base: `/admin/knowledge.php`.
- Chat automation can be toggled on or off from the knowledge base page.
- DOCX text extraction requires PHP ZipArchive.
- PDF text extraction requires `pdftotext` on the server.
- Audio and video uploads are stored for a later transcription worker.

## 5. Next security step before production

Before public production use, replace the starter password immediately and wire a secure credential-delivery step or one-time account setup link for customer accounts.
