# CRM Setup

## 1. Configure PHP

Copy `app/config.example.php` to `app/config.php` and add the real database credentials.

`app/config.php` is ignored by git and should never be committed.

## 2. Create database tables

Import:

```sql
source database/schema.sql;
```

## 3. Create the first admin

Use the CLI script:

```bash
ADMIN_EMAIL="you@example.com" ADMIN_NAME="David Evans" ADMIN_PASS="Use-A-Strong-Password-123!" php scripts/create_admin.php
```

## 4. Authentication notes

- Public customer self-registration is intentionally not included.
- Admins create customer accounts.
- Passwords are stored with PHP `password_hash()`.
- Login uses `password_verify()`.
- Session cookies are `HttpOnly`, `SameSite=Lax`, and `Secure` when served over HTTPS.
- Forms use CSRF tokens.
- Login attempts lock after repeated failures.

## 5. Next security step before production

The customer account creation page is intentionally conservative. Before public production use, wire a secure credential-delivery step or a one-time account setup link so credentials are never emailed or exposed casually.
