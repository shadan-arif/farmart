# Rezgo Connector – Botble/Farmart Plugin

A standalone, **update-proof** Botble plugin that integrates the [Rezgo Booking API](https://www.rezgo.com/api-documentation/) with the Farmart Laravel eCommerce system.

---

## Overview

When a customer completes checkout on the Farmart storefront, the Rezgo Connector automatically transmits the booking to Rezgo in real-time via the Rezgo XML Commit API. No core Botble files are modified — all integration is done through event listeners and Botble's hook system.

---

## Features

| Feature | Description |
|---------|-------------|
| **Real-time Order Sync** | Intercepts `OrderPlacedEvent` and POSTs an XML commit to Rezgo |
| **Encrypted Credentials** | CID and API Key stored via Laravel `Crypt::encryptString` through the Botble `Setting` facade |
| **Dedicated Audit Log** | All requests and responses logged to `storage/logs/rezgo-sync.log` |
| **Admin Settings UI** | Dashboard page to manage credentials with live Test Connection |
| **Non-blocking** | Errors are caught and logged; they never interrupt the customer checkout |
| **Update-Proof** | Hooks into Botble's event system — survives all core updates |
| **Zero Core Changes** | No modifications to any existing Botble or Farmart files |

---

## Requirements

- PHP 8.2+
- Laravel 10.x / 11.x (Botble Farmart environment)
- Botble CMS 7.3.0+
- `botble/ecommerce` plugin **activated**
- A valid [Rezgo account](https://www.rezgo.com/) with CID and API Key

---

## Installation

### Step 1 — Copy Plugin Files

Place the `rezgo-connector` folder into:

```
platform/plugins/rezgo-connector/
```

### Step 2 — Install Guzzle Dependency

From the project root (`main/`):

```bash
composer require guzzlehttp/guzzle
```

### Step 3 — Run the Database Migration

```bash
php artisan migrate
```

This creates the `rezgo_meta` table.

### Step 4 — Clear and Cache Config

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Step 5 — Activate the Plugin

1. Log in to the **Farmart Admin Panel** (e.g. `http://yourdomain.com/admin`)
2. Navigate to **Plugins → All Plugins**
3. Find **Rezgo Connector** in the list and click **Activate**

> The plugin migration runs automatically on activation.

---

## Configuration

1. After activation, go to **Rezgo Connector** in the left sidebar
2. Enter your **Rezgo CID (Transcode)** — found in Rezgo at *Settings → API Access*
3. Enter your **Rezgo API Key** — created in Rezgo at *Settings → API Access → Create API Key*
4. Toggle **Enable Rezgo Sync** on
5. Click **Save Settings**
6. Click **Test Connection** — you should see your Rezgo company name returned

### Getting your Rezgo API Key

1. Log in to Rezgo
2. Go to **Settings → API Access**
3. Click **Create API Key**
4. (Optional) Restrict by IP address for security
5. Click **Create Key** and copy the key

---

## Linking Products to Rezgo

To sync a product with Rezgo, you must map the Farmart product to a Rezgo **service UID**. There are two ways:

### Option A — Use the Test Seeder

Run the provided seeder to create a test product pre-linked to Rezgo UID `72547`:

```bash
php artisan db:seed --class="Botble\\RezgoConnector\\Database\\Seeders\\RezgoTestProductSeeder"
```

> **Important:** Update the `rezgo_uid` value in the `rezgo_meta` table to match a real Rezgo service UID from your account before live testing.

### Option B — Manual Database Insert

```sql
-- Link an existing product (ID = 5) to Rezgo UID "12345"
INSERT INTO rezgo_meta (entity_type, entity_id, meta_key, meta_value, created_at, updated_at)
VALUES ('product', 5, 'rezgo_uid', '12345', NOW(), NOW());

-- Set the default booking date for that product
INSERT INTO rezgo_meta (entity_type, entity_id, meta_key, meta_value, created_at, updated_at)
VALUES ('product', 5, 'rezgo_book_date', '2026-06-01', NOW(), NOW());
```

---

## Step-by-Step Testing Guide

### Phase 1 — Setup Verification

```bash
# 1. Confirm the rezgo_meta table exists
php artisan tinker --execute="var_dump(Schema::hasTable('rezgo_meta'));"

# 2. Confirm settings can be read
php artisan tinker --execute="echo setting('rezgo_cid', 'NOT SET');"

# 3. Check plugin is registered
php artisan tinker --execute="echo is_plugin_active('rezgo-connector') ? 'ACTIVE' : 'INACTIVE';"
```

### Phase 2 — Admin Panel Test

1. Open your admin panel → click **Rezgo Connector** in the sidebar
2. Enter your CID and API Key → **Save Settings**
3. Click **Test Connection**
   - ✅ **Expected**: Green alert: "Connected successfully to: [Your Company Name]"
   - ❌ **If error**: Check CID/API Key; see `storage/logs/rezgo-sync.log` for details

### Phase 3 — End-to-End Checkout Test

1. **Seed the test product** (if you haven't already):
   ```bash
   php artisan db:seed --class="Botble\\RezgoConnector\\Database\\Seeders\\RezgoTestProductSeeder"
   ```

2. **Verify the Rezgo UID** in the database:
   ```bash
   php artisan tinker --execute="print_r(DB::table('rezgo_meta')->where('meta_key','rezgo_uid')->get()->toArray());"
   ```

3. **Update the UID** if needed:
   ```sql
   UPDATE rezgo_meta SET meta_value = 'YOUR_REAL_REZGO_UID'
   WHERE entity_type = 'product' AND meta_key = 'rezgo_uid';
   ```

4. **Open the storefront**, find the test product `[TEST] Rezgo Test Tour`, add it to cart

5. **Complete the checkout** (use any payment method)

6. **Check the sync log**:
   ```bash
   tail -50 storage/logs/rezgo-sync.log
   ```
   You should see `COMMIT_REQUEST` and `COMMIT_RESPONSE` entries.

7. **Verify the Rezgo transaction number was stored**:
   ```bash
   php artisan tinker --execute="print_r(DB::table('rezgo_meta')->where('meta_key','LIKE','trans_num%')->get()->toArray());"
   ```

8. **Verify in Rezgo**: Log in to Rezgo and check *Bookings* — you should see the new booking.

---

## File Structure

```
platform/plugins/rezgo-connector/
├── plugin.json                         # Plugin manifest
├── composer.json                       # Requires guzzlehttp/guzzle
├── README.md                           # This file
├── src/
│   ├── Plugin.php                      # Activated/remove lifecycle
│   ├── Models/
│   │   └── RezgoMeta.php               # Eloquent model for rezgo_meta
│   ├── Providers/
│   │   ├── RezgoConnectorServiceProvider.php   # Main service provider
│   │   ├── EventServiceProvider.php            # OrderPlacedEvent binding
│   │   └── HookServiceProvider.php             # Admin menu registration
│   ├── Services/
│   │   └── RezgoApiService.php         # Guzzle-based Rezgo API client
│   ├── Listeners/
│   │   └── OrderPlacedListener.php     # Intercepts checkout, commits to Rezgo
│   └── Http/
│       └── Controllers/
│           └── RezgoSettingsController.php   # Admin settings CRUD
├── database/
│   ├── migrations/
│   │   └── 2026_03_14_000001_create_rezgo_meta_table.php
│   └── seeders/
│       └── RezgoTestProductSeeder.php
├── routes/
│   └── web.php                         # Admin routes
└── resources/
    ├── views/
    │   └── settings.blade.php          # Admin settings UI
    └── lang/
        └── en/
            └── rezgo.php               # English translations
```

---

## Database Schema

### `rezgo_meta`

| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT (PK) | Auto-increment |
| `entity_type` | VARCHAR(50) | `product` or `order` |
| `entity_id` | BIGINT | ID from `ec_products` or `ec_orders` |
| `meta_key` | VARCHAR(100) | `rezgo_uid`, `rezgo_book_date`, `trans_num_product_{id}` |
| `meta_value` | TEXT | The stored value |
| `created_at` | TIMESTAMP | — |
| `updated_at` | TIMESTAMP | — |

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Plugin not visible in admin | Run `php artisan config:clear && php artisan cache:clear` |
| "Class not found" error | Run `composer dump-autoload` from the `main/` directory |
| Settings save fails | Ensure `rezgo_meta` table exists: `php artisan migrate` |
| Test Connection returns error | Verify CID and API Key; check IP restrictions in Rezgo |
| Commit not firing on checkout | Check `rezgo_enabled` setting is `1`; check `rezgo_uid` is set in `rezgo_meta` |
| Rezgo returns error in log | Check the `COMMIT_RESPONSE` entry in `rezgo-sync.log` for the Rezgo error message |

---

## Security

- **API Key** is encrypted with `Crypt::encryptString()` (Laravel AES-256-CBC) before storage
- **Credentials** are never exposed in view templates — the key field is always empty on page load
- **Logs** do not contain the raw API key

---

## Rezgo API Reference

- Base URL: `https://api.rezgo.com/xml` (XML commit) / `https://api.rezgo.com/json` (read-only queries)
- Commit instruction requires: `transcode`, `key`, `booking` (date, UID, quantities), `payment` (customer info)
- Full docs: [https://www.rezgo.com/api-documentation/](https://www.rezgo.com/api-documentation/)

---

## License

Custom development for internal use. Not for redistribution.
