# CSV → Shopify Product Import System

A Laravel 12 application that lets a user upload a Shopify-formatted product
CSV, processes it **asynchronously via a queued job**, imports each row into
Shopify (creating new products or updating existing ones), and shows live
import status on a dashboard.

Built for the "CSV to Shopify Product Import System" technical assessment.

---

## 1. What's implemented

| Requirement | Where |
|---|---|
| Upload form + client-side validation | `resources/views/uploads/create.blade.php` (JS checks type/size before submit) + `StoreUploadRequest` (server-side re-validation) |
| Async CSV processing | `app/Jobs/ProcessCsvImport.php`, dispatched from `UploadController@store` onto the `database` queue |
| CSV parsing / column mapping | `ProcessCsvImport::createProductImportRow()` maps Shopify's own CSV export column names (Handle, Title, Variant SKU, Variant Price, Image Src, …) onto the `product_imports` table |
| Shopify integration | `app/Services/ShopifyRestService.php` (REST, default) and `app/Services/ShopifyGraphQLService.php` (**bonus**, GraphQL) — toggle with `SHOPIFY_API_MODE` in `.env` |
| Create-or-update | Both services look the product up by `handle` first; if found they `PUT`/`productUpdate`, otherwise `POST`/`productCreate` (**bonus**) |
| Dashboard | `/dashboard` lists every upload with counts per status; `/dashboard/uploads/{id}` shows every row + error message; polls a JSON status endpoint every 3s while a job is running |
| Logging (bonus) | `app/Services/ImportLogger.php` writes every event to **both** the `import_logs` table and `storage/logs/shopify_import.log`; viewable at `/dashboard/logs` |
| Error notifications (bonus) | `app/Notifications/ImportFailedNotification.php` fires when an upload finishes with failed rows, or crashes outright — currently wired to a log channel, trivially swappable for Mail/Slack |
| Migrations | `uploads`, `product_imports`, `import_logs` (see `database/migrations/2025_01_01_*`) |
| Models + relationships | `Upload` hasMany `ProductImport` hasMany `ImportLog` (see `app/Models`) |

### Not wired up
- No authentication — the assessment doesn't call for user accounts, so the
  dashboard is open. Add Laravel Breeze/Fortify in front of it if you need one.
- GraphQL variant price/image mutations are simplified for demo purposes in
  `ShopifyGraphQLService::updateVariantAndImage()` — see the comment there
  for what a production version needs (fetching the default variant ID via
  `productVariantsBulkUpdate`). REST mode is fully functional end-to-end.

---

## 2. Requirements

- PHP 8.2+
- Composer 2
- MySQL 8 (or SQLite — see note below)
- A Shopify store with a **custom app** access token (Admin API, scopes
  `read_products` + `write_products`)

> This container had no PHP/Composer/internet access, so the code was
> hand-written to the Laravel 12 skeleton conventions rather than generated
> with `laravel new` + `composer require`. Run `composer install` locally —
> the `composer.json` pins the same package versions Laravel 12 ships with.

---

## 3. Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database — pick ONE:
#    a) MySQL (default in .env.example): create the DB first
mysql -u root -e "CREATE DATABASE shopify_import"
#    b) or switch to SQLite for a zero-config setup:
#       DB_CONNECTION=sqlite   (and delete/comment the other DB_* lines)
#       touch database/database.sqlite

# 4. Add your Shopify credentials to .env
#    SHOPIFY_STORE_DOMAIN=your-store.myshopify.com
#    SHOPIFY_ACCESS_TOKEN=shpat_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
#    SHOPIFY_API_MODE=rest        # or "graphql" for the bonus integration

# 5. Migrate
php artisan migrate

# 6. Link storage (uploaded CSVs are stored on the public disk)
php artisan storage:link

# 7. Run the app
php artisan serve

# 8. Run the queue worker IN A SEPARATE TERMINAL — this is what makes
#    processing asynchronous. Nothing gets imported to Shopify without this running.
php artisan queue:work
```

No `npm install` / frontend build step is needed — the UI uses the Tailwind
CDN build directly in the Blade layout, so there's no Vite step to run.

Open **http://localhost:8000** — it redirects straight to the dashboard.

---

## 4. Testing the app

A sample CSV (`sample-products.csv`, the same one this project was built
against) is included in the project root — 10 realistic products with
images, variants, and pricing already in Shopify's own export format.

1. Go to **New upload**, drop in `sample-products.csv`.
2. You're redirected to that upload's detail page immediately (upload
   itself is synchronous — only the Shopify import is queued).
3. With `php artisan queue:work` running, refresh (or just wait — the page
   polls automatically) to watch rows flip from `pending` → `processing` →
   `successful`/`failed`.
4. Check **Event log** in the nav for a full timeline of what happened,
   including any Shopify API error messages.
5. To see the "update existing product" path: re-upload the same CSV a
   second time. Since the `Handle` column is unique per row, the second
   pass should mark every row `action = updated` instead of `created`.
6. To see failure handling, temporarily set `SHOPIFY_ACCESS_TOKEN` to
   something invalid, or delete the `Title` from a CSV row — both produce a
   `failed` row with an `error_message`, without crashing the whole job.

### Automated tests
The assessment focuses on a working demo + video, so no PHPUnit suite is
included, but the architecture is test-friendly: `ShopifyRestService` and
`ShopifyGraphQLService` are constructor-injected into the job (see
`ProcessCsvImport::handle()`), so a feature test can bind a fake/mock in
the container and assert on `product_imports` rows without hitting the
real Shopify API.

---

## 5. Assumptions & design decisions

- **One job per upload, looping rows in-process** (rather than one queued
  job per row) — simpler to reason about and keeps `uploads.processed_rows`
  trivial to update, at the cost of a single row's failure not being
  independently retryable. For very large CSVs you'd chunk this into a
  `Bus::batch()` of per-row jobs instead.
- **Products are matched by `Handle`** for the create-vs-update decision,
  since Shopify enforces unique handles and the sample CSV always includes
  one. If a CSV omits handles, one is derived from the title with `Str::slug()`.
- **CSV column names are matched exactly to Shopify's own export format**
  (`Handle`, `Title`, `Body HTML`, `Variant SKU`, `Variant Price`, `Image
  Src`, etc.) since that's what the provided sample file uses and what
  merchants would most commonly export from Shopify itself.
- **Storage disk**: uploaded CSVs are kept on the `public` disk under
  `csv_uploads/` so any queue worker process can re-read them; nothing
  about them is actually served to the public — `storage:link` is only
  needed because the `public` disk is convenient, not because the CSVs are
  meant to be browsable.
- **Notifications** use a small custom `log` notification channel (see
  `app/Notifications/Channels/LogChannel.php`) instead of requiring Mail/Slack
  credentials just to demonstrate the notification system — swap `via()`
  in `ImportFailedNotification` for `['mail']` or `['slack']` in production.

---

## 6. Project structure

```
app/
  Http/Controllers/UploadController.php     # handles the upload form
  Http/Controllers/DashboardController.php  # dashboard + status polling + logs
  Http/Requests/StoreUploadRequest.php      # server-side upload validation
  Jobs/ProcessCsvImport.php                 # the async worker
  Services/ShopifyRestService.php           # Shopify Admin REST client
  Services/ShopifyGraphQLService.php        # Shopify Admin GraphQL client (bonus)
  Services/ImportLogger.php                 # writes to DB + log file
  Notifications/ImportFailedNotification.php
  Models/{Upload,ProductImport,ImportLog}.php
database/migrations/                        # uploads, product_imports, import_logs
resources/views/
  layouts/app.blade.php
  uploads/create.blade.php                  # upload form
  dashboard/{index,show,logs}.blade.php     # dashboard + detail + log viewer
routes/web.php
sample-products.csv                          # ready-to-use test file
```

---

## 7. Submission checklist (per the assessment)

- [ ] Push this to a GitHub repo (`git init && git add . && git commit -m "Initial commit"`)
- [x] README with setup, overview, assumptions, testing — this file
- [ ] Record a short video: upload the sample CSV, start `queue:work`, show
      the dashboard updating live, open a product in Shopify Admin to prove
      it landed, then re-upload to show the update path
