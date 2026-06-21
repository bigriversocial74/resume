# CRM Setup

## 1. Configure PHP

Copy `app/config.example.php` to `app/config.php` and add the real database credentials.

`app/config.php` is ignored by git and should never be committed.

## 2. Import the database

For a new empty database, import one file from the repository root:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/import_all.sql
```

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
- Hero video chat launcher: `assets/hero-video-chat.js`.
- Visit tracking endpoint: `/api/track-visit.php`.
- Text chat dashboard: `/admin/chat.php`.
- Analytics dashboard: `/admin/analytics.php`.
- Agent knowledge base: `/admin/knowledge.php`.
- Tavus settings page: `/admin/tavus.php`.
- Chat automation can be toggled on or off from the knowledge base page.
- The selected model provider retrieves knowledge chunks and drafts the text chat answer.
- DOCX text extraction requires PHP ZipArchive.
- PDF text extraction requires `pdftotext` on the server.
- Audio and video uploads are stored for a later transcription worker.

## 5. AI and video provider environment variables

Set the keys and models in server environment variables or in `app/config.php`:

- OpenAI: `OPENAI_API_KEY`, `OPENAI_MODEL`
- Claude: `ANTHROPIC_API_KEY`, `ANTHROPIC_MODEL`
- Gemini: `GEMINI_API_KEY`, `GEMINI_MODEL`
- Kimi: `KIMI_API_KEY`, `KIMI_MODEL`
- Tavus: `TAVUS_API_KEY`, `TAVUS_PERSONA_ID`, `TAVUS_REPLICA_ID`, `TAVUS_TEST_MODE`
- LiveAvatar: `LIVEAVATAR_API_KEY`, `LIVEAVATAR_ENDPOINT`, `LIVEAVATAR_PROJECT_ID`

Tavus conversations are created only after a visitor clicks **Chat With Dave** in the hero section. The server-side key is never exposed to browser code.

## 6. Next security step before production

Before public production use, replace the starter password immediately and wire a secure credential-delivery step or one-time account setup link for customer accounts.
