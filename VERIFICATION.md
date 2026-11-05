# Verification record

Date: 5 November 2026 (Asia/Manila).

## Completed checks
| Check | Result |
| --- | --- |
| PHP syntax: application, migrations, routes, bootstrap, tests | Passed: 47 PHP files |
| PowerShell script parsing | Passed: 4 scripts |
| Vue / TypeScript `npm run typecheck` | Passed |
| Quasar production SPA build | Passed |
| Combined Laravel-hosted frontend build | Passed; compiled assets loaded from /app/ and browser routes opened |
| Backend PHPUnit suite | Passed: 24 tests, 112 assertions, using isolated in-memory SQLite |
| Fresh SQLite migrations | All migrations completed |
| Demo seed | Completed; repeated seeding tested to preserve existing stock |
| Staff provisioning command | Tested through Laravel's console testing interface |
| Browser smoke checks | Admin sign-in, overview, cash checkout, receipt, all navigation screens, mobile menu, logout, cashier sign-in, admin route guard |
| Browser console | No page errors in the completed smoke run |
| Receipt print-media layout | Inspected: receipt visible; navigation and action buttons hidden |
| Desktop / mobile screenshots | Inspected at 1440 px and 390 px widths |

The suite contains 22 added business-workflow tests and two tests from the supplied Laravel scaffold. It checks access tokens and roles, cash and digital payment validation, inventory side effects, exact replay of checkout requests, changed-key conflicts, duplicate cart rows, multi-product rollback, inactive products, single-use voids, physical stock corrections, protected customer history, product-name snapshots, delivery transitions, report ranges and expenses, CSV formula neutralization, logout/deactivation, password-change revocation, login throttling, account provisioning, and repeated seeding.

## Test environment and reproducibility
- Node.js 24.14.0; PHP 8.2.12; PHPUnit 11.5.57.
- Frontend dependency versions came from package-lock.json; backend packages came from the supplied composer.lock. This revision adds vue-tsc and updates the frontend lock file.
- Backend testing used a separate work copy and fictional data. The user's existing MySQL data was not accessed or altered.
- The managed Windows environment returned false for PHP readability/writability probes even when direct file reads and writes worked. Composer's normal installation therefore failed. For the isolated test run, locked dependency archives were extracted into a disposable work folder and Composer generated the autoloader.
- Two **test-runtime-only** dependency checks were adapted: PHPUnit's bootstrap readability probe and Laravel ProviderRepository's cache-directory writability probe. These workarounds are not in the delivered source or ZIP. Application business rules and assertions were run without changing those rules. Run `composer install` and `php artisan test` normally on the target computer before submission.
- The browser check used headless Chrome against local development and combined-build servers, with a separate browser profile and demo SQLite database. No real payment or external notification was sent.

## Checks still required on the target setup
1. Upgrade a backed-up copy of the actual MySQL database, then compare existing sales and stock. Fresh SQLite migration success does not establish compatibility with every existing database.
2. Use simultaneous MySQL connections to test competing sales for the last unit. The in-memory SQLite suite exercises sequential rules, not MySQL's concurrent locking behavior.
3. Print on the actual receipt printer, or use the school's required PDF/paper format. Print-media inspection does not verify printer drivers, paper widths, or margins.
4. Complete the manual acceptance checklist with the adviser’s required browser, devices, rubric, and business rules.

Screenshots use fictional demonstration records. The delivered source contains no real account credentials, `.env`, database files, vendor dependencies, or node_modules. Install dependencies using the setup guide.
