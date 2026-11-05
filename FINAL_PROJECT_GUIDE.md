# AHY Water Station — Final project presentation guide

## Project description
AHY Water Station is a local point-of-sale and station management system. It brings sales, stock, customer contacts, delivery progress, operating expenses, and management reports into one application.

The frontend uses Vue 3, TypeScript, and Quasar. The backend uses Laravel 12 and a relational database. SQLite is convenient for a fresh classroom demo; MySQL is supported by Laravel and should be used when checking simultaneous transactions for deployment.

## What this revision adds or completes
| Area | Result | Why it matters |
| --- | --- | --- |
| Access control | Login, admin/cashier roles, expiring hashed access tokens, logout, password change | Staff actions are checked by the server |
| Checkout | Integer-cent calculation, payment checks, stock checks, retry keys | Helps prevent invalid or duplicate sales |
| Receipts | View, reprint, browser Print / Save PDF, stored product names | Historical transactions stay readable after renaming a product |
| Sales | Server pagination and receipt/customer search | Older transactions remain accessible |
| Voids | Admin only, required reason, one stock restoration, audit record | Corrects a sale while retaining its history |
| Inventory | Restock, damage/loss, physical-count correction, movement history | Stock changes have a reason and a before/after count |
| Customers | Create, edit, search, purchase history | Links repeat customers to transactions |
| Deliveries | Linked sale, schedule, controlled transitions, duplicate checks | Makes delivery progress traceable |
| Expenses | Categories, date, amount, descriptions, audited removal | Gives context to revenue |
| Reports | Date ranges, daily bars, best sellers, payment breakdown, CSV | Helps explain station performance |
| Dashboard | Today's sales, stock warnings, recent sales, delivery count | Gives a useful starting view |
| Usability | Mobile menu, loading states, error messages, empty states | Supports small screens and failed requests |
| Quality checks | TypeScript check, production build, backend regression tests | Makes correctness reviewable |

## A 10–12 minute demonstration
1. Sign in as admin. Explain that roles are also enforced by API middleware.
2. Open the dashboard and point out stock alerts and today's completed sales.
3. Add a customer with fictional details.
4. In POS, add two units of purified water, select the customer, and enter PHP 100 for a PHP 50 sale. Complete the sale and show PHP 50 change.
5. Show the receipt and its Print / Save PDF action.
6. Open Inventory. Show that stock decreased by two and the movement references the receipt.
7. Schedule delivery against the sale. Move it from Pending to Preparing to Out for Delivery to Delivered. Explain that the server rejects skipped steps.
8. Make a separate sale for a walk-in customer. Void it with a reason. Show stock restored, the sale marked Voided, and revenue excluding the sale.
9. Record a fictional utility expense. Open Reports for today's range and explain sales less recorded expenses.
10. Export CSV. Point out the payment breakdown and best-selling products.
11. Open Activity Log to show the sale, void, stock changes, and staff identity.
12. Sign out and sign in as cashier. Show the available work screens. Explain that cashier requests to admin endpoints return HTTP 403.

## Questions you should be able to answer
- **What happens if checkout fails halfway?** Order creation, line items, stock changes, and the audit record are in a database transaction. Exceptions roll back the changes. See Laravel's transaction documentation below.
- **How do you prevent overselling?** Products are locked before checking and reducing stock. Duplicate product rows are combined. Verify true concurrency against MySQL; SQLite tests do not prove MySQL locking behavior.
- **What if the cashier clicks twice or retries after a timeout?** The client retains a checkout UUID and the exact submitted payload while confirmation is pending. A replay with the same key and content returns the same order. A changed payload using the old key is rejected.
- **Can a cashier change prices?** Product writes, stock adjustments, sale voids, management reports, expenses, and audit access require admin middleware.
- **How are passwords and tokens stored?** Laravel hashes passwords. The database stores a SHA-256 digest of each random access token. Tokens expire after eight hours. Logout revokes the current token; password changes revoke all of that staff member's tokens.
- **What is a physical-count correction?** It sets stock to the counted quantity, including zero. Restock adds units; damage subtracts units.
- **Can every sale be voided?** No. Only Completed sales can be voided once. Dispatched or delivered sales need a separate return process, which is outside this revision. A void does not automatically refund real money.
- **Is sales less expenses profit?** No. It excludes product cost, taxes, depreciation, and any cost not entered in Expenses. The interface states this explicitly.
- **Are GCash payments processed?** No. Staff manually confirms payment and records the reference. There is no payment gateway integration.
- **Are audit logs tamper-proof?** They are read-only through this app. A privileged database administrator can still change the database.

## Data relationships
- User → access tokens; cashier and voiding-user references on orders.
- Customer → orders and deliveries.
- Order → order items; linked deliveries.
- Product → order items and stock movements.
- Expense → recording user.
- Audit log → action, record reference, actor, metadata, timestamp.

An order item stores its sale price, subtotal, quantity, and product-name snapshot. Reports include Completed orders only. CSV exports retain each order's status, including Voided orders, so a reviewer can see the full record.

## Scope to state honestly
This is an improved final-project implementation, not a guarantee of a perfect grade or production certification. It does not include online payment processing, SMS/email notifications, full accounting, supplier purchasing, container deposits/returns, staff administration through a web screen, cloud backup, or offline transaction synchronization. Use the console command for staff provisioning. Validate the work against your school's rubric and your adviser’s requirements.

For real deployment, use HTTPS, private credentials, database backups, a configured production web server, and a dedicated review of authentication, concurrent MySQL transactions, and operating procedures. The included local setup binds to 127.0.0.1.

## Technical references
- [Laravel 12 database transactions](https://laravel.com/docs/12.x/database#database-transactions)
- [Laravel 12 pessimistic locking](https://laravel.com/docs/12.x/queries#pessimistic-locking)
- [Laravel 12 validation](https://laravel.com/docs/12.x/validation)
