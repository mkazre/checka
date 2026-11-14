# Going live: CheckA+ SaaS on checkaplus.co.za

This deploys the `saas` branch to the **checkaplus.co.za** cPanel account. The current single-company CheckA+ on `main` is not touched.

**When you're done:**
- `checkaplus.co.za` / `www` show the marketing website.
- `app.checkaplus.co.za` is your admin panel, plus sign-up and payment pages.
- `<company>.checkaplus.co.za` is each customer's workspace.

Everything runs from **one folder and one database**. No shell is needed: every server command is a one-off **cPanel → Cron Jobs** entry, set to "every minute" and deleted after it has run once.

In the commands below, replace `USER` with the cPanel username. Use the PHP path `/opt/cpanel/ea-php84/root/usr/bin/php`. If PHP 8.4 isn't installed, use your server's PHP 8.2+ path.

---

## 1. DNS and domains (cPanel)

1. **Zone Editor** for checkaplus.co.za: add an **A record** `*.checkaplus.co.za` pointing to the server IP. This is the wildcard that gives every workspace its own address.
2. **Domains**: set the document root of `checkaplus.co.za` to `/home/USER/checka_app/public`.
3. **Domains → Create a new domain**: add `app.checkaplus.co.za` and `*.checkaplus.co.za`, both with document root `/home/USER/checka_app/public`. `www` normally follows the main domain.
4. **SSL/TLS Status → Run AutoSSL.** The wildcard certificate (`*.checkaplus.co.za`) only issues if the domain's DNS is hosted on this cPanel server. If DNS is elsewhere (e.g. Cloudflare or the registrar), either:
   - move the nameservers to the host, or
   - ask the host for a wildcard certificate.

## 2. Database

**MySQL Databases:**
1. Create a database, e.g. `USER_checkaplus`.
2. Create a user and add it to the database with **ALL PRIVILEGES**.

## 3. Code

1. Run this one-off cron job to clone the `saas` branch. It's a private repository, so use a GitHub token with read access. Delete the job after it has run, because it contains the token.

   ```
   cd /home/USER && git clone -b saas https://<TOKEN>@github.com/mkazre/checka.git checka_app > /home/USER/clone.log 2>&1
   ```

2. Install the libraries with this one-off job:

   ```
   cd /home/USER/checka_app && /opt/cpanel/ea-php84/root/usr/bin/php /opt/cpanel/composer/bin/composer install --no-dev --no-interaction --optimize-autoloader > /home/USER/composer.log 2>&1
   ```

   If `/opt/cpanel/composer` doesn't exist, zip the `vendor/` folder from your PC and upload it with File Manager.

3. In **File Manager**, create `/home/USER/checka_app/.env` with:

   ```
   CI_ENVIRONMENT = production
   app.baseURL = 'https://app.checkaplus.co.za/'
   app.indexPage = ''
   database.default.hostname = localhost
   database.default.database = USER_checkaplus
   database.default.username = USER_checkaplususer
   database.default.password = '<db password>'
   database.default.DBDriver = MySQLi
   database.default.DBPrefix =
   security.csrfProtection = 'session'
   encryption.key = hex2bin:<64 hex characters>
   ```

   For `encryption.key`, generate 64 random hex characters, e.g. a SHA-256 hash of a long random phrase.

## 4. Install the platform (one-off cron job)

```
cd /home/USER/checka_app && /opt/cpanel/ea-php84/root/usr/bin/php spark platform:install --domain checkaplus.co.za --email you@yourdomain.co.za --password 'ChooseAStrongPassword' --name "Your Name" > /home/USER/install.log 2>&1
```

This creates the platform tables and your owner login, and switches on multi-workspace mode. Delete the job straight away because it contains your password. Then sign in at **https://app.checkaplus.co.za/login** and change the password under **My account**.

## 5. Permanent cron jobs

Both commands are also shown, ready to copy, under **Settings → General** in the admin panel.

| How often | What it does | Command |
|---|---|---|
| Every minute | Builds new workspaces | `cd /home/USER/checka_app && for s in $(/opt/cpanel/ea-php84/root/usr/bin/php spark platform:pending --no-header); do CHECKA_TENANT=$s /opt/cpanel/ea-php84/root/usr/bin/php spark tenant:provision --no-header; done >> /home/USER/provision.log 2>&1` |
| Every hour | Subscription robot: invoices, reminders, card renewals, pause/reactivate, retention | `cd /home/USER/checka_app && /opt/cpanel/ea-php84/root/usr/bin/php spark platform:billing --no-header >> /home/USER/billing.log 2>&1` |

## 6. Configure everything in the admin panel

1. **Settings → General**: platform name, support email, VAT rate, and your company details and VAT number for invoices.
2. **Settings → Email sending**: choose **Server mail**, or **Brevo** for best deliverability (outbound SMTP is blocked on this host). Then click **Send test**.
3. **Settings → Payment gateways**: switch on and paste the live keys for Paystack, PayFast and Ozow, plus your bank details for EFT. Paste these notify URLs into each gateway's dashboard:
   - Paystack webhook: `https://app.checkaplus.co.za/webhooks/paystack`
   - PayFast ITN: `https://app.checkaplus.co.za/webhooks/payfast`
   - Ozow notify: `https://app.checkaplus.co.za/webhooks/ozow`
4. **Settings → Billing rules**: reminders, grace period, auto-pause, retention and trial rules.
5. **Settings → Email templates**: wording of every customer email. Use **Send me a test** to check them.
6. **Packages**: create your real packages (names, prices, modules, limits) and tick **Show on the public pricing page**. The website's pricing and sign-up pages update instantly.
7. **Settings → Website**: add-ons, FAQ, enterprise box, and where contact-form enquiries go.

## 7. Smoke test

1. Open https://checkaplus.co.za/pricing. Your packages should show.
2. Sign up for a trial with a test company. The "Setting up…" page should switch to "ready" within a minute. Then sign in at `test.checkaplus.co.za`.
3. In the admin panel: **Workspaces**, then the test company, then **Log in as admin**.
4. Make a test payment, ideally with gateways in sandbox/test mode first. Then cancel the test workspace.

## Updating later

Use the same pattern as today's deploy, but with the `saas` branch:

```
( umask 022 && cd /home/USER/checka_app && git fetch origin saas && git checkout -f -B saas origin/saas && git log -1 --oneline ) > /home/USER/gitpull.log 2>&1
```

Then, if the update includes database changes, migrate the platform and every workspace:

```
cd /home/USER/checka_app && /opt/cpanel/ea-php84/root/usr/bin/php spark migrate -n Platform && for s in $(ls writable/platform/tenants | sed 's/\.json$//'); do CHECKA_TENANT=$s /opt/cpanel/ea-php84/root/usr/bin/php spark migrate --all; done > /home/USER/migrate.log 2>&1
```

## Moving the existing CheckA+ data in (optional, later)

The current CheckA+ runs on a different server and database. To bring it in as a workspace:

1. Export its database in phpMyAdmin and import it into the new database under a table prefix, e.g. `w0_`.
2. Copy `public/assets/img` uploads into `writable/tenants/<slug>/` and `public/tenant-files/<slug>/`.
3. In the panel, create a workspace with **Data → Attach existing tables** and enter that prefix.

Ask Claude to do this step with you. Until then, the existing app keeps running exactly as it is.
