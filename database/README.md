# Database import

For a new install, import **one file only**:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/schema.sql
```

Do **not** import the migration file or bootstrap file after `schema.sql` on a fresh database. Those were split helpers during development; the current `schema.sql` is the consolidated source of truth for a fresh install.

The seeded admin account is:

- Username: `dave`
- Email: `bigriversocial74@gmail.com`
- Initial password: `123456`

After logging in, open `/admin/account.php` and update the username, email, full name, and password.
