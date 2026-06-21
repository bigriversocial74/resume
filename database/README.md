# Database import

For a new install, import one file from the repository root:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/import_all.sql
```

`database/import_all.sql` sources the main schema and the media profile table used by the Tavus settings page.

Do not separately import the obsolete migration or bootstrap files. They are retained only as historical references.

After logging in, open `/admin/account.php` and update the username, email, full name, and password.
