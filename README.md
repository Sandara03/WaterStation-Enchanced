# AHY Water Station — Enhanced Final Project

Laravel 12 API + Vue 3 / TypeScript / Quasar. This revision improves the supplied project and adds login with staff roles, payment validation, duplicate-checkout protection, printable receipts, sale voids, expense tracking, stock adjustments, and activity history.

Start with this guide. See `FINAL_PROJECT_GUIDE.md` for the feature comparison and presentation flow, `MANUAL_ACCEPTANCE_CHECKLIST.md` for acceptance scenarios, and `VERIFICATION.md` for the checks actually performed.

## Requirements
- PHP 8.2+ with PDO SQLite (fresh demo) or PDO MySQL, plus mbstring, openssl, fileinfo, DOM/XML, curl, and zip.
- Composer 2.
- Node.js 22.22+ (Node 24 was used here) and npm.
- A modern browser. Internet access is needed for dependency installation; normal local use does not need a CDN.

## Fresh classroom demo on Windows
Extract the ZIP to a folder you can write to. Open PowerShell in that folder. The default example environment uses a new local SQLite database, which does not need MySQL.

```powershell
.\SETUP.ps1 -SeedDemo
cd backend
php artisan staff:create admin@example.test --role=admin
php artisan staff:create cashier@example.test --role=cashier
cd ..
```

The staff command prompts for a name and a password of at least 12 characters. There are no shared default credentials. If PowerShell blocks scripts, use the manual commands below; changing Windows policy is not necessary.

Start two terminals in the project folder:

```powershell
# Terminal 1
.\START_BACKEND.ps1
```

```powershell
# Terminal 2
.\START_FRONTEND.ps1
```

Open [AHY Water Station locally](http://127.0.0.1:9000) and sign in with the account you created. Both servers must remain running. Do not open frontend/index.html directly.

## Manual installation
```powershell
cd backend
composer install
Copy-Item .env.example .env  # Only when .env does not exist
New-Item -ItemType File database/database.sqlite  # Only for a fresh default SQLite setup
php artisan key:generate  # Only when creating a new .env with an empty APP_KEY
php artisan migrate
php artisan db:seed       # Optional sample products for a demo database
php artisan staff:create admin@example.test --role=admin
php artisan serve --host=127.0.0.1 --port=8000
```

In a second terminal:
```powershell
cd frontend
npm.cmd ci
npm.cmd run typecheck
npm.cmd run build
npm.cmd run dev
```

## Upgrade an existing MySQL project
1. Make a backup of your project and export the existing database. First test the upgrade on a separate copy of that database.
2. Copy your existing backend `.env` into the new backend folder. Keep its APP_KEY, MySQL database name, host, port, username, and password. Do not overwrite it with `.env.example` or regenerate a populated application's key.
3. Run `composer install`, `php artisan config:clear`, then `php artisan migrate`. Do **not** run `migrate:fresh` or `migrate:refresh`: these can erase data.
4. Provision an admin account with `php artisan staff:create your-email --role=admin`. Existing accounts receive the cashier role. To update an existing account explicitly, use `php artisan staff:create your-email --role=admin --reset`.
5. Run `npm.cmd ci` inside frontend, then typecheck and build. Start both servers.
6. Check old receipts, products, stock, and reports against your backup. Legacy orders can have missing payment metadata or product-name snapshots; the UI provides fallbacks. Stock movement history starts from the movements actually recorded; existing stock is not reconstructed retroactively.

For a new MySQL database, create an empty `ahy_water_station` database in phpMyAdmin, then configure these values in backend/.env before migration:
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ahy_water_station
DB_USERNAME=root
DB_PASSWORD=
```
Use your actual credentials and port. Never include `.env`, database dumps, or real customer details in a school submission ZIP.

## Roles and daily operations
| Operation | Cashier | Admin |
| --- | --- | --- |
| Dashboard, POS, sales, receipts | Yes | Yes |
| Customer create/edit/search/history | Yes | Yes |
| Delivery scheduling and progress | Yes | Yes |
| Read stock and movement history | Yes | Yes |
| Product management and stock adjustment | No | Yes |
| Void sales and delete unlinked customers | No | Yes |
| Reports, CSV export, expenses, activity log | No | Yes |
| Change own password and sign out | Yes | Yes |

Use Inventory to change stock with a reason. A physical-count correction sets the exact stock count. Product editing changes product details, price, alert level, or active status.

Cash must cover the sale total. GCash and bank transfer require an exact manually confirmed amount and a reference. No payment is processed by this app. A void preserves the sale record and returns stock once; arrange any real refund separately. Orders already dispatched or delivered cannot be voided.

New receipts preserve the product name at sale time. Use Sales & Receipts to reprint. Browser Print / Save PDF produces the receipt; check the actual printer's paper size and margins before your presentation. Receipts are labeled as project demonstrations.

## Build the frontend into Laravel (optional)
```powershell
.\BUILD_LOCAL.ps1
.\START_BACKEND.ps1
```
Open [the combined local application](http://127.0.0.1:8000). This copies compiled UI assets into backend/public/app; they are generated files and can be rebuilt. `php artisan serve` is a local development server, not a public production deployment.

## Verification commands
```powershell
cd backend
php artisan config:clear
php artisan test
cd ../frontend
npm.cmd run typecheck
npm.cmd run build
```
Backend tests use an in-memory SQLite database through phpunit.xml. Check that no cached application configuration points at a live database before running tests. MySQL migration and concurrent-session checks are listed separately in the acceptance checklist.

## Troubleshooting
- **Connection refused / failed request:** keep Laravel on 127.0.0.1:8000 and Quasar on 127.0.0.1:9000. The dev proxy forwards /api to Laravel.
- **401 after a while:** sessions expire after eight hours. Sign in again. Logout removes the current token; changing a password revokes all of that account's tokens.
- **403:** the action requires the admin role; changing browser navigation does not change server permissions.
- **409 on checkout:** a checkout key was reused with different details. Review Sales & Receipts before starting a new transaction.
- **Unconfirmed checkout:** use Retry checkout. The exact pending submission stays in the current browser tab until confirmed or explicitly rejected. Keep the tab open while resolving a network failure.
- **No application encryption key:** for a new installation with an empty key, run `php artisan key:generate`.
- **Database/table not found:** inspect `.env`, run config:clear and migrate, and check MySQL/SQLite configuration.
- **Storage/cache error:** ensure backend/storage and backend/bootstrap/cache exist and are writable by your PHP process.
- **PowerShell blocks npm.ps1:** use `npm.cmd` as in these instructions.

## Deliberate scope
Reports show sales less recorded expenses, not accounting profit. No payment gateway, SMS, full accounting, supplier purchasing, container-return ledger, web staff management, or automatic cloud backup is included. The activity log is read-only in the app but not tamper-proof against database administrators. The app does not submit transactions offline.

Your original ZIP is unchanged. Validate this revision against your adviser’s rubric and the real demonstration environment before submitting it.
