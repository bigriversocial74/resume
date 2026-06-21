# CRM Setup

## 1. Configure PHP

Copy `app/config.example.php` to `app/config.php` and add the real database credentials.

`app/config.php` is ignored by git and should never be committed.

## 2. Create database tables and starter admin

For a new empty database, import **one file only**:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/schema.sql
```

The current `database/schema.sql` includes the full CRM schema, username support, and the starter admin account.

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

## 4. Next security step before production

Before public production use, replace the starter password immediately and wire a secure credential-delivery step or one-time account setup link for customer accounts.
