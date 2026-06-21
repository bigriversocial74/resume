# Database import

For a new install, import one file from the repository root:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/import_all.sql
```

`database/import_all.sql` now sources the main `database/schema.sql`. The main schema includes users, CRM project requests, customer projects, text chat, visit analytics, agent knowledge base, AI settings, Tavus video conversation tracking, and the starter admin.

Do not separately import the obsolete migration or bootstrap files. They are retained only as historical references.

After logging in, open `/admin/account.php` and update the username, email, full name, and password.
