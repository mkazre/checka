# Checka by WheelsTurn

A vehicle-inspection web app built on **CodeIgniter 4**. Capture inspections with
photos against a fully editable checklist, view a world-class report, and download
a branded PDF. Ships with a REST API for a future React/Flutter client.

- **Auth:** CodeIgniter Shield — `admin` + `mechanic` roles, plus API access tokens.
- **PDF:** mPDF (pure PHP — works on shared cPanel).
- **Front end:** server-rendered views + Alpine.js via CDN (no Node build step).
- **DB:** MySQL/MariaDB.

---

## What's in the box

- 20-section WheelsTurn default checklist (seeded), with RAG ratings, condition
  score, verdict, tyre-health (tread + DOT age), body-damage map, findings, QR verify.
- **Form builder** (admin): add/edit/delete sections and fields
  (rating, text, number, radio, dropdown, checkbox, photo, signature, date). Tag a
  field with a *role* (`tread_depth`, `dot_code`, `body_zone_*`, …) and the report
  intelligence picks it up automatically.
- Dashboard: view / capture / edit / delete inspections.
- REST API under `/api/v1` (token auth).

---

## Local vs server

You do **not** run this on the Windows laptop. Author code locally, commit, and
deploy to the cPanel **subdomain** over SSH. PHP/Composer/MySQL/PDF all run on the
server.

---

## First-time deploy (cPanel + SSH)

### 1. Create the subdomain
In cPanel → **Domains / Subdomains**, create e.g. `checka.yourdomain.co.za`.
Set its **Document Root** to the repo's **`public/`** folder (recommended). Your
existing Laravel app is untouched — different subdomain, different docroot.

### 2. Create the database
cPanel → **MySQL Databases**: create a DB + user, add the user to the DB with
**All Privileges**. Note the names (usually prefixed with your cPanel user).

### 3. Clone the repo (SSH)
```bash
cd ~
git clone <your-repo-url> checka
cd checka
```
If the subdomain docroot must be inside `checka/public`, point it there in cPanel.

### 4. Install dependencies
```bash
composer install --no-dev
```
(If `composer` isn't global on your host, use `php composer.phar install --no-dev`.)

### 5. Configure environment
```bash
cp env .env
```
Edit `.env` and set:
```
CI_ENVIRONMENT = production
app.baseURL = 'https://checka.yourdomain.co.za/'
database.default.hostname = localhost
database.default.database = cpaneluser_checka
database.default.username = cpaneluser_checka
database.default.password = 'your-db-password'
```
Then generate the encryption key:
```bash
php spark key:generate
```

### 6. Migrate + seed
```bash
php spark migrate --all          # app + Shield + Settings tables
php spark db:seed App\\Database\\Seeds\\DefaultTemplateSeeder
```

### 7. Create the first admin
```bash
php spark checka:admin --email you@wheelsturn.co.za --username admin --password 'ChangeMe123'
```
(Or run `php spark checka:admin` and answer the prompts.)

### 8. Permissions
```bash
chmod -R 775 writable
```

Visit `https://checka.yourdomain.co.za/` → log in → you're on the dashboard.

---

## Updating (each release)
```bash
cd ~/checka
git pull
composer install --no-dev
php spark migrate --all
```

---

## REST API (for the future mobile / React app)

Get a token:
```
POST /api/v1/auth/token
{ "email": "...", "password": "...", "device": "flutter-app" }
→ { "token": "...", "user": {...} }
```
Then send `Authorization: Bearer <token>` to:
```
GET  /api/v1/me
GET  /api/v1/templates            GET /api/v1/templates/{id}
GET  /api/v1/vehicles             POST/PUT/DELETE /api/v1/vehicles
GET  /api/v1/inspections          POST /api/v1/inspections
GET  /api/v1/inspections/{uid}    PUT /api/v1/inspections/{uid}
GET  /api/v1/inspections/{uid}/report
```
The report JSON matches the on-screen report data model exactly.

---

## Migrating to the home domain later
Everything host-specific lives in `.env`. On the new host: clone the repo,
`composer install --no-dev`, new `.env` (new baseURL + DB), `php spark migrate --all`,
copy the `writable/uploads/` directory across, done.

---

## Project layout
```
app/
  Config/         framework + Shield + Checka config
  Controllers/    web + Api/V1
  Models/         Template, Section, Field, Vehicle, Inspection, Value, Photo
  Services/       InspectionIntelligence (scoring), PdfService (mPDF)
  Database/       Migrations + DefaultTemplateSeeder
  Views/          layout, dashboard, capture, reports (web + pdf), templates builder…
  Commands/       checka:admin
public/           web root (index.php)
writable/uploads/ inspection photos & signatures
```
