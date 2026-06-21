# Database import order

For a new install, import in this order:

```bash
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/schema.sql
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/migrations/001_add_username_to_users.sql
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < database/bootstrap_admin.sql
```

The bootstrap admin account is:

- Username: `dave`
- Email: `bigriversocial74@gmail.com`
- Initial password: the temporary password requested during setup

After logging in, open `/admin/account.php` and update the username, email, and password.
