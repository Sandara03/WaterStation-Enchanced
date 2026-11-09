# Water Station V2

Updated 8 November 2026, Asia/Manila. This revision extends the complete Laravel + Vue/Quasar project.

## Dashboard and design
- A seven-day sales chart, week-to-week comparison, completed-order count, and quick links.
- A delivery desk showing in-progress, scheduled-today, and overdue deliveries.
- Upcoming deliveries, clickable recent receipts, stock alerts, and the last refresh time.
- Automatic overview refresh every minute while the page is visible.
- Responsive cards and a compact account button on phones.

## POS and receipts
- Available / low-stock product filters, search across names and descriptions, and `/` to focus search.
- Quick cash amounts, exact payment, amount still due, and disabled checkout until payment is sufficient.
- An explicit confirmation for manually verified GCash / bank payments. No payment gateway is connected.
- Create a customer without leaving the sale, or start a customer-linked sale from a profile.
- The client sends its confirmed total; a price mismatch rejects the transaction without changing stock. Refreshing the cart requires staff to confirm payment again.
- Receipts identify the cashier, distinguish cash from confirmed digital payments, and support text download plus the existing browser Print / Save PDF action.
- Schedule a delivery directly from a completed sale's receipt.

## Customers and deliveries
- Search customers by email as well as name and phone.
- Profiles show completed spending, completed order count, last purchase, printable purchase history, and recent deliveries. Voided sales are excluded from spending.
- Delivery search by recipient, phone, rider, or receipt, plus status / today / overdue / upcoming filters.
- Assign a rider and edit recipient, contact, address, instructions, or schedule before dispatch.
- Delivery timelines record the staff member, timestamp, and changes. Cancelling requires a reason; voiding a linked sale also records cancellation.
- Delivered and dispatched records reject detail edits. Existing transition and duplicate-delivery safeguards remain in force.
- A receipt uses the latest delivery after an earlier delivery is cancelled and rescheduled.

## Upgrade an existing local installation

Back up the database first. Keep the existing private `backend/.env` and `APP_KEY`. Install dependencies from the lock files if needed, then run:

```powershell
cd backend
php artisan migrate
cd ../frontend
npm ci
npm run typecheck
npm run build
```

Use the existing start scripts for the local frontend and backend, or run `BUILD_LOCAL.ps1` for Laravel to serve the built frontend. The new migration adds a nullable rider field and a delivery-events table. It preserves existing transactions, stock, customers, and staff accounts. Older deliveries have no historical timeline events; their future updates are recorded.

A fresh installation still follows `README.md` and creates staff accounts through `php artisan staff:create`. The preview's demo database and credentials are not bundled in this source archive.

## Validation for this revision

- Delivered backend suite: **34 tests, 199 assertions**, passed using isolated in-memory SQLite. Eight workflow regression tests cover stale prices and exact retry behavior, cashier information exposure, customer spending, delivery edits and history, cancellation, search/overdue filters, rescheduling, and the seven-day reporting boundary. Two staff-email tests verify that renaming keeps the password, role, and identity, revokes old sessions, and cannot take over another account.
- Vue/TypeScript check and the Quasar production build passed.
- Applied the additive migration to the backed-up fictional local preview database.
- Browser walkthrough: created a fictional customer from POS, sold two PHP 25 items with PHP 100 cash, confirmed PHP 50 change, downloaded a text receipt, scheduled from the receipt, assigned a rider, rescheduled, moved through every delivery status, and verified the customer's PHP 50 spending and delivered record.
- Desktop and phone layout reviewed; a narrow-header overflow was corrected. Screenshots are in `docs/screenshots`.

The local test runtime uses the dependency-probe workarounds documented in `VERIFICATION.md`; delivered dependencies are not patched. Run the normal suite on your target machine. MySQL concurrency, physical receipt-printer settings, and live payment processing remain outside these local checks. This revision's browser Print / Save PDF action should be checked on the target printer/browser; the new text download was verified directly.

The running preview is local. This archive does not itself publish a hosted website or create a cloud database.
