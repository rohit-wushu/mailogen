# Deploying Eventogen Mailer to Hostinger

A step-by-step guide to go live on Hostinger shared hosting. Plain PHP 8.2 + MySQL — no Composer, Node, or Docker required.

> **Why a real server matters:** open/click tracking, unsubscribe links, and scheduled sending only work on a public HTTPS domain with a running cron job. They cannot work on `localhost`.

---

## 1. Prerequisites

- A Hostinger plan with PHP **8.1+** and MySQL.
- A domain or subdomain (e.g. `mailer.yourdomain.com`).
- At least one SMTP account to send through (Gmail/Google Workspace App Password, Brevo, SES, etc.) — added later from inside the app.

---

## 2. Create the database

1. Hostinger panel → **Databases → MySQL Databases**.
2. Create a database + user, note the **DB name, user, password, host** (host is usually `localhost`).
3. Open **phpMyAdmin** → select the new database → **Import** → upload `database/schema.sql` → **Go**.
   - This creates all tables and seeds 3 plans + an admin user.
   - **Default admin:** `admin@eventogen.com` / `Admin@123` — **change this password immediately** after first login.

---

## 3. Upload the files

1. Upload the project (everything **except** `config/config.php` and `config/installed.lock`) to your hosting, e.g. `~/domains/yourdomain.com/emailer-tool/`.
2. **Document root:** point your domain/subdomain's document root to the project's **`/public`** folder (Hostinger → Website → *Change website root*). This is the most secure layout.
   - If you cannot change the docroot, the root `.htaccess` already redirects requests into `/public` and blocks access to internal folders.

---

## 4. Configure the app

1. Copy `config/config.example.php` → `config/config.php`.
2. Edit `config/config.php` and set:
   - `APP_ENV` → `production`
   - `APP_URL` → your real `https://` domain (no trailing slash)
   - `DB_HOST / DB_NAME / DB_USER / DB_PASS`
   - `APP_KEY` and `CRON_KEY` → generate fresh values:
     ```bash
     php -r "echo bin2hex(random_bytes(16)).PHP_EOL;"   # APP_KEY
     php -r "echo bin2hex(random_bytes(12)).PHP_EOL;"   # CRON_KEY
     ```
   - (Optional) `RAZORPAY_KEY_ID` / `RAZORPAY_KEY_SECRET` for live billing. Leave blank to run billing in **demo mode**.

> ⚠️ **APP_KEY encrypts every user's stored SMTP/IMAP passwords.** Use a unique value and never share or commit it. Changing it later makes existing stored passwords unreadable.

3. Make sure `uploads/` is writable (755/775).

---

## 5. Set up the cron jobs

Hostinger panel → **Advanced → Cron Jobs**. Add these (CLI form; adjust the absolute path):

| Schedule | Command |
|---|---|
| Every 5 min | `php /home/USER/domains/yourdomain.com/emailer-tool/cron/send-emails.php` |
| Every 15 min | `php /home/USER/domains/yourdomain.com/emailer-tool/cron/automations.php` |
| Every 15 min | `php /home/USER/domains/yourdomain.com/emailer-tool/cron/ab-test.php` |
| Every 30 min | `php /home/USER/domains/yourdomain.com/emailer-tool/cron/bounces.php` |
| Daily | `php /home/USER/domains/yourdomain.com/emailer-tool/cron/warmup.php` |

`send-emails.php` rolls over daily SMTP limits, launches due scheduled campaigns, and drains the send queue. `ab-test.php` decides subject-line A/B tests once their test window elapses and releases the holdout batch with the winning subject. `warmup.php` ramps up daily sending limits for SMTP accounts/domains still in warm-up. The others run follow-up automations and read bounces/replies (bounces needs the PHP `imap` extension; it self-skips if absent).

**URL-based alternative** (if CLI cron isn't available) — append your `CRON_KEY`:
```
https://mailer.yourdomain.com/cron/send-emails.php?key=YOUR_CRON_KEY
```

---

## 6. Go live & verify

1. Visit `https://mailer.yourdomain.com` → sign in as admin → **change the password**.
2. **SMTP Accounts** → add your sending account → **Test Connection**.
3. **Contacts** → import a small CSV.
4. **Campaigns** → create one → **Send test** to yourself → confirm it arrives.
5. Send/schedule a real campaign and confirm the cron drains the queue (check **Reports** for opens/clicks — these now work because you're on a public URL).

---

## 7. Production hardening checklist

- [ ] `APP_ENV = production` (hides stack traces)
- [ ] Fresh unique `APP_KEY` and `CRON_KEY` (not the dev defaults)
- [ ] Admin password changed from `Admin@123`
- [ ] `config/config.php` is **not** web-accessible (docroot = `/public`, and `.htaccess` blocks it)
- [ ] HTTPS enabled (Hostinger free SSL) so the session cookie is sent `secure`
- [ ] `uploads/` writable but not executable
- [ ] Database backups scheduled (Hostinger auto-backups or a periodic dump)

---

## 8. Upgrading later

To deploy code changes: upload the changed files (never overwrite `config/config.php`). If a release adds DB columns/tables, run the matching file in `database/migrations/` via phpMyAdmin. New installs already include everything via `schema.sql`.

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| Blank/500 page | Temporarily set `APP_ENV=development` to see the error; check PHP error log. |
| Inner pages 404 | Ensure `mod_rewrite` is on and docroot is `/public` (or root `.htaccess` is present). |
| No emails sending | Confirm the `send-emails.php` cron runs; check **Reports** and `system_logs`; verify the SMTP account passes **Test Connection**. |
| Tracking shows nothing | `APP_URL` must be your real https domain; recipients must load images. |
| "Too many attempts" on login | Rate limiter — wait a few minutes (per-IP/email throttle). |
