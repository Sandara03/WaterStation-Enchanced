# Manual acceptance checklist

Use a separate demo database with fictional customer data. Record the browser, database engine, date, expected result, actual result, and pass/fail for your school’s testing appendix.

| ID | Scenario | Expected result |
| --- | --- | --- |
| A01 | Open POS without signing in | Redirect to sign-in; API returns 401 without a token |
| A02 | Use an incorrect password five times | Further attempts temporarily return 429 |
| A03 | Sign in as cashier and request admin-only API | HTTP 403 |
| A04 | Change password, then reuse an old token | Old token returns 401 |
| P01 | Buy two PHP 25 units with PHP 100 | Total 50, change 50, stock reduced by two |
| P02 | Enter PHP 49 for that sale | Validation error; no order or stock deduction |
| P03 | Digital payment without reference or with wrong amount | Validation error |
| P04 | Buy an inactive or unavailable product | Server rejects checkout |
| P05 | Retry the exact checkout UUID and payload | Same order returned; one stock deduction |
| P06 | Change the payload but reuse a successful checkout key | HTTP 409 |
| P07 | Refresh POS with an unsold cart | Cart restored for the current browser tab; prices and stock refreshed |
| P08 | Open an old sale after renaming a product | Receipt retains original product-name snapshot for new sales |
| P09 | Print receipt or save as PDF | Only receipt content printed; inspect on target printer/browser |
| S01 | Void a sale twice | First returns stock; second returns 409 |
| S02 | Void a sale with a dispatched or delivered delivery | Rejected; stock unchanged |
| S03 | Void a sale with pending/preparing delivery | Sale voided; linked delivery cancelled |
| I01 | Restock five units with reason | Stock +5 and movement recorded |
| I02 | Mark damage greater than available stock | Rejected; stock unchanged |
| I03 | Set physical count to zero | Stock becomes zero; before/after retained |
| I04 | Edit product details | Stock cannot be edited there; use Inventory |
| C01 | Add/edit a customer and find by phone | Details saved and searchable |
| C02 | Delete customer with sales or delivery history | Rejected |
| C03 | Add more than 25 customers | Further pages are reachable |
| D01 | Schedule a completed sale | Linked Pending delivery created |
| D02 | Schedule another active delivery for the same sale | Rejected |
| D03 | Skip directly from Pending to Delivered | Rejected |
| D04 | Follow the delivery sequence | Delivered time recorded; terminal status cannot restart |
| R01 | View date-range report with a voided sale | Voided sale excluded from sales totals |
| R02 | Record a PHP 12.50 expense with PHP 50 sales | Expenses 12.50; sales less expenses 37.50 |
| R03 | Reverse the report dates | Validation error |
| R04 | Export a customer name beginning with '=' | CSV value neutralized with a leading apostrophe |
| R05 | Page through sales and audit log | Older records accessible |
| U01 | Use 390 px and desktop viewport widths | Navigation, dialogs, and forms remain usable; tables scroll |
| U02 | Stop backend and load a screen | Clear error and retry path |
| U03 | Disconnect during checkout and reconnect | Exact pending submission can be retried |
| M01 | Two MySQL connections buy the last unit concurrently | Exactly one succeeds; no negative stock |
| M02 | Migrate a backed-up copy of an existing MySQL database | Existing sales/products retained; login requires staff provisioning |

SQLite automated tests cover sequential business rules. M01 and M02 must be checked against the actual MySQL environment; passing SQLite tests does not establish MySQL concurrency or upgrade compatibility.
