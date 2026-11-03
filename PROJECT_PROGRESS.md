# Temple Management System — Project Progress & Phase Tracker

## Current Status: Phase 6 (Budget & Procurement Management) — FINAL APPROVED

**Current Local Date**: 2026-11-01  
**Active Phase**: Phase 6 — Budget, Vendor & Procurement Management (FINAL APPROVED)  
**Phase 1 Status**: **Completed & Fully Tested** (22 tests, 43 assertions)  
**Phase 2 Status**: **Completed & Fully Tested** (54 tests, 139 assertions)  
**Phase 2.1 Status**: **Completed & Fully Tested** (67 tests, 232 assertions)  
**Phase 3 Status**: **Completed & Fully Tested** (80 tests, 321 assertions)  
**Phase 4 Status**: **Completed, Corrected & Fully Tested** (101 tests, 513 assertions)  
**Phase 5 Status**: **Completed & Fully Tested** (144 tests, 876 assertions total)  
**Phase 6 Status**: **FINAL APPROVED — All Increments (1, 2, 3, 4 & 5) Completed, Audited & Fully Verified** (269 tests, 1,608 assertions total)  
**Next Phase**: Phase 7 (Inventory, Goods Receiving, Asset Registry & Maintenance) — On Standby Awaiting Explicit User Approval

---

## Event Donations, Excess Contributions, Outstanding Dues & Event Fund Settlement Deliverables

| # | Task / Requirement | Status | Verification Detail |
|---|---|---|---|
| 1 | **Guest / Visitor Donation Entry** | Completed | Clean entry flow supporting guest walk-in donors without forcing permanent person or family creation. Support for event donation (`voluntary_event`) and General Temple Fund (`voluntary_general`). Never creates fictitious pledges or dues. Full verification and receipt generation preserved. |
| 2 | **Excess Contribution Handling & Destination Selection** | Completed | Dynamic split when payment exceeds outstanding pledge balance: pledge portion applies to pledge, remaining excess allocated to user-selected destination (**Current Event Fund** vs **General Temple Fund**). Evaluates remaining balance first on partial payments. Never silently reclassifies excess funds. Fully localized in EN and BN. |
| 3 | **Event Collection & Financial Overview Dashboard** | Completed | Rich 10-metric dashboard cards on Event details page: Total Pledged, Verified Pledge Collections, Outstanding Pledges, Event Excess Contributions, Voluntary Event Donations, Total Verified Collections, Approved Expenses, Net Event Surplus/Deficit, Pending Verification, and Dynamic Above Target (+৳X) / Shortfall (৳X) Indicators. |
| 4 | **Clickable Summary Cards & Detailed Modal Reports** | Completed | 1. **Above Pledged Target Report Modal**: Detailed report of donors contributing more than pledged, showing pledge, verified, excess, chosen destination, and receipt.<br>2. **Outstanding Dues Report Modal**: Computes strictly `max(0, Pledged - Verified)`, listing donors, phone, and direct collection action.<br>3. **Excess Contribution Report Modal**: Comprehensive breakdown of all excess allocations and destination funds. |
| 5 | **Individual Donor Comparison Tab** | Completed | Interactive tab on Event details page listing every donor: Pledged Amount, Verified Paid Applied, Excess Contributed, Outstanding Balance, Status, and Actions with exact decimal arithmetic. |
| 6 | **Event Closing & Event Fund Settlement Workflow** | Completed | Funds remain attributed to active events. When closing/closed, provides explicit **Event Fund Settlement** workflow. Reconciles verified collections and approved expenses to determine net surplus. Transfers surplus to General Temple Fund via balanced double-entry journal entry without creating new income. Strict database-enforced idempotency (`event_id` unique on `event_settlements`) prevents duplicate settlements. |
| 7 | **Automated Test Suite & Pint Code Hygiene** | Completed | Created `tests/Feature/EventDonationAndSettlementTest.php` with 15 tests (111 assertions) covering guest donations, excess splitting, metrics calculation, reports, and fund settlement. Total test suite: 144 passed (876 assertions) with 100% PSR-12 compliance via Laravel Pint. |

---

## Phase 5 Finance & Accounting Management Deliverables & Verification Checklist

| # | Task / Requirement | Status | Verification Detail |
|---|---|---|---|
| 1 | **Centralized Double-Entry General Ledger** | Completed | Standard Chart of Accounts (`1010` Cash, `1020` Bank, `1030` MFS, `2010` AP, `3010` General Fund, `3020` Opening Balance Equity, `4010`-`4040` Income, `5010`-`5080` Expenses). Enforces mathematical invariant $\sum \text{Debit} = \sum \text{Credit}$ on every journal voucher. Posted vouchers are immutable. Sequential concurrency-safe numbering `JRN-YYYY-0001`. |
| 2 | **Automated Phase 4 Integration & Idempotency** | Completed | `FinancePostingService` hooks atomically into `CollectionPaymentService::verifyPayment`. Automatically credits appropriate income accounts and debits payment asset account (Cash, Bank, MFS). Excess donations posted as additional voluntary income without duplicate counting. Idempotent via unique constraint on `['source_type', 'source_id']`. Unverified or rejected payments never hit the ledger. |
| 3 | **Payment Reversal Inverse Ledger Entries** | Completed | Reversing a verified payment creates an inverse balanced reversal voucher (`JRN-YYYY-0002`) preserving complete historical audit trail. Receipts voided, allocations deactivated. |
| 4 | **Expense Approval & Management Workflow** | Completed | Full expense lifecycle (`pending_approval` -> `approved` / `rejected` / `reversed`). Super Admin & authorized roles can View, Edit, Approve, and Reject pending expenses. Approval requires confirmation, posts balanced double-entry ledger entry atomically, and prevents duplicate posting. Rejection requires mandatory reason and never hits ledger. Pending expenses support secure server-validated edits via `UpdateExpenseRequest`. Completed `EXP-YYYY-0001` sequencing. |
| 5 | **Fund Transfer Between Liquid Asset Accounts** | Completed | Direct transfer between cash, bank, and MFS accounts without touching income or expense accounts. Handles optional transfer/bank charge fees to account `5070`. Validates source and destination accounts are distinct asset accounts. Sequential numbering `TRF-YYYY-0001`. |
| 6 | **Manual & Miscellaneous Temple Income** | Completed | Record manual non-devotee miscellaneous income (hall booking, interest, scrap sales) debiting asset deposit account and crediting income account. Sequential numbering `INC-YYYY-0001`. |
| 7 | **Controlled Opening Balance Setup** | Completed | Controlled setup of opening balances for balance-sheet accounts debiting/crediting against `Opening Balance Equity` (`3020`). Prevents duplicate opening balances per account. Never distorts period income/expense statements. |
| 8 | **Executive Financial Dashboard** | Completed | Real-time liquid asset cards (Cash in Hand, Bank Accounts, Mobile Financial Services), monthly income vs. expense comparison, net cash flow, pending expense approvals, unposted payments alert, and recent journal feed. |
| 9 | **7 Core Financial Statements & Reports** | Completed | 1. **Trial Balance**: Debit/Credit reconciliation with balanced status indicator.<br>2. **General Ledger**: Account-wise transaction history with running balances.<br>3. **Income & Expense Statement**: Net surplus/deficit computation.<br>4. **Cash Book**: Daily cash inflow/outflow register.<br>5. **Bank & MFS Account Statement**: Account-specific register.<br>6. **Account Balances Summary**: High-level balance overview.<br>7. **Event-wise Financial Summary**: Puja-specific revenue, direct expense, and net surplus. |
| 10 | **Granular RBAC & Separation of Duties** | Completed | 16 dedicated finance permissions (`view_finance_dashboard`, `view_accounts`, `manage_accounts`, `view_journals`, `record_expenses`, `approve_expenses`, etc.). Requisition submitters cannot approve their own expenses. Protected mutating endpoints with FormRequests, Gate policies, and audit logging. |
| 11 | **Bilingual Localization (EN & BN)** | Completed | 100% key parity across all language files (`lang/en/` and `lang/bn/`), including `finance.php`, `common.php`, and `datatables.php`. DataTables core strings (Search, emptyTable, zeroRecords, info, pagination buttons) automatically localized across all views. |
| 12 | **Automated Test Suite & Code Hygiene** | Completed | Created `tests/Feature/FinanceAndAccountingTest.php` (18 tests) and `tests/Feature/FinanceExpenseApprovalTest.php` (9 tests) covering all specifications (including journal voucher show, reversal tracking, expense approval/editing/rejection, and double-entry balance). All 129 tests passing cleanly (760 assertions total) with 100% PSR-12 compliance via Laravel Pint. |
| 13 | **UI Layout, Sticky 100vh Sidebar & Auto-Scroll** | Completed | Configured `100vh` sticky layout with independently scrollable `.sidebar-menu`. Fixed page reload issue by automatically scrolling `.sidebar-menu` to keep the active menu item in view. Persisted user scroll position via `sessionStorage`. Global DataTables defaults ensure consistent localization everywhere. |

---

## Phase 4 & Phase 4 Corrections Deliverables & Verification Checklist

| # | Task / Requirement | Status | Verification Detail |
|---|---|---|---|
| 1 | **Allow Payments Exceeding Pledged / Expected Amount** | Completed | Supported payments exceeding outstanding pledge or monthly dues. Automatically allocates up to outstanding amount to the obligation, and allocates remaining excess as additional voluntary donation (`voluntary_event` or `voluntary_general`). Never creates fictitious debt or outstanding balances. |
| 2 | **Respectful Excess Contribution Presentation** | Completed | On pledge details page (`pledges/show.blade.php`), member contributions, and reports, displays positive respectful message: *“এই ভক্ত তাঁর প্রতিশ্রুত অনুদানের চেয়েও অতিরিক্ত ৳X প্রদান করে মন্দিরের কাজে সহযোগিতা করেছেন।”* Displays pledged amount, total contributed, and additional contribution distinctly. |
| 3 | **Preselected & Locked Obligation / Payer Flow** | Completed | Action buttons from `/pledges` and `/committee-contributions` open `/collections/create` with preselected and locked payer card (avatar, contact, obligation code, outstanding amount) preventing accidental cashier modification. Server-side validation enforces submitted IDs match obligation owner. Preserves locked state on validation errors. |
| 4 | **Open Payer Selection Flow for Fresh Payments** | Completed | Fresh payment entry from `/collections` provides open payer selection (Individual Devotee, Family Unit, Walk-in Donor) with Select2 AJAX. Features `GET /collections/payer-obligations` AJAX endpoint dynamically loading active obligations for selected payer. |
| 5 | **Receipt Download & Share Capabilities** | Completed | Preserved existing print view. Added PDF Download via offline `html2pdf.bundle.min.js` rendering 100% faithful Bengali and English typography with temple branding, signatures, and amount in words. Added Share options: Web Share API, direct WhatsApp link, Messenger, and clipboard link copy. Server-enforced permissions protect access. |
| 6 | **Fix DataTable Error on Committee Details Page** | Completed | Resolved column count mismatch alert on `/committees/3` by updating `TempleUI.initDataTable` in `temple-ui.js` to automatically clean single empty `td[colspan]` rows, and updated `resources/views/committees/show.blade.php` to use `@foreach` for active and historical membership tables. |
| 7 | **Atomic Verification & Receipt Generation** | Completed | Payment status changes to `verified` atomically with official sequential receipt generation (`RCP-YYYY-0001`). Reversals void receipts and deactivate allocations. |
| 8 | **Strict Separation of Duties & Server Security** | Completed | Submitter cannot self-verify payments. All endpoints protected by server-side middleware, FormRequests, row locking (`lockForUpdate()`), and comprehensive audit logging. |
| 9 | **Automated Test Suite & Pint Code Hygiene** | Completed | All 101 tests passing cleanly (513 assertions) with zero warnings or errors. 100% PSR-12 code compliance verified with `php ./vendor/bin/pint --test`. |

---

## Phase 3 Puja & Event Management Deliverables & Verification Checklist

| # | Task / Requirement | Status | Verification Detail |
|---|---|---|---|
| 1 | **Event Data Model & Migrations** | Completed | Created `event_categories`, `events`, and `event_lifecycle_logs` tables. Sequential per-year code format `EVT-YYYY-0001` with thread-safe `lockForUpdate()`. Independent start/end dates and collection window dates. Strict foreign keys and soft deletes. |
| 2 | **Configurable Event Categories** | Completed | Full CRUD and management for religious and community event categories (Durga Puja, Lakshmi Puja, Kali Puja, Saraswati Puja, Shivaratri, etc.) with bilingual names, slugs, and descriptions. Safe deletion protection (`canBeSafelyDeleted()`) preventing deletion of system or referenced categories. |
| 3 | **Multiple Concurrent Events** | Completed | Database and model architecture explicitly supports arbitrary numbers of overlapping and concurrent active events across different venues and liturgical years. |
| 4 | **Independent Collection Periods** | Completed | Flexible collection start and end dates allowing donation/pledge collection to start in preceding calendar years (e.g., event year 2027 starting collections in Dec 2026) or continue post-event. |
| 5 | **Status Transition State Machine** | Completed | Structured lifecycle states: `draft` -> `upcoming` -> `active` -> `closing` -> `closed`. Atomic transition enforcement in `EventLifecycleService` with structured logging. Direct transition to `closed` is prohibited; formal closing workflow is mandatory. |
| 6 | **Two Distinct Closing Modes** | Completed | **Mode A (`close_and_lock`)**: Complete immutability, blocks standard and post-closing edits, requires privileged reopening.<br>**Mode B (`close_with_authorized_adjustments`)**: Marked closed, blocks ordinary CRUD, permits authorized non-structural metadata adjustments (venue, descriptions) with mandatory reason and diff logging. |
| 7 | **Privileged Reopening Workflow** | Completed | Reopening requires permission (`reopen_events`), target status (`active` or `upcoming`), and mandatory justification. Reopening permanently preserves prior closing mode, reason, and closing timestamp in `event_lifecycle_logs` diff. |
| 8 | **Audit Trail & Lifecycle History** | Completed | Comprehensive logging of all transitions, closures, reopenings, and adjustments into `event_lifecycle_logs` and centralized `AuditLogger`. Formatted diff inspection collapse for adjustments. |
| 9 | **UI Standardization & Integration** | Completed | Implemented Event Dashboard, DataTables listing with server-side processing (`deferLoading`), Select2 filters (year, category, status), detailed show view with status banners, and modal dialogs with button loading indicators. |
| 10 | **Bilingual Localization (EN & BN)** | Completed | Full 100% parity across `lang/en/events.php` and `lang/bn/events.php` for all labels, statuses, closing modes, modal notices, and feedback messages. |
| 11 | **Automated Test Suite & Code Hygiene** | Completed | Added `tests/Feature/EventManagementTest.php`. 80 tests passing (321 assertions). 100% PSR-12 compliance verified via `php ./vendor/bin/pint --test`. |

---

## Automated Test Verification Record

Executed command:
```bash
php artisan test
```

Result:
```text
   PASS  Tests\Unit\DuplicateDetectionTest
  ✓ detects duplicate phone
  ✓ detects duplicate email
  ✓ detects duplicate name and dob
  ✓ excludes self during update

   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Unit\PersonCodeTest
  ✓ person code generation format
  ✓ family code generation format

   PASS  Tests\Unit\RoleTest
  ✓ role returns localized name

   PASS  Tests\Feature\AuthenticationTest
  ✓ login screen can be rendered
  ✓ users can authenticate using the login screen
  ✓ users cannot authenticate with invalid password
  ✓ inactive users cannot authenticate
  ✓ users can logout
  ✓ authenticated user redirected away from login

   PASS  Tests\Feature\CommitteeManagementTest
  ✓ guest cannot access committees
  ✓ devotee without permission cannot manage committees
  ✓ admin can create committee
  ✓ assign existing person does not create duplicate person
  ✓ admin can update member position
  ✓ concluding membership preserves historical records
  ✓ person can participate in multiple terms

   PASS  Tests\Feature\DashboardTest
  ✓ guest is redirected to login from dashboard
  ✓ authenticated user can view dashboard
  ✓ user can view and update profile

   PASS  Tests\Feature\EventManagementTest
  ✓ guest cannot access events dashboard or list
  ✓ user without permission cannot access events
  ✓ authorized user can view events dashboard and list
  ✓ authorized user can create event with independent collection period
  ✓ event code increments per year independently
  ✓ system supports multiple concurrent active events
  ✓ event lifecycle transitions
  ✓ closing mode a close and lock blocks ordinary and post closing modifications
  ✓ closing mode b permits authorized adjustments with reason and diff logging
  ✓ privileged reopening preserves historical closing details
  ✓ draft event can be deleted but non draft cannot
  ✓ event category management and safe deletion
  ✓ datatable server side json response for events

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response

   PASS  Tests\Feature\FamilyManagementTest
  ✓ guest cannot access families
  ✓ devotee without permission cannot access families
  ✓ admin can view families list
  ✓ admin can create family
  ✓ admin can assign existing person to family
  ✓ admin can remove member from family
  ✓ cannot delete family with existing members
  ✓ can delete empty family

   PASS  Tests\Feature\LocalizationTest
  ✓ default locale is english
  ✓ user can switch locale to bengali
  ✓ user can switch locale to english
  ✓ authenticated user preferred locale is saved on switch

   PASS  Tests\Feature\PeopleManagementTest
  ✓ guest cannot access people directory
  ✓ devotee without permission cannot access people directory
  ✓ admin can view people list
  ✓ admin can filter people by search and classification
  ✓ admin can create person with auto generated code
  ✓ duplicate warning returned when matching profile exists
  ✓ user can proceed with registration when confirming distinct person
  ✓ admin can update person profile
  ✓ admin can toggle person active status
  ✓ person can have multiple classifications without duplicates
  ✓ private notes are protected and only visible to authorized users

   PASS  Tests\Feature\RolePermissionTest
  ✓ user can be assigned role
  ✓ super admin has all permissions implicitly
  ✓ super admin can access audit logs
  ✓ devotee cannot access audit logs
  ✓ treasurer can access finance overview
  ✓ devotee cannot access finance overview

   PASS  Tests\Feature\UiStandardizationTest
  ✓ guest cannot access people search ajax
  ✓ devotee without permission cannot access people search
  ✓ admin can search people via select2 ajax
  ✓ guest cannot access families search ajax
  ✓ devotee without permission cannot access families search
  ✓ admin can search families via select2 ajax
  ✓ people datatable server side json response
  ✓ people datatable sorting and page length change
  ✓ families datatable server side json response
  ✓ audit logs datatable server side json response
  ✓ audit logs page renders bootstrap five pagination
  ✓ datatables and select2 localization parity
  ✓ ui assets are present

  Tests:    80 passed (321 assertions)
  Duration: 9.58s
```

---

## Code Hygiene & Formatting Verification

Executed command:
```bash
php ./vendor/bin/pint --test
```
Result:
```json
{"tool":"pint","result":"passed"}
```

---

## Phase 4: Donation, Pledge & Collection Management — Status: COMPLETED

### Summary of Completed Implementation
1. **Event-Specific Pledges & Manat Tracking**:
   - Single owner enforcement: individual devotee (`person_id`) OR family unit (`family_id`).
   - Concurrency-safe unique pledge code generation: `PLG-YYYY-0001` with retry protection.
   - Concurrent event pledges for the same individual without collision (e.g. Durga Puja vs. Kali Puja).
   - Lifecycle tracking: `unpaid`, `partial`, `paid`, and `cancelled` (with mandatory audit reason).
2. **Voluntary Donations Without Mandatory Pledge**:
   - Direct event and general donations recorded without creating fictitious pledges or outstanding balances.
3. **Submission & Verification Workflow with Strict Separation of Duties**:
   - Payment submissions enter `pending_verification` status and do not count toward verified totals or reduce outstanding balances until approved.
   - Server-enforced self-verification prohibition: submitters (`submitted_by`) cannot verify their own submissions (`verify_collections`) unless `super_admin`.
   - Authorized verifiers review and atomically approve submissions, issuing official money receipts.
   - Rejection workflow with mandatory `rejection_reason` and audit logging.
4. **Official Sequential Receipts & Reversal Safeguards**:
   - Unique official receipt number generation (`RCP-YYYY-0001`) atomically generated only upon verification.
   - Receipts cannot be issued for unverified or pending payments.
   - Print-ready receipt document with `@media print` styling, dual English/Bengali header, amounts in numbers and words, and authorized signatory block.
   - Reversal workflow (`reverse_collections`): verified payment marked `reversed`, payment allocations marked `is_active = false`, pledge/contribution balances recalculated, and receipt marked `status = voided` with `voided_at`, `voided_by`, and `void_reason`. No financial records are deleted.
5. **Payment Allocations & Integrity Rules**:
   - Foreign key `payment_allocations.collection_payment_id` uses `restrictOnDelete()` to prevent silent cascading deletes.
   - Exact sum constraint: sum of active allocations must match the payment amount.
   - Allocations cannot exceed outstanding balance of pledges or monthly committee dues, protected by row locking (`lockForUpdate`).
6. **Monthly Committee Contributions**:
   - Monthly committee dues treated as non-refundable temple fund income (not liabilities, savings, or DPS).
   - Unique composite constraint: `(committee_membership_id, contribution_month)` prevents duplicate entries.
   - Concurrency-safe contribution code format: `MCC-YYYYMM-0001`.
   - Bulk generation for active committee members skipping existing records.
   - Historical contributions and obligations preserved after committee membership concludes.
   - Waiver workflow with mandatory reason.
7. **Collection Reports & UI Standardization**:
   - Event-wise collection and pledge summary report (`/collections-reports/event-summary`).
   - Outstanding pledges ledger report (`/collections-reports/outstanding-pledges`).
   - 12-month committee contributions ledger report (`/collections-reports/committee-contributions`).
   - Server-side DataTables on `/collections`, `/pledges`, `/receipts`, and `/committee-contributions`.
   - Toastr notifications and `TempleUI.button.loading()` across all submission and modal forms.
   - 100% English (`lang/en/donations.php`) and Bengali (`lang/bn/donations.php`) localization parity.
8. **Phase 5 Finance Integration Boundary Contract**:
   - Fields `finance_posted_at` and `finance_post_reference` on `collection_payments` are null by default.
   - Phase 4 maintains clean operational records without competing ledgers or premature double-entry journal entries.
   - Phase 5 contract: Phase 5 will consume verified `collection_payments` where `finance_posted_at IS NULL`, post balancing double-entry ledger entries (Debit: Bank/Cash Asset, Credit: Event Donation/Pledge/Fund Income), and stamp `finance_posted_at = now()` and `finance_post_reference = voucher/journal_code`.

---

## Test Execution Results (Phase 4 Included)

Executed command:
```bash
php artisan test
```

Result:
```
  Tests:    96 passed (462 assertions)
  Duration: 14.12s
```

All 96 tests run cleanly against SQLite in-memory without error.

---

## Code Hygiene & Formatting Verification

Executed command:
```bash
php ./vendor/bin/pint --test
```
Result:
```json
{"tool":"pint","result":"passed"}
```

---

## Recommended Next Phase: Phase 5 (Centralized Financial Ledger & Double-Entry Accounting)

> **NOTICE**: As per architectural rules, Phase 5 will **NOT** begin until Phase 4 has been explicitly reviewed and approved by the user.

### Proposed Phase 5 Scope:
1. Chart of Accounts (Assets, Liabilities, Equity, Incomes, Expenses).
2. Balanced Double-Entry General Ledger (`debits == credits`).
3. Integration with verified Phase 4 collections (`finance_posted_at`, `finance_post_reference`).
4. Operational Expense Vouchers and approval workflows.
5. Financial Statements: Trial Balance, Balance Sheet, Income & Expenditure Statement, Fund Balances.


---

## Clear Income & Expense Reports (Revised)

### Scope Implemented:
1. **Separate Sidebar Menus**:
   - Added **Income Report** (`/finance/reports/income`) and **Expense Report** (`/finance/reports/expenses`) under Finance sidebar.
   - Updated route matching so reports highlight their respective menu items cleanly.
2. **Income Report & Dynamic Filters**:
   - Person/Donor dynamic AJAX Select2 search.
   - Filters: Donor Type (Registered Person, Committee Member, Guest/Visitor), Income Type (Pledge, Voluntary Event, Monthly Contribution, General Donation, Miscellaneous Income), Event, Date Range, Status (Verified, Pending, Rejected, Reversed), Payment Method, and Keyword Search.
   - Guest donations with `payer_name_override` are fully supported without requiring permanent profiles.
3. **Expense Report & Dynamic Filters**:
   - Payee/Person dynamic AJAX Select2 search (searching people and vendor names).
   - Filters: Expense Category Account, Event, Date Range, Status (Approved, Pending Approval, Rejected, Reversed), Payment Method, and Keyword Search.
4. **Simple DataTables (STRICTLY NO ROW NUMBER / `#` COLUMN)**:
   - Server-side DataTables processing with exactly 7 columns:
     - Income: Donor / User Details, Purpose, Event, Amount, Date, Status, Action.
     - Expense: Payee / User Details, Purpose, Event, Amount, Date, Status, Action.
   - Localized status badges with full English and Bengali parity.
5. **Bootstrap 5 Offcanvas AJAX View Details**:
   - "View Details" opens an `offcanvas-end` drawer with loading indicator and renders comprehensive audit sections without page refresh:
     - Section A: Transaction Info
     - Section B: Donor / Payee Info
     - Section C: Event & Fund Allocation / Financial Accounts
     - Section D: Verification / Approval & Audit Trail
     - Section E: Related Records & Actions (Money Receipt links, Journal Voucher links)
6. **Live Summary Performance Cards**:
   - Income: Verified Income Total, Pending Verification Amount, Number of Transactions.
   - Expense: Paid / Posted Expense Total, Pending Approval Amount, Number of Transactions.
   - Strict status separation ensuring unverified/pending/rejected/reversed transactions are not double counted.
7. **Comprehensive Feature Test Suite & Frontend JavaScript Reliability**:
   - Fixed syntax error in `expenses.blade.php` (redundant closing bracket) that prevented DataTables initialization in the browser.
   - Verified server-side DataTables AJAX response, live metrics updating, and Offcanvas view rendering.
   - `tests/Feature/IncomeAndExpenseReportTest.php` covers access control, column format verification, filters, guest donations, summary calculations, offcanvas rendering, and Select2 AJAX endpoints.
   - 156 tests passed (1009 assertions). Clean Pint code styling.

---

## Phase 6: Budget, Vendor & Procurement Management — Status: IN PROGRESS (Increments 1 & 2 Completed)

### Gap Analysis Summary
1. **Sidebar Navigation**:
   - **Prior state**: All sidebar navigation links were flat top-level items under module headings, leading to excessive vertical scrolling and fragmented related pages.
   - **Current state**: Reorganized into a standardized Parent Menu → Submenu hierarchy using Bootstrap 5 collapse with animated chevrons, active-parent highlighting, and auto-expanding on child route navigation.
2. **Budget Management**:
   - **Prior state**: Budgets existed only as a conceptual `budget_amount` integer on `events`. No formal annual, general fund, or event-specific budget ledger, revision history, approval workflow, or Budget vs Actual analytics existed.
   - **Current state (Increment 2 Completed)**: Comprehensive Budget model, `budgets` and `budget_items` tables, concurrency-safe code generation (`BDG-YYYY-0001`), workflow states (Draft, Submitted, Approved, Rejected, Cancelled), strict separation of duties, and live Budget vs Actual variance analytics against Finance expenses.
3. **Vendor Management**:
   - **Gap**: Currently vendors are recorded as unconstrained strings (`vendor_name`, `vendor_phone`) on `expenses`. No master vendor directory, contact registry, tax identifiers, or transaction histories exist.
   - **Plan (Increment 3)**: Implement dedicated `vendors` directory with DataTables, Select2 integration, and historical transaction auditing.
4. **Procurement Pipeline (Purchase Requests, Orders, Bills)**:
   - **Gap**: No purchase requisition, approval, or PO issuance workflows exist.
   - **Plan (Increments 4-6)**: Implement PR -> PO -> Bill -> Expense/Payment workflow seamlessly integrated with Phase 5 Double-Entry Ledger without duplicate postings.
5. **Procurement Reporting**:
   - **Gap**: No dedicated procurement reports exist.
   - **Plan (Increment 7)**: Deliver Budget vs Actual, PR Report, PO Report, and Vendor Outstanding Bills Report.

### Increment 1 Completed: Standardized Sidebar Parent/Submenu Hierarchy
1. **Sidebar Hierarchy Implementation**:
   - **Finance**:
     - Chart of Accounts (direct link)
     - Income & Expense (Collapsible Parent: Income Report, Expense Report, Misc Income)
     - Expenses (Collapsible Parent: Expense List, Record Expense)
     - Transfers (Collapsible Parent: Transfer List, New Transfer)
     - Journals & Ledgers (Collapsible Parent: Journal Entries, Opening Balances)
     - Financial Statements (Collapsible Parent: Trial Balance, General Ledger, Cash Book, Income & Expenditure)
   - **Budget & Procurement**:
     - Budgets (Collapsible Parent: Budget List, Create Budget, Budget vs Actual)
     - Vendors (Collapsible Parent: Vendor List, Add Vendor)
     - Purchase Requests (Collapsible Parent: Request List, Create Request)
     - Purchase Orders (Collapsible Parent: Purchase Order List)
     - Vendor Bills & Payments (Collapsible Parent: Bill List)
     - Procurement Reports (Collapsible Parent: Budget vs Actual, PR Report, PO Report, Vendor Outstanding Bills)
   - **Donations & Collections**:
     - Collections & Payments (Collapsible Parent: Payment Register, Record Payment)
     - Pledges & Manat (Collapsible Parent: Pledge Register, Create Pledge)
     - Monthly Committee Contributions, Official Receipts, Collection Reports
   - **Puja & Events**:
     - Events & Festivals (Collapsible Parent: All Events, Create Event, Event Categories)
   - **People & Devotees**:
     - Directory & Members (Collapsible Parent: Devotee Directory, Family Units, Executive Committee)
2. **Frontend UI & Accessibility**:
   - Bootstrap 5 collapse with `aria-expanded`, `.sidebar-parent`, `.sidebar-chevron` (rotating 180deg when expanded), `.sidebar-submenu`, and `.sidebar-sublink`.
   - `TempleUI.initSidebarScroll` automatically detects active sublinks, auto-expands parent collapse if not already open, highlights parent with `.active-parent`, and centers the active link in the viewport.
   - Server-side permission enforcement: Parent menus only display if the authenticated user has permission to at least one child item.
3. **Localization**:
   - 100% key parity maintained across `lang/en/` and `lang/bn/` (`nav.php`, `finance.php`, `procurement.php`).
4. **Test Verification**:
   - Added `tests/Feature/SidebarMenuHierarchyTest.php` with 6 feature tests asserting collapse attributes, active parent expansion, role-based visibility, and Bengali localization.
   - All 162 tests passed (1,044 assertions) cleanly. Pint passed.

### Increment 2 Completed: Budget Management
1. **Database Schema & Migrations**:
   - `budgets` table: `budget_code` (`BDG-YYYY-0001` concurrency-safe with retries), `title_en`, `title_bn`, `budget_type` (`annual`, `event`, `general_fund`), `fiscal_year`, `event_id` (nullable foreign key), `total_amount` (`DECIMAL(12,2)`), `status` (`draft`, `submitted`, `approved`, `rejected`, `revised`, `cancelled`), `created_by`, `submitted_by`, `submitted_at`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `rejection_reason`.
   - `budget_items` table: `budget_id`, `financial_account_id` (constrained to expense accounts in Chart of Accounts), `allocated_amount` (`DECIMAL(12,2)`), `notes`. Unique constraint `(budget_id, financial_account_id)`.
2. **Business Rules & Workflows**:
   - Draft → Submitted → Approved / Rejected lifecycle.
   - Strict separation of duties: Budget creators cannot approve their own budgets unless `super_admin`.
   - Mandatory rejection reason required for rejection.
   - Scope uniqueness: duplicate active/approved/submitted budgets for the same event or annual/general fund period are prevented.
   - Approved budgets cannot be silently edited.
3. **Budget vs Actual Variance Analytics**:
   - Compares approved budget line items against actual approved expenses recorded in Phase 5 Finance.
   - Each expense is counted exactly once.
   - Excludes unposted, rejected, and reversed expenses from actual totals.
   - Tracks pending expenses separately without distorting actuals.
   - Unbudgeted expenses for an event are clearly surfaced with allocated = 0.
   - Calculates remaining balance, over-budget amount, and utilization percentages.
4. **User Interface & Dedicated Views**:
   - Budget Listing (`/procurement/budgets`) with server-side DataTables (strictly no row number column, exactly 7 columns).
   - Create Budget (`/procurement/budgets/create`) with dynamic client-side line item addition/removal and live sum calculation.
   - Edit Budget (`/procurement/budgets/{budget}/edit`) for draft/rejected budgets.
   - Budget Show (`/procurement/budgets/{budget}`) with status badge, approval/rejection modals, and variance preview.
   - Dedicated Budget vs Actual Report (`/procurement/reports/budget-vs-actual`) with budget selector, summary cards, and print styling.
5. **Localization & Code Style**:
   - Complete parity between `lang/en/procurement.php` and `lang/bn/procurement.php`.
   - Pint passed with 0 style violations.
6. **Automated Feature Tests**:
   - Added `tests/Feature/BudgetManagementTest.php` with 12 comprehensive feature tests:
     - Access control for guests and devotees.
     - Unique budget code generation (`BDG-YYYY-0001`).
     - Event ID requirement for event budgets.
     - Duplicate active budget prevention.
     - Submission, approval, rejection with reason.
     - Strict separation of duties (preventing self-approval).
     - Accurate Budget vs Actual calculations and single expense counting.
     - Server-side DataTables JSON response verification (strictly 7 columns, no row number column).
     - Clean Bengali localization rendering.
   - Full test suite: **174 passed (1,104 assertions)** with zero failures or regressions.

### Increment 3 Completed: Vendor Management
1. **Database Schema & Migrations**:
   - `vendors` table (`2026_10_29_000021_create_vendors_table.php`):
     - `vendor_code`: `VND-0001` format generated with concurrency-safe row-level locking and retry loop. Unique database constraint.
     - `name`, `contact_person`, `phone` (indexed), `email`, `address`, `vendor_type` (indexed), `tax_id` (trade license / TIN / BIN, indexed), `notes`, `is_active` (boolean, indexed).
     - `created_by`, `updated_by` (foreign keys to `users`, nullOnDelete), timestamps, and softDeletes.
   - `expenses` table integration: Added nullable `vendor_id` foreign key referencing `vendors(id)` with `nullOnDelete()`, while seamlessly supporting historical `vendor_name` and `vendor_phone` records.
2. **Business Rules, Validation & Protection**:
   - Unique phone and tax ID validation accounting for soft deletes (`Rule::unique('vendors', ...)->whereNull('deleted_at')`), while safely allowing vendors to retain their own credentials upon updating.
   - Protected deactivation / deletion: Vendors with linked expenses or financial records cannot be hard-deleted or soft-deleted; they are deactivated by toggling `is_active`.
   - Comprehensive audit logging via `AuditLogger::log` for vendor creation, updates, status toggles, and deletions.
3. **Select2 AJAX Search Endpoint**:
   - `GET /procurement/vendors/search` returning `{ results: [ { id, text, name, vendor_code, phone, vendor_type } ] }`.
   - Default filter excludes inactive vendors, while supporting optional category filtering and search queries matching name, code, phone, or contact person.
4. **User Interface & Dedicated Views**:
   - **Vendor List (`/procurement/vendors`)**: Server-side DataTables with strictly NO row number / `#` column (exactly 7 business data columns + actions), summary metrics cards (Total Vendors, Active Vendors, Inactive Vendors, Total Paid Expenditure), category and status filters.
   - **Add Vendor (`/procurement/vendors/create`)**: Clean form with vendor category dropdown, contact details, tax ID, notes, active status toggle, and button loading indicators.
   - **Edit Vendor (`/procurement/vendors/{vendor}/edit`)**: Pre-populated update form preserving unique constraints on self.
   - **Vendor Profile & History (`/procurement/vendors/{vendor}`)**: Profile overview card with contact information, status badge, quick toggle button, and complete transaction history table listing linked expenses, vouchers, and statuses without duplicate counting.
5. **Localization & Code Style**:
   - 100% key parity between `lang/en/procurement.php` and `lang/bn/procurement.php` for all vendor fields, categories, status badges, and feedback messages.
   - 100% PSR-12 code compliance verified with `./vendor/bin/pint --test`.
6. **Automated Feature Tests**:
   - Added `tests/Feature/VendorManagementTest.php` with 13 comprehensive feature tests:
     - Access control for guests and devotees.
     - Unique vendor code generation in `VND-0001` format.
     - Authorized creation with complete metadata and audit logging.
     - Duplicate phone and tax ID prevention.
     - Update allowing same phone and tax ID.
     - Active status toggling.
     - Protected deletion prevention when linked expenses exist.
     - Deletion allowed for unused vendors.
     - Select2 AJAX search format and inactive vendor exclusion.
     - Server-side DataTables JSON response verification (strictly NO row number column).
     - Clean Bengali localization rendering.
   - Full test suite: **187 passed (1,174 assertions)** with zero failures or regressions.


### Increment 4 Completed: Purchase Requests & Purchase Orders
1. **Database Schema & Migrations**:
   - `purchase_requests` table (`2026_10_30_000022_create_purchase_requests_and_items_tables.php`):
     - `request_number`: `PRQ-YYYY-0001` format generated with concurrency-safe row-level locking (`lockForUpdate()`) and unique database constraint.
     - `title`, `event_id` (nullable foreign key), `budget_id` (nullable foreign key), `request_date`, `status` (`draft`, `submitted`, `approved`, `rejected`, `cancelled`), `total_estimated_amount` (`DECIMAL(12,2)`), `justification`, `notes`, `rejection_reason`, `is_converted_to_po` (boolean lock).
     - Audit timestamps and foreign keys: `requested_by`, `created_by`, `submitted_by`, `submitted_at`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`.
   - `purchase_request_items` table:
     - `purchase_request_id`, `description`, `quantity` (`DECIMAL(10,2)`), `unit`, `estimated_unit_price` (`DECIMAL(12,2)`), `estimated_total` (`DECIMAL(12,2)`), `financial_account_id` (constrained to expense accounts), `budget_item_id`, `notes`.
   - `purchase_orders` table (`2026_10_30_000023_create_purchase_orders_and_items_tables.php`):
     - `order_number`: `PO-YYYY-0001` format generated with concurrency-safe locking and unique database constraint.
     - `purchase_request_id` (nullable foreign key to source request), `vendor_id` (constrained to active vendors), `event_id`, `budget_id`, `order_date`, `expected_delivery_date`, `status` (`draft`, `approved`, `sent`, `completed`, `cancelled`).
     - Money fields: `subtotal` (`DECIMAL(12,2)`), `discount_amount` (`DECIMAL(12,2)`), `total_amount` (`DECIMAL(12,2)`).
     - Text fields: `terms_conditions`, `delivery_address`, `notes`.
     - Audit fields: `approved_by`, `approved_at`, `created_by`, `updated_by`, timestamps, and softDeletes.
   - `purchase_order_items` table:
     - `purchase_order_id`, `purchase_request_item_id` (nullable), `description`, `quantity` (`DECIMAL(10,2)`), `unit`, `unit_price` (`DECIMAL(12,2)`), `line_total` (`DECIMAL(12,2)`), `financial_account_id` (constrained to expense accounts), `budget_item_id` (nullable), `notes`.
2. **Business Rules, Validation & Ledger Invariant**:
   - **Safe Request-to-Order Conversion**:
     - Only requests in `approved` status and where `is_converted_to_po = false` can be converted into purchase orders.
     - Conversion is executed inside a database transaction with pessimistic row-locking (`PurchaseRequest::lockForUpdate()`) to eliminate race conditions and prevent concurrent double-conversion.
     - Marks `is_converted_to_po = true`. If the resulting purchase order is later cancelled, the lock is safely released (`is_converted_to_po = false`).
   - **Active Vendor Validation**:
     - Purchase order creation and conversion strictly validates that the chosen vendor exists and has `is_active = true`.
   - **Server-Side Financial Integrity**:
     - Ignores client-side tampered totals. Subtotal, discount adjustments, line totals, and grand total are strictly calculated and rounded on the server (`DECIMAL(12,2)`).
   - **Strict Separation of Duties**:
     - Order creators cannot approve their own purchase orders unless they possess the `super_admin` role.
   - **Finance Ledger Invariant**:
     - Purchase requests and purchase orders are procurement documents. Neither action generates rows in `expenses` or `journal_entries`.
3. **Dedicated Views & UI Implementation**:
   - **Purchase Request Views** (`resources/views/procurement/requests/`):
     - `index.blade.php`: Server-side DataTables strictly without row number / `#` column (exactly 7 business data columns + actions), summary metrics cards, status filter.
     - `create.blade.php` & `edit.blade.php`: Dynamic client-side line item addition/removal with automatic recalculation and event/budget selector.
     - `show.blade.php`: Full requisition details, budget utilization alert, status badges, and action modals for submission, approval, rejection, and cancellation.
   - **Purchase Order Views** (`resources/views/procurement/orders/`):
     - `index.blade.php`: Server-side DataTables strictly without row number / `#` column (7 business data columns + actions), summary metrics cards (Total Orders, Active Orders, Completed Orders, Total Order Value), vendor filter, and status filter.
     - `create.blade.php`: Dual-mode creation interface (converts from approved requisition with pre-filled line items or accepts direct purchase orders) with vendor selection and delivery details.
     - `edit.blade.php`: Draft PO modification form with server-validated recalculations.
     - `show.blade.php`: Comprehensive order overview, vendor profile summary, line items table, delivery & terms card, status progression, and action modals (Approve, Mark as Sent, Mark as Completed, Cancel).
     - `print.blade.php`: Clean printable PO voucher with temple branding, order and vendor details, itemized breakdown, terms, and signature lines (Prepared By, Authorized Signatory, Vendor Acknowledgment).
4. **Bilingual Localization (EN & BN)**:
   - 100% key parity maintained between `lang/en/procurement.php` and `lang/bn/procurement.php` for all purchase request and order terms, form fields, validation messages, action prompts, status badges, and voucher text.
5. **Automated Feature Tests**:
   - `tests/Feature/PurchaseRequestTest.php` (15 tests, 71 assertions):
     - Access control (guests, unauthorized users).
     - Unique code generation (`PRQ-YYYY-0001`).
     - Validation of items, positive amounts, budget/event relationships.
     - Submission and cancellation workflow.
     - Strict separation of duties preventing self-approval.
     - Approval and rejection requiring reason.
     - Zero finance ledger entries invariant.
     - DataTables JSON response strictly without row number column.
     - Bengali locale rendering without errors.
   - `tests/Feature/PurchaseOrderTest.php` (14 tests, 69 assertions):
     - Access control (guests, unauthorized devotees).
     - Non-approved or already converted request rejection.
     - Concurrency-safe conversion with row locking.
     - Duplicate conversion rejection under concurrency.
     - Server-side calculation ignoring tampered totals.
     - Inactive vendor validation.
     - Strict separation of duties preventing creator from self-approving.
     - Authorized approval workflow.
     - Order lifecycle state machine (`draft` → `approved` → `sent` → `completed`, and cancellation releasing source PR lock).
     - Zero finance ledger entries invariant.
     - DataTables JSON response strictly without row number column.
     - Bengali locale rendering without errors.
   - Total test suite: **216 passed tests (1,314 assertions)** with zero failures or regressions.
   - 100% PSR-12 code compliance verified with `./vendor/bin/pint --test`.


---

## UI Maintenance: Code Clutter Removal & Full Localization Parity
1. **Internal Code Clutter Cleaned from Frontend Views**:
   - **Form Selects & Dropdowns**:
     - Removed internal code prefixes/suffixes (VND-0001, BDG-2026-0001, EVT-2026-0001, ACC-001, PER-0001, FAM-0001) from option labels across Finance, Procurement, Donations, Events, People, and Family views.
     - Preserved exact option value attributes (primary database IDs) so backend FormRequests, queries, and controller inputs remain 100% identical and undisturbed.
   - **Show Pages, Detail Cards, Headings & Breadcrumbs**:
     - Vendor Show/Edit: Removed (VND-0001) from page titles, breadcrumbs, and header badges, highlighting the clean human-friendly Vendor Name.
     - Budget Show/Edit: Replaced raw budget code headers with clean Budget Titles.
     - Purchase Request Show/Edit: Replaced request code badges with descriptive Request Titles.
     - Purchase Orders: Cleaned secondary vendor and account codes from items tables while keeping official order_number identifiers.
     - Events: Removed EVT-2026-0001 badges from headings, breadcrumbs, and dashboard cards, presenting clean Event Titles.
     - Committees & Devotees: Removed PER-0001 badges from member lists, history tables, and modal selects.
     - Family Units: Removed FAM-0001 and PER-0001 badges from household headers, member lists, and dropdown selectors.
   - **Document Invariants Preserved**:
     - Official printable vouchers and accounting receipts (orders/print.blade.php, receipts/print.blade.php) retain their official document numbers as legally required.
2. **Comprehensive Localization Parity**:
   - Discovered and resolved 31 missing keys across common.php, donations.php, finance.php, and procurement.php.
   - Verified 100% key parity between lang/en/ and lang/bn/ with zero missing keys.
   - Localized all action buttons, modal dismiss triggers (Close -> common.close), payment methods, and filter options.
3. **Verification & Regression Testing**:
   - Full automated test suite: 216 tests passed (1,314 assertions) cleanly with zero regressions.
   - Code style verified with ./vendor/bin/pint --test (100% PSR-12 compliant).

---

## Phase 6 — Increment 5, Stage 1: Vendor Bills Management (FINAL APPROVED)

1. **Database Schema & Migrations**:
   - `vendor_bills` table (`2026_11_01_000024_create_vendor_bills_and_items_tables.php`):
     - `bill_number`: Unique sequential code `BIL-YYYY-0001` with concurrency-safe locking (`lockForUpdate()`) and unique database index.
     - `vendor_id`: Constrained to active vendors.
     - `purchase_order_id`: Optional foreign key to linked purchase orders.
     - `event_id`: Optional dimension for event/puja scope.
     - `budget_id`: Optional dimension for budget plan.
     - `vendor_invoice_number`: Vendor's official invoice/memo reference, validated unique per vendor (`vendor_id` + `vendor_invoice_number`).
     - `bill_date`: Incurred bill date.
     - `due_date`: Payment due date (`>= bill_date`).
     - `status`: Workflow state machine (`draft`, `submitted`, `verified`, `approved`, `rejected`, `cancelled`).
     - `payment_status`: Stage 1 placeholder (`unpaid`, `partially_paid`, `paid`).
     - Monetary fields: `subtotal`, `discount_amount`, `tax_amount`, `total_amount` (`DECIMAL(12,2)`).
     - Text & files: `notes`, `document_path` (scanned invoice copy up to 5MB: PDF/JPG/PNG).
     - Accounting linkages: `journal_entry_id` (posted Accounts Payable journal entry), `reversal_journal_entry_id` (balanced reversal entry if cancelled after approval).
     - Audit trail: `submitted_by`, `submitted_at`, `verified_by`, `verified_at`, `verification_notes`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `rejection_reason`, `cancelled_by`, `cancelled_at`, `cancellation_reason`, `created_by`, `updated_by`, timestamps, soft deletes.
   - `vendor_bill_items` table:
     - `vendor_bill_id`, `purchase_order_item_id` (nullable), `financial_account_id` (constrained to active expense accounts), `budget_item_id` (nullable).
     - `description`, `quantity` (`DECIMAL(10,2)`), `unit`, `unit_price` (`DECIMAL(12,2)`), `discount_amount` (`DECIMAL(12,2)`), `line_total` (`DECIMAL(12,2)`), `notes`.
   - Granular RBAC Permissions seeded and assigned to roles:
     - `view_vendor_bills`, `manage_vendor_bills`, `verify_vendor_bills`, `approve_vendor_bills`, `cancel_vendor_bills`.

2. **Accrual Double-Entry Accounting Ledger Invariant**:
   - **Accrual Accounting Enforcement**:
     - Bill approval posts an accrual liability transaction to the General Ledger via `FinancePostingService::postVendorBill()`.
     - **Debits**: Line-item expense accounts (from Chart of Accounts, e.g. 5101, 5102), scaled proportionally if header-level discount/tax exists.
     - **Credit**: Accounts Payable liability account (`2010`).
     - **Cash & Bank Balance Invariant**: Bank, Cash, and MFS balances are completely untouched. Approving a bill never creates an `Expense` payment record.
     - **Idempotency**: Strict check prevents duplicate journal postings if approval is retried.
     - **Immutable Reversal**: Cancelling an approved bill generates an inverse balanced reversal journal entry (`FinancePostingService::reverseVendorBill()`), setting the original journal entry status to `reversed` and recording `reversal_journal_entry_id` on the bill without deleting history.

3. **Cumulative Purchase Order Billing Safeguard**:
   - Dynamic PO item pre-population via `GET /procurement/bills/po-items/{po}`.
   - Tracks cumulative billed quantity across all active (non-rejected, non-cancelled) bills.
   - Strictly enforces that cumulative billed quantity cannot exceed the PO item's ordered quantity.
   - Prevents over-billing and tracks unbilled remaining quantities and amounts on both `PurchaseOrder` and `PurchaseOrderItem` models.

4. **Dedicated Views & UI Implementation**:
   - **Bill Index** (`resources/views/procurement/bills/index.blade.php`):
     - Server-side DataTables strictly without row number / `#` column (8 business data columns: Bill Number, Vendor, Invoice Ref / PO, Bill Date, Due Date, Total Amount, Status, Actions).
     - Summary metrics cards: Total Bills, Approved Payables, Pending Verification, Overdue Bills.
     - Multi-dimension filter card: Status, Vendor, Event.
   - **Bill Create & Edit** (`create.blade.php` & `edit.blade.php`):
     - Optional PO selector with instant AJAX auto-fill of vendor, items, and quantities.
     - Dynamic line items table with add/remove rows, expense account selection, live subtotal, tax, discount, and grand total recalculation.
     - File upload for invoice evidence with MIME and size constraints.
   - **Bill Show** (`resources/views/procurement/bills/show.blade.php`):
     - Complete vendor details card, line items table, financial breakdown, file download link, PO link card, General Ledger posting card (with entry link), and comprehensive audit timeline.
     - Separation of duties enforcement: Creator cannot self-approve unless `super_admin`.
     - Action modals for Submit, Verify (with notes), Approve, Reject (mandatory reason), Cancel (mandatory reason).
   - **Printable Voucher** (`print.blade.php`):
     - Official bill voucher with temple header, vendor metadata, itemized table, GL posting breakdown, status watermark if cancelled, and 4-block signatures (Prepared By, Verified By, Approved By, Vendor Acknowledgment).
   - **Vendor Profile Integration** (`vendors/show.blade.php`):
     - Direct "+ Create Bill" action shortcut.
     - Dedicated Vendor Bills Summary card displaying Total Billed, Total Outstanding, and a table of bills for the vendor.

5. **Bilingual Localization (EN & BN)**:
   - 100% key parity across `lang/en/procurement.php` and `lang/bn/procurement.php` with 38 new vendor bills keys and zero missing translations.

6. **Automated Feature Tests**:
   - Created `tests/Feature/VendorBillTest.php` with **23 comprehensive tests (119 assertions)**:
     - Access control (guests, unauthorized users).
     - Unique sequential code generation (`BIL-YYYY-0001`).
     - Inactive vendor rejection.
     - Unique vendor invoice number per vendor enforcement.
     - PO vendor mismatch and PO status eligibility checks.
     - Partial billing from PO and cumulative over-billing prevention.
     - Server-side financial calculation ignoring tampered client totals.
     - Non-expense account rejection.
     - Workflow state machine: Draft → Submitted → Verified → Approved.
     - Strict separation of duties preventing creator from self-approving (with Super Admin override).
     - Mandatory rejection and cancellation reasons.
     - Accrual double-entry posting to General Ledger (Debits: Expense Accounts, Credit: AP 2010; Cash/Bank untouched).
     - Idempotency preventing duplicate journal entries.
     - Approved bill cancellation creating balanced reversal journal entry.
     - Vendor outstanding calculation accuracy.
     - Server-side DataTables JSON response strictly omitting row number columns.
     - Clean Bengali localization rendering without missing keys.
     - PO items remaining quantity API endpoint verification.
   - Total test suite: **239 passed tests (1,433 assertions)** with zero failures or regressions.
   - 100% PSR-12 code compliance verified with `./vendor/bin/pint --test`.


---

## Phase 6 — Increment 5, Stage 2: Vendor Payments & Payment Processing (FINAL APPROVED)

1. **Database Schema & Migrations**:
   - `database/migrations/2026_11_01_000025_create_vendor_payments_and_allocations_tables.php`:
     - Added `paid_amount` (`DECIMAL(12,2) DEFAULT 0.00`) and `payment_status` (`VARCHAR(20) DEFAULT 'unpaid'`) to `vendor_bills`.
     - Created `vendor_payments` table:
       - `payment_number`: Unique sequential code in `VPY-YYYY-0001` format.
       - `vendor_id`: Constrained to `vendors` table.
       - `financial_account_id`: Constrained to liquid asset accounts in `financial_accounts` (`account_type = 'asset'`).
       - `payment_date`: Date payment disbursement occurred.
       - `payment_method`: Supported payment methods (`cash`, `bank_transfer`, `cheque`, `bkash`, `nagad`, `rocket`, `other`).
       - `amount`: Total payment amount (`DECIMAL(12,2)`).
       - `reference_number`: Cheque number, bank transfer ID, or MFS reference.
       - `status`: State machine (`draft`, `submitted`, `verified`, `approved`, `posted`, `rejected`, `cancelled`, `reversed`).
       - `notes`: Optional description/remarks.
       - `document_path`: Uploaded disbursement voucher, receipt or cheque copy.
       - `journal_entry_id`: Link to General Ledger settlement entry.
       - `reversal_journal_entry_id`: Link to General Ledger reversal entry if reversed after posting.
       - Audit columns: `submitted_by`, `submitted_at`, `verified_by`, `verified_at`, `verification_notes`, `approved_by`, `approved_at`, `posted_by`, `posted_at`, `rejected_by`, `rejected_at`, `rejection_reason`, `cancelled_by`, `cancelled_at`, `cancellation_reason`, `reversed_by`, `reversed_at`, `reversal_reason`, `created_by`, `updated_by`, timestamps, soft deletes.
     - Created `vendor_payment_allocations` table:
       - `vendor_payment_id`: Foreign key to `vendor_payments`.
       - `vendor_bill_id`: Foreign key to `vendor_bills`.
       - `allocated_amount`: Amount applied to this bill (`DECIMAL(12,2)`).
       - `notes`: Optional allocation note.
   - Granular RBAC Permissions seeded and assigned to roles (`temple_admin`, `treasurer`, `committee_member`):
     - `view_vendor_payments`, `manage_vendor_payments`, `submit_vendor_payments`, `verify_vendor_payments`, `approve_vendor_payments`, `post_vendor_payments`, `cancel_vendor_payments`, `reverse_vendor_payments`.

2. **Accrual Settlement & General Ledger Invariant**:
   - **Double-Entry Settlement**:
     - Bill settlement is executed via `FinancePostingService::postVendorPayment()`.
     - **Debit**: Accounts Payable liability account (`2010`). Reduces the temple's payable liability.
     - **Credit**: Selected Liquid Asset Account (e.g. `1010` Cash in Hand, `1020` Main Bank Account, `1030` MFS). Reduces cash/bank balance.
     - **Mathematical Balance**: Debit = Credit = Payment Amount.
     - **Bill Balance Settlement**: Atomically increments bill `paid_amount` by the allocated amount and updates `payment_status` (`paid` if fully paid, or `partially_paid`).
     - **Idempotency**: Strict check prevents duplicate journal creation if post is called again.
     - **No Expense Duplication**: Payments never create a second expense record; they settle the recognized AP liability.
   - **Immutable Reversal**:
     - Reversing a posted payment via `FinancePostingService::reverseVendorPayment()` creates an inverse journal entry (`createReversal()`), sets payment status to `reversed`, restores affected bills' `paid_amount` and `payment_status`, and preserves complete audit history with mandatory reversal reason.

3. **Bill Payment Allocation Rules & Concurrency**:
   - Payments must allocate funds only against approved bills belonging to the selected active vendor.
   - Validates that total allocations exactly match total payment amount.
   - Prevents allocating more than the bill's current outstanding balance (`total_amount - paid_amount`).
   - Re-validates outstanding balances with database locking during final posting.

4. **UI & Blade Implementation**:
   - **Payments Index** (`resources/views/procurement/payments/index.blade.php`):
     - Server-side DataTables strictly without row number / `#` column (8 business columns: Payment Number, Vendor, Date, Method & Ref, Account, Amount, Status, Actions).
     - Summary metrics: Total Payments, Total Paid (Posted), Pending Verification, Drafts.
     - Filters: Status, Vendor, Payment Method.
   - **Payment Create & Edit** (`create.blade.php` & `edit.blade.php`):
     - Dynamic AJAX bill loading when vendor dropdown changes (`GET /procurement/payments/payable-bills/{vendor}`).
     - Interactive allocations table with "Auto-fill Balance" helper button and live unallocated difference balance indicator.
     - Supporting document upload (PDF/JPG/PNG up to 5MB).
   - **Payment Show** (`resources/views/procurement/payments/show.blade.php`):
     - Disbursement metadata, settled bills breakdown, General Ledger journal entry card (with clickable journal voucher link), and complete audit timeline.
     - Separation of duties enforcement: Creator cannot self-approve unless `super_admin`.
     - Modals for Submit, Verify (notes), Authorize, Post to Ledger, Approve & Post, Reject (mandatory reason), Cancel (mandatory reason), Reverse (mandatory reason).
   - **Printable Voucher** (`print.blade.php`):
     - Sri Sri Radha Krishna Temple header, payment metadata, bill settlement breakdown, Bengali/English wording, status watermark if reversed/cancelled, and 4-signature block (Prepared By, Verified By, Treasurer, Payee/Vendor Acknowledgment).
   - **Navigation & Integrations**:
     - Added "Vendor Payments" submenu under "Budget & Procurement" parent menu in `resources/views/partials/sidebar.blade.php`.
     - Integrated recent payments list and "+ Record Payment" action in Vendor Profile (`procurement/vendors/show.blade.php`).
     - Integrated payment allocations list and "Pay Bill" action in Vendor Bill Show (`procurement/bills/show.blade.php`).

5. **Bilingual Localization (EN & BN)**:
   - 100% key parity across `lang/en/procurement.php` and `lang/bn/procurement.php` (440 keys each) with zero missing keys or hardcoded English strings.

6. **Automated Feature Tests & Targeted Read-Only Audit**:
   - Created `tests/Feature/VendorPaymentTest.php` with **25 comprehensive tests (122 assertions)**:
     - Access control (guest, unauthorized devotee).
     - Unique sequential code generation (`VPY-YYYY-0001`).
     - Inactive vendor rejection.
     - Non-asset payment account rejection.
     - Positive amount and allocation presence validation.
     - Sum of allocations matching payment amount.
     - Allocation exceeding bill outstanding balance rejection.
     - Unapproved bill and cross-vendor bill allocation rejection.
     - Workflow progression: Draft → Submitted → Verified → Approved → Posted.
     - Strict separation of duties preventing self-approval.
     - Super Admin approval override.
     - Mandatory reasons for rejection and cancellation.
     - General Ledger double-entry posting (Debits AP 2010, Credits Asset Account).
     - Atomic bill `paid_amount` and `payment_status` updates (partial and full).
     - Idempotency preventing duplicate journal entries.
     - Payment reversal with inverse journal and restored bill balances.
     - Vendor outstanding calculation accuracy.
     - DataTables JSON strictly omitting row number / `#` column.
     - Clean Bengali localization rendering.
     - Payable bills AJAX endpoint verification.
   - Created `tests/Feature/VendorPaymentAuditTest.php` with **5 targeted audit tests (53 assertions)**:
     - Area 1: Reversal and Ledger Integrity — **PASS** (original journal marked `reversed`, reversal entry linked via `reversed_journal_id`, balanced debit/credit mirror, voucher links preserved).
     - Area 2: Concurrent Payment Posting — **PASS** (database row locking with `lockForUpdate()` prevents concurrent over-settlement; bill outstanding never negative; unposted voucher remains `approved` without journal entries).
     - Area 3: Multiple Payment Reversal — **PASS** (selective reversal of one partial payment restores paid and outstanding balances and transitions bill status accurately, e.g. `paid` $\rightarrow$ `partially_paid` $\rightarrow$ `unpaid`).
     - Area 4: Financial Account Validation — **PASS** (multi-tier rejection of inactive or non-asset accounts at Form Request, Service, and Posting layers).
     - Area 5: Approval and Posting Authorization — **PASS** (separation of duties enforced; direct approval of draft/submitted rejected; unauthorized HTTP requests rejected with 403; posting idempotency guaranteed).
   - **Combined Payment Test Suite**: **30 passed tests (175 assertions)** in 31.98s.
   - **Full Application Test Suite**: **269 passed tests (1,608 assertions)** across all modules with zero failures and zero regressions.
   - **Code Style (Laravel Pint)**: 100% PSR-12 code compliance verified with `./vendor/bin/pint --test`.

7. **Known System Limitations (Documented & Acknowledged)**:
   - **No Unallocated Vendor Advances**: Vendor payments strictly require allocation against existing, verified, and approved vendor bills. Standalone advance payments without an approved bill cannot be recorded; advance deposits must follow the approved billing workflow or formal deposit account structure.
   - **No External Automated Payment Gateway Webhooks**: Payment recording represents an internal accounting voucher workflow managed by authorized temple financial officers (Cash, Bank Transfer, Cheque, MFS vouchers). Real-time external payment gateway IPN/webhooks (e.g. bKash/Nagad checkout callbacks) are not implemented.
   - **Concurrency Scope**: Database row locking (`lockForUpdate()`) provides strict ACID isolation on single-instance and primary-replica MySQL installations. Active-active multi-master distributed clustering would require distributed lock managers (e.g. Redis mutex).

---

## Phase: Public Transparency & Payment Portal — Admin Panel Implementation (COMPLETED)

### Architectural Foundation & Scope Boundaries
- **Strict Scope Separation**: Only admin-side management, verification, and preparation modules were implemented. Zero public-facing pages, public routes, guest forms, or CAPTCHA were introduced.
- **Liturgical & Accounting Independence**: Liturgical/closing event status (`draft`, `upcoming`, `active`, `closing`, `closed`) is completely independent from public portal publication status (`draft`, `published`, `unpublished`). Publishing/unpublishing events or documents has zero impact on temple financial records or ledger balances.
- **Double-Entry Financial Invariant**: Complaints and inquiries NEVER alter financial records directly. Any refund or adjustment must proceed through authorized voucher reversal procedures. Public payment verification strictly integrates with `CollectionPaymentService::verifyPayment()`, issuing official receipts and posting balanced double-entry ledger entries.

---

### Increments Implemented

#### Increment 1: Public Event Management (Admin)
- **Database Schema**:
  - Migration `2026_11_02_000026_add_public_portal_fields_to_events_table.php` added: `slug` (unique, indexed), `banner_image_path`, `short_description_en`, `short_description_bn`, `publication_status` (`draft`, `published`, `unpublished`), `published_at`, `published_by`, `unpublished_at`, `unpublished_by`.
- **Model Extensions** (`app/Models/Event.php`):
  - Constants: `PUB_STATUS_DRAFT`, `PUB_STATUS_PUBLISHED`, `PUB_STATUS_UNPUBLISHED`.
  - Relations: `publisher()`, `unpublisher()`.
  - Helpers: `isPublished()`, `isUnpublished()`, `scopePublished()`.
  - Accessors: `short_description`, `banner_url`.
- **Form Request & Controller** (`UpdatePublicEventRequest.php`, `PublicEventController.php`):
  - Gated by permissions: `view_public_events`, `manage_public_events`, `publish_public_events`.
  - Slug validation with lowercase hyphenated format regex and cross-event uniqueness.
  - Banner image validation (JPEG, PNG, WebP up to 3MB) and safe removal.
  - AJAX publish/unpublish toggling with audit logging.
- **Views**:
  - `portal/events/index.blade.php`: Server-side DataTables (strictly 7 data columns, NO `#` column), publication status filter, year filter, AJAX status toggle.
  - `portal/events/edit.blade.php`: Slug editor, banner file upload with live preview, bilingual short description textareas with live character counters.
  - `portal/events/show.blade.php`: Public preview hero card, metadata audit timeline.
- **Feature Tests** (`tests/Feature/PublicPortal/PublicEventTest.php`): 11 passed (64 assertions).

#### Increment 2: Public Event Report & Document Management (Admin)
- **Database Schema**:
  - Migration `2026_11_02_000027_create_event_documents_table.php`:
    - `id`, `event_id`, `title_en`, `title_bn`, `document_type` (`financial_report`, `audit_report`, `supporting_document`, `other`), `file_path`, `file_name`, `file_size`, `mime_type`, `description_en`, `description_bn`, `publication_status` (`draft`, `published`, `unpublished`), `published_at`, `published_by`, `unpublished_at`, `unpublished_by`, `uploaded_by`, timestamps, soft deletes.
- **Model** (`app/Models/EventDocument.php`):
  - Fillables, casts, scopes (`scopePublished`, `scopeForEvent`, `scopeOfType`), helper accessors (`formatted_file_size`, localized `title`, `document_type_name`).
- **Secure File Storage & Streaming Controller** (`EventDocumentController.php`):
  - Gated by `view_event_documents`, `manage_event_documents`, `publish_event_documents`, `delete_event_documents`.
  - Non-guessable randomized storage on private local disk (`private/event_documents/`).
  - Secure permission-checked streaming/download endpoint supporting both attachment download and inline preview with correct MIME headers.
  - Optional replacement file upload with automatic safe deletion of deprecated physical files.
- **Views**:
  - `portal/documents/index.blade.php`: Server-side DataTables (7 data columns, NO `#` column), event and document type filters.
  - `portal/documents/create.blade.php`: Event picker, document type, file upload (max 10MB; PDF/JPG/PNG), publish immediately switch.
  - `portal/documents/edit.blade.php`: Metadata editing with optional file replacement.
  - `portal/documents/show.blade.php`: Details view, preview widget, publication and audit history.
- **Feature Tests** (`tests/Feature/PublicPortal/EventDocumentTest.php`): 14 passed (71 assertions).

#### Increment 3: Receiver Account Management (Admin)
- **Database Schema**:
  - Migration `2026_11_02_000028_create_receiver_accounts_table.php`:
    - `id`, `payment_method` (`bkash`, `nagad`, `rocket`, `bank_transfer`, `other`), `account_name`, `account_number`, `account_type` (`merchant`, `personal`, `current`, `savings`, `agent`), `bank_name`, `branch_name`, `routing_number`, `swift_code`, `qr_code_image_path`, `instructions_en`, `instructions_bn`, `is_active`, `is_verified`, `verified_at`, `verified_by`, `display_order`, `financial_account_id` (foreign key to chart of accounts), audit fields, timestamps, soft deletes.
- **Model** (`app/Models/ReceiverAccount.php`):
  - Scopes (`scopeActive`, `scopeVerified`, `scopePublicReady`, `scopeMethod`), accessors (`qr_code_url`, `method_name`, `type_name`, `instructions`).
- **Controller** (`ReceiverAccountController.php`):
  - Gated by `view_receiver_accounts`, `manage_receiver_accounts`, `verify_receiver_accounts`.
  - Server-side DataTables (7 columns, NO `#` column), dynamic active status toggling, verification toggling.
  - QR code image uploads (max 2MB) with safe replacement and deletion.
  - Optional mapping directly to an active liquid asset account (Cash/Bank) in the double-entry chart of accounts.
- **Views**:
  - `portal/receivers/index.blade.php`: DataTable with method, active, and verification status filters.
  - `portal/receivers/create.blade.php`: Method picker with conditional bank transfer fields, QR code upload, financial account selector.
  - `portal/receivers/edit.blade.php`: Edit form with QR code preview/removal option.
  - `portal/receivers/show.blade.php`: Account overview, QR code display, ledger account card, verification actions.
- **Feature Tests** (`tests/Feature/PublicPortal/ReceiverAccountTest.php`): 13 passed (72 assertions).

#### Increment 4: Public Payment Submission Management (Admin Verification Workflow)
- **Database Schema Extensions**:
  - Migration `2026_11_02_000029_add_public_portal_fields_to_collection_payments_table.php`:
    - `receiver_account_id`, `sender_mobile`, `payment_proof_path`, `is_public_submission` (indexed), `public_donor_name`, `public_donor_phone`, `public_donor_email`.
- **Model Extensions** (`app/Models/CollectionPayment.php`):
  - Added new fillables, casts, `receiverAccount()` relation, `getPaymentProofUrlAttribute()`, enhanced `payer_display_name` to resolve public donor names gracefully.
- **Controller** (`PublicPaymentController.php`):
  - Gated by `view_public_payments`, `verify_public_payments`, `reject_public_payments`.
  - Server-side DataTables (8 columns, NO `#` column) with status, event, receiver, and method filters.
  - **Verification & Posting**:
    - Optional automatic guest `Person` profile creation or linking to an existing registered devotee.
    - Atomically calls `CollectionPaymentService::verifyPayment()`.
    - Generates official money receipt (`Receipt`).
    - Posts double-entry journal entry to the general ledger (Debits liquid asset, Credits event donation income).
  - **Rejection**:
    - Atomically calls `CollectionPaymentService::rejectPayment()` with mandatory rejection reason.
    - Zero financial ledger entries created.
  - **Devotee Linking & Autocomplete**:
    - Autocomplete search endpoint (`search-persons`) and suggestion matching by phone number.
- **Views**:
  - `portal/payments/index.blade.php`: Server-side DataTable, verify modal, reject modal with AJAX handling.
  - `portal/payments/show.blade.php`: Full submission review, donor profile card, receiver details, payment proof preview, posted accounting journal entries.
- **Feature Tests** (`tests/Feature/PublicPortal/PublicPaymentTest.php`): 12 passed (64 assertions).

#### Increment 5: Complaint Management (Admin-Side)
- **Database Schema**:
  - Migration `2026_11_02_000030_create_complaints_table.php`:
    - `id`, `tracking_number` (unique, `CMP-YYYY-0001`), `complainant_name`, `complainant_phone`, `complainant_email`, `person_id`, `event_id`, `collection_payment_id`, `category` (`payment_issue`, `event_management`, `prasadam_service`, `general_inquiry`, `suggestion`, `other`), `subject`, `description`, `attachment_path`, `status` (`submitted`, `under_review`, `resolved`, `rejected`, `closed`), `priority` (`low`, `medium`, `high`, `urgent`), `assigned_to`, `admin_response`, `internal_notes`, `resolved_at`, `resolved_by`, `closed_at`, `closed_by`, timestamps, soft deletes.
- **Model** (`app/Models/Complaint.php`):
  - Concurrency-safe unique tracking number generator (`generateTrackingNumber()`).
  - Relations to `person`, `event`, `payment`, `assignee`, `resolver`, `closer`.
- **Controller** (`ComplaintController.php`):
  - Gated by `view_complaints`, `manage_complaints`, `respond_complaints`, `assign_complaints`, `resolve_complaints`.
  - DataTables list (8 columns, NO `#` column) with status, category, priority, assignee, and event filters.
  - Actions: Assign staff, submit official response, resolve with mandatory note, reject with mandatory reason, close, update priority, append timestamped internal notes, link to registered devotee.
- **Views**:
  - `portal/complaints/index.blade.php`: Server-side DataTable, quick respond modal, quick resolve modal.
  - `portal/complaints/show.blade.php`: Complete inquiry overview, complainant card, related event/payment context, response editor, internal notes log, assignment sidebar.
- **Feature Tests** (`tests/Feature/PublicPortal/ComplaintTest.php`): 14 passed (64 assertions).

---

### Increment 6: Permissions, Navigation & Localization
- **RBAC Permissions**:
  - Seeded into `RoleAndPermissionSeeder.php` with bilingual names and descriptions:
    - `view_public_events`, `manage_public_events`, `publish_public_events`
    - `view_event_documents`, `manage_event_documents`, `publish_event_documents`, `delete_event_documents`
    - `view_receiver_accounts`, `manage_receiver_accounts`, `verify_receiver_accounts`
    - `view_public_payments`, `verify_public_payments`, `reject_public_payments`
    - `view_complaints`, `manage_complaints`, `respond_complaints`, `assign_complaints`, `resolve_complaints`
- **Sidebar Menu**:
  - Added "Public Portal" collapsible section in `resources/views/partials/sidebar.blade.php` with 5 permission-controlled submenus.
- **Bilingual Localization**:
  - Created `lang/en/portal.php` and `lang/bn/portal.php` with **100% key parity (239 keys each)**, verified by automated script.
  - Updated `lang/en/nav.php` and `lang/bn/nav.php`. Zero hardcoded English strings in Blade views or responses.

---

### Increment 7: UI & UX Standards
- **Strict DataTables `#` Column Prohibition**: All 5 portal DataTables (`portal.events.index`, `portal.documents.index`, `portal.receivers.index`, `portal.payments.index`, `portal.complaints.index`) strictly omit `#` or row index columns, placing primary business data in column 0.
- **Select2 & Toastr**: Standardized select inputs with `.select2-enable` and dynamic AJAX Toastr feedback.
- **Button Loaders**: Modals and action buttons disable on submit to prevent duplicate submissions.

---

### Increment 8: Security & Data Integrity
- **Multi-Tenant / Role Protection**: Controller middleware and Form Requests strictly enforce granular permissions server-side. Devotees and guests are blocked with 401/403.
- **Financial Ledger Invariant**: Complaints and inquiries NEVER alter financial records directly. Collection payment verification strictly executes inside database transactions with accrual double-entry posting.
- **Private Document Streaming**: Event documents are stored in private local storage with randomized filenames, accessible only via authenticated streaming responses with strict MIME type validation.

---

### Increment 9: Testing & QA Verification
- **Testing & QA Verification Summary**:
  - `tests/Feature/PublicPortal/PublicEventTest.php`: 12 passed (70 assertions)
  - `tests/Feature/PublicPortal/EventDocumentTest.php`: 16 passed (79 assertions)
  - `tests/Feature/PublicPortal/ReceiverAccountTest.php`: 16 passed (85 assertions)
  - `tests/Feature/PublicPortal/PublicPaymentTest.php`: 17 passed (87 assertions)
  - `tests/Feature/PublicPortal/ComplaintTest.php`: 14 passed (86 assertions)
  - **Subtotal Public Portal Tests**: **75 passed (407 assertions)** (100% PASS).
- **Full Application Test Suite**:
  - **344 passed tests (2,015 assertions)** across the entire application with ZERO failures and ZERO regressions.
- **Code Style (Laravel Pint)**:
  - 100% PSR-12 code compliance verified with `./vendor/bin/pint --test` (0 issues).

---

### Increment 10: Form Corrections & Public Payment Accounting Rules Verification

#### 1. Public Event Management (`/admin/portal/events`)
- **Localization & Language Sanitization**:
  - Fixed raw translation keys (`events.event_name`, `events.date`, `events.dates`, `events.event_duration`, `events.view_details`).
  - Added bilingual keys with 100% parity to both `lang/en/events.php` and `lang/bn/events.php`.
  - Ensured all table headers, filters, modal forms, and badges render clean human-readable text in both English and Bengali.

#### 2. Event Documents / Financial Report Upload (`/admin/portal/documents`)
- **Financial Statement Report Context**:
  - Configured document upload form specifically for Puja/event financial statements (Total Income / আয়, Total Expenses / ব্যয়, Remaining Balance or Due / স্থিতি বা বকেয়া, and Expense breakdown by category / কোন খাতে কত ব্যয়).
  - Multi-report support: allows multiple financial statements/annexures per event.
  - Clear Event Dropdown: displays Event Name, Category, and Year formatted as `{{ $event->name }} ({{ $event->category?->name ?: __('common.general') }}) – {{ $event->event_year }}`.
  - Document type defaults to financial report with accepted PDF, Excel, and scan formats.
  - Publication status control (`published` vs `unpublished`) to withhold draft statements until committee approval.

#### 3. Receiver Account Management (`/admin/portal/receivers/create`)
- **Conventions & Accounting Boundaries**:
  - Reused existing financial account architecture and followed `/collections/create` conventions.
  - Added `event_id` (nullable foreign key) to `receiver_accounts` table (`2026_11_03_000031_add_event_id_to_receiver_accounts_table.php`). Accounts can be dedicated to a specific Puja/event or marked as general.
  - Clear Account Holder Name, Receiver Account Number, display instructions (English & Bengali), verification status, and active toggle.
  - Prominent UI notice: Receiver accounts are payment collection endpoints only; they do NOT generate ledger or collection records themselves.
  - Soft deactivation preserves all historical payment submissions.

#### 4. Public Payment Submission & Accounting Verification Rules (`/admin/portal/payments`)
- **Strict Registration Rule**: Public payment verification is strictly restricted to registered devotees submitting against active pledges for running events.
- **Pending Payment Ledger Invariant**: Pending submissions NEVER modify verified collection totals or reduce pledge balances.
- **Accounting Verification Calculations (Scenarios A, B, C)**:
  - **Formula Enforced**:
    $$\text{Remaining Due} = \max(\text{Pledged Amount} - \text{Total Verified Paid}, 0)$$
    $$\text{Extra Contribution} = \max(\text{Total Verified Paid} - \text{Pledged Amount}, 0)$$
  - **Scenario A (Partial Payment)**:
    - Pledge: ৳5,000 | Verified Paid: ৳1,000 | Remaining Due: ৳4,000
    - New Payment: ৳2,000
    - Result: Total Verified Paid = ৳3,000, Remaining Due = ৳2,000, Extra Contribution = ৳0.
  - **Scenario B (Full Pledge Settlement)**:
    - Remaining Due: ৳4,000
    - New Payment: ৳4,000
    - Result: Total Verified Paid = ৳5,000, Remaining Due = ৳0, Extra Contribution = ৳0. Pledge marked `paid`.
  - **Scenario C (Payment Exceeding Remaining Pledge)**:
    - Remaining Due: ৳4,000
    - New Payment: ৳5,000
    - Result: Total Verified Paid = ৳6,000 (Pledge ৳5,000 + Extra ৳1,000), Remaining Due = ৳0, Extra Contribution = ৳1,000.
    - Allocation split: ৳4,000 assigned to `target_type = pledge` (settling the pledge to ৳0), ৳1,000 assigned to `target_type = voluntary_event` retaining the `event_id`.
    - Double-Entry Posting: Exactly 1 balanced journal entry generated:
      - Debit: Liquid Asset / MFS / Bank (৳5,000)
      - Credit: Event Pledge Income (৳4,000)
      - Credit: Event Voluntary Contribution Income (৳1,000)
  - **Immutability of Original Pledge**: The original `pledged_amount` (৳5,000) is NEVER modified.
  - **Idempotency & Fraud Prevention**: Method-aware uniqueness check prevents duplicate transaction IDs across bKash, Nagad, Rocket, and Bank Transfer. Already verified or rejected payments cannot be re-verified.
  - **Separation of Duties & Audit Trail**: Requires `verify_public_payments` permission. Verified payments record `verified_by`, `verified_at`, and mandatory `verification_note`. All actions are logged via `AuditLogger`.
