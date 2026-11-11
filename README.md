# AHY Water Station — iPhone home-screen web app 2.2.2

For the optional free Render deployment and an Android build without the server-address panel, see **RENDER_DEPLOYMENT.md**. The local commands below still work.

Laravel 12 API with a Vue 3, TypeScript, and Quasar interface for staff access, POS, inventory, receipts, customers, deliveries, expenses, management reports, and activity history.

For iPhone installation from Safari on the same Wi-Fi, read **IPHONE_INSTALLATION.md**. This release adds the home-screen manifest, Apple touch icon, launch metadata, and an installation-help dialog. It is a web app; no .ipa, Apple signing profile, or App Store submission is included. The previously tested Android APK remains version 2.2.1.

Android APK support uses Capacitor 8. Read **ANDROID_APP_GUIDE.md** for installing the APK, connecting the phone to Laravel, rebuilding with Gradle, and the remaining device checks. The web commands below are unchanged.

## Administrator login

- Email: **Admin@Email.com**
- Password: **Admin@123456**

The administrator is included in `backend/database/seeders/DemoAdminSeeder.php`. One-time setup seeds it automatically into a fresh local database, with a hashed password. No registration, account creation command, or password prompt is needed.

## One-time setup

Extract this updated ZIP into a new folder and open **AHY_WaterStation_Final** in VS Code. Install PHP 8.2+, Composer 2, and Node.js 22.22+ first. Enable PHP's PDO SQLite extension. Internet access is needed to install dependencies.

From the project root:

```powershell
cd backend
composer run setup
```

This installs backend and frontend dependencies, creates a missing .env with a private key, creates the default SQLite database, migrates, and seeds the admin plus four water products. Existing keys, records, passwords, and stock are preserved. Setup is for APP_ENV=local.

## Start the website

Terminal 1, starting from the project root:

```powershell
cd backend
php artisan serve
```

Terminal 2, starting from the project root:

```powershell
cd frontend
npm run dev
```

Open **http://127.0.0.1:9000** and sign in using the credentials above. Laravel runs at **http://127.0.0.1:8000**; the frontend forwards /api requests to it. Keep both terminals open. Ctrl+C stops each server. On later runs, only these two start commands are needed.

## Fix the screenshot errors

- **Missing vendor/autoload.php:** backend dependencies were not installed. Run `composer run setup` in backend.
- **quasar is not recognized during npm run dev:** frontend dependencies were not installed. Setup installs them; alternatively, run `npm install` in frontend. Use `npm run dev` to find the local Quasar dependency. A global Quasar installation is unnecessary.
- **npm.ps1 cannot be loaded:** use `npm.cmd run dev` in PowerShell or use VS Code's Command Prompt terminal. Changing Windows execution policy is unnecessary.
- **Missing SQLite driver:** enable pdo_sqlite and sqlite3 in the terminal's PHP configuration; `php --ini` identifies it.

## Existing installations

Back up your database and private .env first. Setup never resets existing passwords or roles. If Admin@Email.com already exists, it keeps its existing password. For the documented default login, use a fresh local extraction/database. Do not delete real transaction records to obtain it.

After manual dependency/configuration setup, `php artisan migrate --seed` applies migrations and seeds the same login. Demo credentials are created only in local/testing environments. Change the shared demo password before public hosting.

## Included workflows

| Area | Features |
| --- | --- |
| Dashboard | Seven-day sales, comparison, stock alerts, recent receipts, upcoming deliveries, auto-refresh |
| POS | Product filters, quantities, customer selection/creation, cash shortcuts, manually confirmed digital payments |
| Checkout recovery | Retained retry key and confirmed total, stock/price checks, replay protection |
| Receipts | Product-name snapshots, cashier, received/change amounts, text download, Print / Save PDF |
| Inventory | Restock, damage/loss, physical-count correction, movement history |
| Customers | Search, contacts, completed spending, purchase and delivery history |
| Deliveries | Scheduling, rider assignment, rescheduling before dispatch, status transitions, reasoned cancellation, timeline |
| Management | Admin-only product changes, voids, expenses, reports/CSV, audit log |
| Accounts | Admin/cashier access, password change, login-email rename, expiring hashed access tokens |

## Upgrade and other staff accounts

Retain your private backend/.env, APP_KEY, and database settings. Install dependencies with `composer install` in backend and `npm install` in frontend, apply `php artisan migrate`, then start both servers. Do not run destructive migration resets against records you want to retain. For MySQL, rehearse upgrades and concurrent last-stock checkouts on a backed-up copy; SQLite tests do not prove MySQL locking behavior.

Create another cashier using `php artisan staff:create cashier@example.test --role=cashier` in backend. To rename a login, use `php artisan staff:email CURRENT_EMAIL Admin@Email.com`; its identity/password/history are preserved and old sessions are revoked. The built-in local admin requires neither command. Email matching is case insensitive.

## Manual setup alternative

In backend, run `composer install`, then `php setup-local.php`, then `php artisan migrate --seed`. In frontend, run `npm install`. Start both servers as above. The PHP helper preserves existing APP_KEY and creates only missing local configuration/default SQLite files. It refuses non-local environments. The optional root SETUP.ps1 delegates to composer run setup.

The ZIP is source code; dependencies require a one-time installation from the lockfiles. PHP needs PDO SQLite (or PDO MySQL), mbstring, openssl, fileinfo, DOM/XML, curl, and zip.

## Checks and optional combined build

```powershell
cd backend
php artisan test
cd ../frontend
npm test
npm run typecheck
npm run build
```

BUILD_LOCAL.ps1 builds the frontend into Laravel for an optional single-server presentation. After that build, START.ps1 serves the combined website at http://127.0.0.1:8000. The default workflow is php artisan serve plus npm run dev.

See VERIFICATION.md, RELEASE_NOTES.md, FINAL_PROJECT_GUIDE.md, and MANUAL_ACCEPTANCE_CHECKLIST.md for checks and demonstration guidance.

## Scope

GCash/bank payments are manually confirmed records; no payment gateway charges money. Printer margins need checking on the actual device. Sales less expenses is not full accounting profit. Cloud hosting, SMS/courier integrations, and offline sale synchronization are not included.

Technical references: [Laravel seeding](https://laravel.com/docs/12.x/seeding), [Composer dependency installation](https://getcomposer.org/doc/01-basic-usage.md#installing-dependencies).
