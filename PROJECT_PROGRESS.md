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
**Public Portal Status**: **Admin Panel, Increments 1, 2, 3, 4, 4.1, 5, 6, 7 & Increment 8 (Devotee Self-Service Portal, Event Payment System, Themed Event Cards & Payment Method Logo Branding) Completed & Fully Tested** (35 Devotee tests, 128 assertions; 264 Public Portal tests, 1,450 assertions total)  
**Next Phase**: Phase 7 (Inventory, Goods Receiving, Asset Registry & Maintenance) or Next Portal Increment — On Standby Awaiting Explicit User Approval

---

## Procurement Reports Module & Table UI Polish Deliverables & Verification Checklist

| # | Task / Requirement | Status | Verification Detail |
|---|---|---|---|
| 1 | **Procurement Reports Hierarchy & Navigation** | Completed | Parent menu organized cleanly under *Procurement Reports / ক্রয় সংক্রান্ত প্রতিবেদন* with submenus for *Purchase Requisition Report*, *Purchase Order Report*, and *Outstanding Vendor Bills Report*. Sidebar active states and collapse retention preserved across direct URL access and page reloads. |
| 2 | **Table Header (thead) Optimization & Layout Polish** | Completed | All report and listing table headers shortened to concise, single-line labels (`তারিখ`, `হিসাব`, `বিবরণ`, `উৎস`, `ডেবিট (৳)`, `ক্রেডিট (৳)`, `অবস্থা`, `অ্যাকশন`, `দাতা`, `ধরন`, `অঙ্গীকার (৳)`, `আদায় (৳)`, `অতিরিক্ত (৳)`, `বকেয়া (৳)`, `শিরোনাম`, `আবেদনকারী`, `মেয়াদ`, `ব্যয় (৳)`, `অর্ডার নং`, `বিল নং`, `বিল (৳)`, `পরিশোধ (৳)`). In Event Details (`/admin/events/{id}`), Journal Entries (`/finance/journals`), and Opening Balances (`/finance/opening-balances`), streamlined table headers to compact, single-line terms, enhanced DataTables with length changers, and standardized `.table-action-btn` action icons, eliminating header wrapping. |
| 3 | **Purchase Requisition Report** | Completed | Server-side DataTables with 9 clean columns (strictly no serial `#`), real-time status summary cards, comprehensive multi-attribute filters (Date Range, Status, Requester, Approver, Amount, Search), Bootstrap Offcanvas details drawer, and CSV/Print exports. |
| 4 | **Purchase Order Report** | Completed | Server-side DataTables with 8 clean columns, real-time metrics (Total Orders, Active Committed, Completed, Draft, Cancelled), multi-attribute filters, Offcanvas details with line items and vendor info, and CSV/Print exports. |
| 5 | **Outstanding Vendor Bills Report** | Completed | Authoritative financial calculation: `Outstanding Amount = Approved Bill Amount - Net Posted Payment Allocations`. Accurately accounts for payment reversals without distorting summaries. Filterable by vendor, date ranges, payment status, overdue status, and reference. Summary metrics dynamically calculate approved totals, paid amounts, and overdue liabilities. |
| 6 | **Automated Test Suite & Code Hygiene** | Completed | Comprehensive test coverage in `tests/Feature/ProcurementReportTest.php` and `tests/Feature/SidebarMenuHierarchyTest.php`. Full test suite runs 587 tests (3,357 assertions) with 100% pass rate. 100% key parity across `lang/en/` and `lang/bn/`. |
| 7 | **Authentication & Demo Credentials Polish** | Completed | Synchronized demo devotee credentials (`devotee@temple.local` / `Devotee@123456`) in database and `DevoteeCredential` store, ensuring 100% login success from quick test switch buttons. Fixed login submit button styling (`temple-btn-maroon`) with robust CSS variable mappings, gradient styling, and elevation shadow, resolving unstyled white button display. |
| 8 | **Devotee Payments History & Filter Localization Polish** | Completed | Fixed all unresolved language keys on `/devotee/payments` (`portal.public.*` and `common.date_*`). Removed standalone `Payment Code` column, moving the Transaction ID (`TrxID`) cleanly below the Payment Method badge as instructed, and embedding the clickable payment code link under the Event name. Enhanced date picker inputs with instant reactive table filtering and reset handling. Full test suite passes 100%. |
| 9 | **Public My Contributions History DataTable & Layout Polish** | Completed | In `/my-contributions` (`public.payments.history`), converted static table to client-side DataTables JS (`TempleUI.initDataTable`) with dynamic pagination (10, 25, 50, 100), live search, and sorting. Streamlined table headers to 6 concise columns (`পূজা / অনুষ্ঠান`, `মাধ্যম`, `তারিখ`, `পরিমাণ (৳)`, `স্থিতি`, `কার্যক্রম`). Embedded payment code badge under Event name, positioned Transaction Reference `# TXN...` cleanly beneath Payment Method badge, and relocated quick modals outside table DOM for zero layout interference. All 264 Public Portal feature tests passed (1,451 assertions). |
| 10 | **Devotee Complaints (`/my-complaints`) DataTable, Pagination & Header Badge Removal** | Completed | In `/my-complaints` (`public.complaints.index`), removed the devotee person code badge (`DEV-XXXXX`) from the header banner as explicitly instructed in user screenshot. Fixed broken translation key `portal.history_payment_date` to standard `common.date`. Converted table to DataTables JS (`TempleUI.initDataTable('#myComplaintsTable')`) with client-side pagination (10, 25, 50, 100), live search, chronological date ordering, and streamlined 7-column layout. All 30 related feature tests passed cleanly (122 assertions). |
| 11 | **Submit Complaint Page (`/my-complaints/create`) Styling & Badge Removal** | Completed | In `/my-complaints/create` (`public.complaints.create`), removed the person code badge (`DEV-XXXXX`) from the banner as instructed. Upgraded form layout from narrow centered box to full enterprise 2-column grid (`col-lg-8` main submission form card, `col-lg-4` devotee profile and guidelines sidebar card). Enhanced form field aesthetics with clear contextual icons, responsive 2-column category/event grouping, character counter, and loading button. All 16 donor complaint feature tests passed cleanly (58 assertions). |
| 12 | **Devotee Profile & Password OTP Modal Workflow & Button Style Polish** | Completed | Upgraded `/devotee/profile` OTP verification from static inline forms to a dynamic, interactive Bootstrap 5 verification modal. When a devotee submits profile or password changes, the modal opens instantly showing sending state and email destination instructions. Upon dispatch, reveals a 6-digit OTP input, active 60-second countdown timer, reactive Resend OTP button, and 'Verify & Confirm' action. Completely eliminated cluttered inline OTP boxes from the page. Fixed button styling on 'Save Profile Changes' and 'Update Password' with robust maroon gradient (`#75141c` to `#560e15`), elevation shadows, and icons, resolving unstyled white button appearance. All 35 Devotee tests passed cleanly (128 assertions). |
| 13 | **General Profile (`/profile`) Button Styling & Cache-Busting Polish** | Completed | In `/profile` (`profile.edit`), standardized both 'Save Changes' (Profile Information) and 'Update Password' buttons to temple maroon gradient (`#75141c` to `#560e15`), white text, elevation shadows, and rounded-pill styling, resolving unstyled white button display. Added cache-busting timestamp version query strings to `temple-theme.css` in `layouts/app.blade.php` and `layouts/public.blade.php` to permanently eliminate stale browser stylesheet caching. |
| 14 | **Committee Member Procurement Permissions & 403 Localization Polish** | Completed | Fixed 403 Forbidden on `/procurement/budgets/create` when accessed by Committee accounts (`member@temple.local`). Granted `manage_budgets` and `manage_purchase_requests` permissions to the `committee_member` role in `RoleAndPermissionSeeder.php` and synced database, empowering committee members to propose budgets and purchase requisitions. Enforced `@if(Auth::user()->hasRole('super_admin') || Auth::user()->hasPermission('manage_budgets'))` in `sidebar.blade.php` so unprivileged viewers are not exposed to unauthorized create links. Added missing `'unauthorized'` and `'unauthorized_access'` translation keys to `lang/en/common.php` and `lang/bn/common.php`, permanently resolving raw `COMMON.UNAUTHORIZED` error pages. All 589 tests passed (3,369 assertions). |

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

---

### Public Portal — Increment 1: Public Layout, Home Page & Event Listing

#### 1. Scope & Architectural Boundaries
- **No SPA Frameworks**: Built strictly with standard Laravel 11 Blade templates, Bootstrap 5.3, custom CSS, and vanilla JS.
- **Strict Scope Enforcement**: Implemented public layout, home page, event listing, and public event details. Payment submission forms, donor search/directory, payment details, public complaint submission, and prior event financial report downloads are intentionally excluded and preserved for subsequent increments.
- **Zero Fictitious Info**: No invented email addresses, fake telephone numbers, or unverified contact lines in footer or layout.

#### 2. Responsive Public Layout & Navigation (`resources/views/layouts/public.blade.php`)
- **Visual Identity**: Dignified devotional aesthetic featuring Om (ॐ) branding, saffron-gold accents, accessible typography, and smooth transitions.
- **Responsive Header & Navbar**:
  - Brand identity with sacred emblem.
  - Navigation links: Home (`/`), Events (`/events`), and placeholders with "coming soon" tooltips for future increments (Payment, Financial Reports, Complaints).
  - Locale Switcher with current language badge (EN / BN) preserving user preference.
  - Responsive hamburger toggler for mobile and tablet devices.
- **Footer**:
  - Temple name and devotional mission.
  - Quick navigation links.
  - Transparent system notices with localization support.
  - Copyright and credits without fabricated contact details.

#### 3. Public Home Page (`/` via `HomeController@index`)
- **Hero Showcase**: Dignified header with temple title, welcoming subtitle, and primary call-to-action buttons ("Explore Events" & "Learn More").
- **Core Pillars**: Transparency, Sacred Devotion, and Community Unity cards.
- **Active & Running Events Section**: Cards displaying events with status `active` or `closing` that are marked `is_published = true`.
- **Upcoming Events Preview**: Preview section highlighting scheduled Pujas (`status = 'upcoming'`).
- **Completed Events Highlights**: Recent successfully concluded Pujas (`status = 'closed'`, limited to top 4) displaying completion badges.
- **Community CTA Section**: Clean devotional message encouraging community participation.

#### 4. Public Event Listing (`/events` via `EventController@index`)
- **Server-Side Filters**:
  - Category filter (dynamically loaded from `event_categories`).
  - Liturgical / Calendar Year filter.
  - Status filter (`all`, `active`, `upcoming`, `completed`).
- **Card-Based Event Grid**:
  - Event banner image (or elegant fallback gradient with category icon).
  - Event name, category badge, and status pill.
  - Event dates and venue.
  - Collection window timeline.
  - Responsive "View Details" button linking to `/events/{slug}`.
- **Pagination & Empty States**: Clean Bootstrap 5 pagination with preserved query strings, and respectful empty-state illustration when no events match filter criteria.

#### 5. Public Event Details (`/events/{slug}` via `EventController@show`)
- **SEO & Route Safety**:
  - Accessible strictly via slug (`/events/{slug}`). Numeric database IDs in URLs return 404.
  - Unpublished, draft, or non-existent event slugs return HTTP 404.
- **Content Presentation**:
  - Rich header banner with event category, name, and publication status.
  - Breadcrumb navigation (`Home > Events > [Event Name]`).
  - Event schedule: start date, end date, and duration.
  - Collection period: start date to end date.
  - Venue / Location information.
  - Detailed description and devotee instructions.
  - Back to listing button.

#### 6. Route Structure & Non-Interference
- **Public Routes Added**:
  - `GET /` -> `App\Http\Controllers\Public\HomeController@index` (`route('home')`)
  - `GET /events` -> `App\Http\Controllers\Public\EventController@index` (`route('public.events.index')`)
  - `GET /events/{slug}` -> `App\Http\Controllers\Public\EventController@show` (`route('public.events.show')`)
- **Admin Event Routes Preserved**:
  - Admin event management routes moved under `Route::prefix('admin/events')` to prevent collision with `/events`.
  - All admin named routes (`events.dashboard`, `events.index`, `events.create`, `events.show`, `events.edit`, etc.) and middleware remain 100% intact. Existing admin tests (`EventManagementTest`, `EventDonationAndSettlementTest`) pass with zero regressions.

#### 7. Localization Parity
- All public UI strings mapped under `'public'` array in `lang/en/portal.php` and `lang/bn/portal.php` with 100% key parity.
- Tested and verified via `Tests\Feature\LocalizationTest::all language files maintain key parity`.

#### 8. Automated Feature Tests & Verification
- **Test File**: `tests/Feature/PublicPortal/PublicPortalHomeAndEventsTest.php` (13 tests, 46 assertions):
  1. `public home page renders successfully`
  2. `home page lists published active upcoming and completed events`
  3. `public event listing page renders successfully`
  4. `event listing filters by category`
  5. `event listing filters by year`
  6. `event listing filters by status`
  7. `unpublished events are not visible`
  8. `event details work through slug`
  9. `invalid or nonexistent slug returns 404`
  10. `unpublished event slug returns 404`
  11. `draft event slug returns 404`
  12. `public routes never expose database ids`
  13. `bengali and english localization work`
  14. `existing admin event functionality remains unaffected`
- **Full Application Test Suite**: **357 passed (2,061 assertions)** with 0 failures.
- **Code Style (Laravel Pint)**: 100% PSR-12 code compliance verified via `./vendor/bin/pint --test` (0 issues).

---

### Public Portal — Increment 2: Running Event Details & Collection Summary

#### 1. Scope & Architectural Boundaries
- **Increment 2 Scope**: Enhanced running event details page with aggregate financial summary cards, visual collection progress bar, and transparency notice.
- **Strict Boundary Control**:
  - Excluded Donor-wise collection table (Increment 3).
  - Excluded Public payment submission (Increment 4).
  - Excluded Payment details lookup and public complaints (Increment 5).
  - Excluded Previous event financial report download archives (Increment 6).
- **Data & Model Reuse**:
  - Reused `Event`, `Pledge`, `CollectionPayment`, and `PaymentAllocation` models.
  - Reused centralized `EventCollectionReportService` to perform calculation without duplicate accounting math.
  - No new database tables created.

#### 2. Exact Financial Calculation Rules & Formulas
- **Total Pledged**:
  $$\text{Total Pledged} = \sum \text{pledged\_amount} \quad \text{where status} \neq \text{'cancelled' and not soft-deleted}$$
  - Computed via direct database `SUM()` avoiding loading records into PHP memory.
- **Verified Collections Applied to Pledges**:
  $$\text{Verified Paid Against Pledges} = \sum \text{amount} \quad \text{where target\_type} = \text{'pledge', is\_active} = 1, \text{and payment status} = \text{'verified'}$$
- **Total Verified Collection (Uncapped)**:
  $$\text{Total Verified Collection} = \sum \text{amount} \quad \text{where target\_type} \in [\text{'pledge'}, \text{'voluntary\_event'}], \text{is\_active} = 1, \text{and payment status} = \text{'verified'}$$
  - Accurately captures both pledge payments, excess contributions directed to the event, and guest voluntary event donations.
  - Excludes unverified/pending payments, rejected payments, and reversed/cancelled payments.
  - Uncapped: if verified collections exceed pledged targets, the full collection amount is displayed accurately.
- **Total Due**:
  $$\text{Total Due} = \max(\text{Total Pledged} - \text{Verified Paid Against Pledges}, 0)$$
  - Invariant: Due is never negative.
  - Excess contributions allocated to the event fund do not distort or reduce other donors' outstanding pledge dues.
- **Collection Progress**:
  $$\text{Progress} = \frac{\text{Verified Paid Against Pledges}}{\text{Total Pledged}} \times 100$$
  - When $\text{Total Pledged} = 0$, Progress returns `null` and displays `N/A` cleanly without division-by-zero errors.
  - Visual progress bar width is capped at $100\%$ ($\min(100.0, \max(0.0, \text{Progress}))\%$).
  - Over-collection badge with exact extra amount ($+\text{৳}X$) is highlighted when total collections exceed pledges.

#### 3. Responsive UI & Visual Design (`resources/views/public/events/show.blade.php`)
- **Four Distinct Summary Cards**:
  1. **Total Pledged (মোট প্রতিশ্রুত চাঁদা)** with bookmark icon and target subtitle.
  2. **Total Verified Collection (মোট যাচাইকৃত আদায়)** with shield verification badge.
  3. **Total Due (মোট বকেয়া)** with clock icon and pending status color.
  4. **Collection Progress (চাঁদা সংগ্রহের অগ্রগতি)** with percentage rate badge.
- **Large Progress Section**:
  - Striped animated progress bar with ARIA accessibility tags.
  - Textual fraction indicator showing verified against target.
  - Zero-pledge informational notice when event has no formal pledge targets.
- **Transparency Notice**:
  - Highlights that only verified payments appear in published figures, and pending payments are verified prior to inclusion.
- **Zero Exposure of Private Data**:
  - Public page displays aggregate financial metrics only.
  - No donor names, phone numbers, addresses, private notes, receipt numbers, or transaction references are rendered.

#### 4. Localization Parity
- All strings added to `lang/en/portal.php` and `lang/bn/portal.php` with 100% key parity.
- Verified via automated `LocalizationTest`.

#### 5. Automated Feature Tests & Verification
- **Test File**: `tests/Feature/PublicPortal/PublicPortalEventSummaryTest.php` (15 tests, 71 assertions):
  1. `running published event displays collection summary cards and progress`
  2. `correct total pledge calculation and cancellation exclusion`
  3. `correct verified collection calculation combining pledges and voluntary`
  4. `pending payments are strictly excluded`
  5. `rejected payments are strictly excluded`
  6. `reversed and cancelled payments are excluded from net collection`
  7. `correct due calculation and progress percentage`
  8. `progress visually capped at 100 percent while actual collection remains uncapped`
  9. `zero pledge event displays na without division by zero`
  10. `excess contribution does not create negative due`
  11. `excess contribution is not counted twice`
  12. `unpublished events return 404 and do not expose financials`
  13. `completed published events remain accessible with final summary`
  14. `no private donor or payment details are exposed`
  15. `bengali and english localization switch on summary cards`
- **Public Portal Feature Test Suite**: **103 passed (524 assertions)**.
- **Full Application Test Suite**: **372 passed (2,132 assertions)** with ZERO failures and ZERO regressions.
- **Code Style (Laravel Pint)**: 100% PSR-12 code compliance verified via `php vendor/bin/pint --test` (0 issues).

---

### Public Portal — Increment 3: Donor-wise Running Collection Report

#### 1. Scope & Architectural Boundaries
- **Increment 3 Scope**: Donor-wise contribution and collection report on published event details page (`/events/{slug}`).
- **Strict Boundary Control**:
  - Excluded Public payment submission (Increment 4).
  - Excluded Public payment details lookup / external verification portal (Increment 5).
  - Excluded Previous event financial report download archive (Increment 6).
- **Double-Entry Financial Invariant**:
  - Complaint submissions strictly NEVER alter financial ledgers, journals, payment allocations, or pledges.
  - Corrections or adjustments must proceed through authorized reversal procedures.
- **Privacy & Security Boundaries**:
  - Devotee visibility preferences: Public Name, Anonymous ("Anonymous Donor" / "বেনামী ভক্ত"), Private ("Private Devotee" / "ব্যক্তিগত অনুদান").
  - Strictly NEVER exposes devotee phone numbers, emails, addresses, database IDs, receipt numbers, or committee roles/titles in public tables.
  - Anonymous and private contributions remain 100% included in published aggregate metrics and reconciliations.
  - Offcanvas View Details enforces strict security: unauthenticated guests receive a devotional privacy security notice; cross-donor unauthorized access returns HTTP 403 Forbidden.

#### 2. Mandatory UI Table Structure (`resources/views/public/events/show.blade.php`)
- **Exact Column Structure**:
  `Name | Amount / Pledged | Paid | Status | Action`
- **Zero Serial Number (#) Rule**: Strictly omits `#` or serial number column; compact row height.
- **Horizontal Status Cell**:
  - Compact progress bar and percentage aligned horizontally side by side in the same cell (`d-flex align-items-center gap-2`).
  - Dynamic progress bar coloring: Green (`bg-success`) for progress $\ge 50\%$, Red (`bg-danger`) for progress $< 50\%$.
  - Progress bar width visually capped at 100%, paid amount uncapped.
  - Zero-pledge voluntary event donations display `N/A`.
- **Action Buttons**:
  - `View`: Compact eye icon (`bi-eye`) button triggering Bootstrap Offcanvas (`#donorDetailsOffcanvas`) via AJAX.
  - `Complain`: Compact complaint icon (`bi-flag`) button triggering Bootstrap Modal (`#donorComplaintModal`).
- **Interactive Search & Status Filtering**: Real-time client-side search by devotee name or pledge code, and dropdown status filter (`all`, `paid`, `partial`, `due`, `voluntary`).

#### 3. Database & Models
- **Migration**: `2026_11_14_000033_add_visibility_to_pledges_and_people_tables.php` added indexed `visibility` column with defensive `hasColumn` checks.
- **Models Extended**:
  - `App\Models\Pledge`: Added `VISIBILITY_PUBLIC`, `VISIBILITY_ANONYMOUS`, `VISIBILITY_PRIVATE`, `isAnonymous()`, `isPrivate()`, and `getPublicDisplayNameAttribute()`.
  - `App\Models\Person`: Added visibility constants, `$fillable` entry, and `getPublicDisplayNameAttribute()`.
- **Service Layer** (`App\Services\EventCollectionReportService`):
  - Added `getDonorCollectionReport(Event $event, array $filters = []): array` supporting search, status filtering, and 100% mathematical reconciliation with `getPublicSummary()`.

#### 4. Controller & Routes
- **Routes Added** (`routes/web.php`):
  - `GET /events/{slug}/donor-details/{code}` -> `public.events.donor_details`
  - `POST /events/{slug}/complaint` -> `public.events.complaint.store`
- **Controller Methods** (`App\Http\Controllers\Public\EventController`):
  - `show()`: Injects `$donorReport` and `$filters` to event details view.
  - `donorDetails()`: Returns verified payment breakdown and receipts for authorized donor/admin; returns security notice for guest; aborts 403 for unauthorized cross-donor access.
  - `storeComplaint()`: Validates input, creates `Complaint` record (`category = 'payment_issue'`, `status = 'submitted'`, `priority = 'medium'`, sequential tracking number `CMP-YYYY-0001`), supporting file attachments without touching financial ledgers.

#### 5. Bilingual Localization (EN & BN)
- Added 35 keys across `lang/en/portal.php` and `lang/bn/portal.php` with 100% key parity.
- Respectful Bengali localization: "চলমান চাঁদা ও অনুদান বিবরণী", "বেনামী ভক্ত", "ব্যক্তিগত অনুদান", "স্বেচ্ছাসেবী ভক্ত".

#### 6. Automated Feature Tests & Verification
- **Test File**: `tests/Feature/PublicPortal/PublicPortalDonorReportTest.php` (23 tests, 87 assertions):
  1. `donor_wise_running_collection_table_is_rendered_with_exact_column_headers`
  2. `strictly_no_serial_number_column_present_in_table`
  3. `status_cell_displays_compact_horizontal_side_by_side_progress_and_percentage`
  4. `status_progress_color_is_green_for_greater_equal_50_percent`
  5. `status_progress_color_is_red_for_less_than_50_percent`
  6. `zero_pledge_voluntary_donation_displays_na_status`
  7. `progress_bar_is_capped_visually_at_100_percent_while_paid_amount_is_uncapped`
  8. `public_donor_visibility_displays_actual_devotee_name`
  9. `anonymous_donor_visibility_displays_anonymous_donor_label`
  10. `private_donor_visibility_displays_private_devotee_label`
  11. `privacy_invariants_never_leak_phone_email_address_or_committee_titles`
  12. `anonymous_and_private_contributions_remain_fully_included_in_financial_totals`
  13. `action_column_contains_compact_view_and_complain_buttons`
  14. `unauthenticated_guest_accessing_donor_details_receives_security_notice`
  15. `cross_donor_unauthorized_access_returns_403`
  16. `matching_authorized_devotee_can_view_own_payment_history`
  17. `admin_user_can_view_donor_payment_details`
  18. `modal_complaint_submission_creates_complaint_record_without_touching_ledger`
  19. `complaint_submission_validates_required_fields_and_rejects_invalid_type`
  20. `cancelled_or_soft_deleted_pledges_are_strictly_excluded_from_report`
  21. `donor_report_reconciles_100_percent_with_increment_2_aggregate_summary`
  22. `service_donor_report_filtering_by_status_and_search_query`
  23. `bilingual_parity_for_donor_report_and_complaint_labels`
- **Public Portal Feature Test Suite**: **126 passed (611 assertions)**.
- **Full Application Test Suite**: **395 passed (2,219 assertions)** with ZERO failures and ZERO regressions.
- **Code Style (Laravel Pint)**: 100% PSR-12 code compliance verified via `php vendor/bin/pint --test` (0 issues).

---

### Public Portal — Increment 4: Public Payment Submission & Devotee Payment History

- **Objective**: Implement the public payment submission feature and authenticated donor payment history tracking for published events without altering financial ledger invariants prior to authorized admin verification.
- **Architecture & Implementation Details**:
  1. **Person vs. User Decoupled Devotee Resolution**:
     - Added `person(): ?Person` method and `getPersonAttribute()` accessor to `User` model, dynamically resolving the linked registered devotee profile from the temple directory by matching phone or email where `status = 'active'`.
     - Guests accessing submission form are redirected to `route('login')`.
     - Authenticated users without an active linked devotee profile in the directory are denied submission access with a localized warning.
     - Never trusts client-supplied donor IDs; all records are resolved server-side from `auth()->user()`.
  2. **Dedicated Payment Submission Form (`GET /events/{slug}/pay`)**:
     - Pre-fills read-only registered devotee profile details (Name, Devotee Code, Phone, Email).
     - Displays active pledges for the event with pledged amount, verified paid, and remaining due, allowing selection of pledge or voluntary donation.
     - Method selector for bKash, Nagad, Rocket, and Bank Transfer with offline Bootstrap icons.
     - Dynamic receiver account selector card showing verified account name, number with one-click copy, account type, bank/branch routing numbers, QR code image, and payment instructions without exposing internal ledger IDs.
  3. **Strict Validation & Duplicate Prevention (`StorePublicPaymentRequest`)**:
     - FormRequest enforces published running event, active & verified receiver account, positive amount, non-future payment date, and BD sender mobile for MFS.
     - Server-side method-aware duplicate transaction reference validation (`in_array(status, ['verified', 'pending_verification'])`), preventing duplicate submissions for the same payment method.
  4. **Strict Financial Ledger Invariants**:
     - Submitting public payments creates `CollectionPayment` in `STATUS_PENDING` (`pending_verification`) and associated `PaymentAllocation`.
     - Submissions strictly do NOT create `JournalEntry` records, do NOT issue `Receipt` numbers, do NOT alter account balances, do NOT change pledge verified paid amounts, and do NOT alter public aggregate verified collection metrics.
     - Only authorized admin verification (`POST /admin/portal/payments/{id}/verify`) posts balanced double-entry vouchers to general ledger and generates official receipts.
  5. **Authenticated Devotee Payment History (`/my-contributions`)**:
     - Comprehensive tracking page showing the donor's submitted payments, reference code (`PAY-YYYY-XXXX`), event, method, transaction ID, date, amount, and status badges.
     - IDOR-protected receipt download route (`/my-contributions/receipt/{receiptNumber}`) allowing authenticated owners to view and print official receipts once verified.
  6. **UI Integration & Localization**:
     - "Pay Contribution" / "অর্থ প্রদান করুন" button prominently displayed on published running event showcase and summary headers.
     - "My Contributions" / "আমার জমাকৃত অর্থ" nav link in public header for authenticated users.
     - 100% bilingual parity across `lang/en/portal.php` and `lang/bn/portal.php`.
  7. **Feature Test Suite (`tests/Feature/PublicPortal/PublicPaymentSubmissionTest.php`)**:
     - 12 automated tests (63 assertions) covering access control, unlinked user denial, form viewing, closed event blocking, MFS submission, strict ledger invariants, method-aware duplicate prevention, file upload, donor history isolation, IDOR receipt protection, and full admin verification workflow.
- **Public Portal Feature Test Suite**: **138 passed (674 assertions)**.
- **Full Application Test Suite**: **407 passed (2,282 assertions)** with ZERO failures, ZERO errors, and ZERO regressions.
- **Code Style (Laravel Pint)**: 100% PSR-12 code compliance verified via `php vendor/bin/pint --test` (0 issues).

---

### Public Portal — Increment 4.1: Security & Workflow Hardening

- **Objective**: Address security, authorization, privacy, and workflow issues identified in the Increment 4 audit, hardening payment proof storage, person resolution, voluntary event donations, receiver account scopes, collection date boundaries, and transaction reference concurrency.
- **Key Enhancements Implemented**:
  1. **Private Payment Proof Storage & IDOR Protection**:
     - Uploads routed strictly to private disk (`'local'` disk: `storage/app/payment_proofs/`), preventing direct public access via public storage symlink.
     - Added secure authenticated download/stream endpoints:
       - Admin: `GET /admin/portal/payments/{payment}/proof` (`portal.payments.proof`) protected by `permission:view_public_payments`.
       - Public: `GET /my-contributions/{paymentCode}/proof` (`public.payments.proof`) protected by owner IDOR verification and staff authorization.
     - Enforced directory traversal protections (`str_contains($path, '..')`, path sanitization) and graceful backward-compatible fallback for legacy public files.
     - Replaced direct `Storage::disk('public')->url(...)` accessors in `CollectionPayment` with authorization-aware routing.
     - Created Artisan migration command `php artisan portal:migrate-proofs` (`app/Console/Commands/MigratePaymentProofsCommand.php`) with SHA-256 checksum verification and dry-run simulation.
  2. **Person vs. User Unambiguous Resolution**:
     - Hardened `User::person()` with normalized BD phone variants (`+880`, `88`, `01...`).
     - Added collision detection: if multiple active Person records share a phone or email, or phone matches Person A and email matches Person B, the method safely logs a warning and returns `null` instead of arbitrarily picking a record.
  3. **Voluntary Public Donation Admin Verification**:
     - Enhanced `PublicPaymentController@verify` to support voluntary event donations without requiring or creating a pledge.
     - Posts balanced double-entry vouchers to `CODE_INCOME_EVENT` (4020), issues official receipts, and preserves existing pledge balances without reducing dues.
     - UI updated to display "Voluntary Event Donation" badges and verification notices in admin payment show and verify modal.
  4. **Receiver Account Event Scope Enforcement**:
     - Validated server-side in `StorePublicPaymentRequest` and controller that receiver accounts assigned to a specific event (`event_id != null`) cannot be used for a different event, while general accounts (`event_id == null`) remain available across all events.
  5. **Event Collection Date Boundaries**:
     - Enforced application timezone (`config('app.timezone')`) collection date window (`collection_start_date` and `collection_end_date`) in both `create` and `store` methods.
  6. **Concurrency-Safe Duplicate Transaction Prevention**:
     - Added method-aware transaction reference verification with row locking (`lockForUpdate()`) inside the database transaction to prevent concurrent duplicate submissions.
  7. **Feature & Regression Test Suite**:
     - Expanded `tests/Feature/PublicPortal/PublicPaymentSubmissionTest.php` from 12 to 21 automated tests (113 assertions).
     - Full application suite: **416 passed (2,332 assertions)** with ZERO failures and ZERO regressions.
     - Code style: 100% PSR-12 verified via `php vendor/bin/pint --test`.



---

### Public Portal — Increment 5, Step 1: Security & Validation Hardening

- **Objective**: Implement comprehensive Form Request validation, strict permission enforcement, separation of duties, cross-person/cross-event pledge mismatch prevention, and transactional row locking concurrency protection for public payment verification, rejection, and devotee linking without modifying UI or introducing Step 2 features.
- **Key Deliverables Implemented**:
  1. **Dedicated Form Request Architecture**:
     - `app/Http/Requests/PublicPortal/VerifyPublicPaymentRequest.php`:
       - Enforces `verify_public_payments` permission (or `super_admin` role).
       - Validates optional `verification_note` (max 1000).
       - Validates optional `person_id` (must exist in `people` with `status = 'active'`).
       - Validates optional `pledge_id` (must exist in `pledges`, not cancelled, matching devotee person ID, and matching payment event ID).
       - Rejects unlinked public payments when neither pre-linked nor explicitly provided.
       - Method-aware duplicate transaction reference check for bKash, Nagad, Rocket, and Bank Transfer.
       - Enforces separation of duties: administrative submitters cannot verify their own submission unless super admin.
       - Standardized AJAX/JSON failed validation format with `['success' => false, 'message' => ..., 'errors' => ...]`.
     - `app/Http/Requests/PublicPortal/RejectPublicPaymentRequest.php`:
       - Enforces `reject_public_payments` or `verify_public_payments` permission (or `super_admin`).
       - Validates mandatory `rejection_reason` (min 3, max 1000 characters).
       - Rejects payments not in `STATUS_PENDING` (cannot reject verified or reversed payments).
     - `app/Http/Requests/PublicPortal/ReversePublicPaymentRequest.php`:
       - Enforces `reverse_collections`, `reverse_transactions`, or `reverse_payments` permission (or `super_admin`).
       - Validates mandatory `reversal_reason` (min 3, max 1000 characters).
       - Ensures only verified payments can be reversed (rejects pending or already reversed payments).
       - *Note: Reversal controller, route, and UI remain deferred to Step 2 as specified.*
  2. **Controller Security Hardening (`PublicPaymentController.php`)**:
     - `verify()`: Injected `VerifyPublicPaymentRequest`. Entire operation wrapped in authoritative `DB::transaction(...)` with pessimistic row locking (`lockForUpdate()`) on `CollectionPayment` and associated `Pledge`. Prevents race conditions and duplicate receipts/journal entries. Preserves voluntary event donation verification without a pledge.
     - `reject()`: Injected `RejectPublicPaymentRequest`. Wrapped in `DB::transaction(...)` with `lockForUpdate()`. Validates status `STATUS_PENDING`. Deactivates allocations, records audit trail, and generates no receipts or journal entries.
     - `linkDevotee()`: Wrapped in `DB::transaction(...)` with `lockForUpdate()`. Validates target Person exists and has `status = 'active'`. Rechecks payment status is `STATUS_PENDING` under lock; rejects relinking of verified, rejected, or reversed payments with localized error message.
  3. **Bilingual Localization Parity**:
     - Added new keys in `lang/en/portal.php` and `lang/bn/portal.php`:
       - `pledge_donor_mismatch`: "The selected pledge does not belong to the payment devotee." / "নির্বাচিত অঙ্গীকারটি এই ভক্তের নয়।"
       - `pledge_event_mismatch`: "The selected pledge does not belong to the payment event." / "নির্বাচিত অঙ্গীকারটি এই ইভেন্টের নয়।"
       - `inactive_or_invalid_person`: "The selected devotee profile is inactive or invalid." / "নির্বাচিত ভক্তের প্রোফাইলটি নিষ্ক্রিয় বা অবৈধ।"
       - `cannot_relink_processed_payment`: "Cannot link devotee because this payment has already been processed." / "পেমেন্টটি ইতিমধ্যে প্রক্রিয়া সম্পন্ন হওয়ায় ভক্ত পরিবর্তন করা যাবে না।"
  4. **Automated Test Suite Expansion**:
     - Expanded `tests/Feature/PublicPortal/PublicPaymentTest.php` from 17 to 38 automated tests (202 assertions).
     - Fully verified all 20 required cases:
       1. Authorized admin can verify eligible pledge payment.
       2. Unauthorized admin cannot verify (devotee 403).
       3. Submitter cannot approve own submission unless super admin.
       4. Admin cannot verify same payment twice (idempotent 422).
       5. Admin cannot verify payment using another Person's pledge (cross-person rejection).
       6. Admin cannot verify payment using another Event's pledge (cross-event rejection).
       7. Voluntary event donation verification works without pledge.
       8. Admin can reject pending payment with valid reason.
       9. Rejection without valid reason fails (empty or < 3 chars).
       10. Rejected payments create no journal entries or receipts.
       11. Verified payments cannot be rejected.
       12. Reversed payments cannot be verified or rejected.
       13. Pending payment can be linked to eligible active Person.
       14. Verified payment cannot be relinked.
       15. Rejected payment cannot be relinked.
       16. Reversed payment cannot be relinked.
       17. Unauthorized Person linking is denied.
       18. Invalid or inactive Person linking is denied.
       19. Cross-event Person/Pledge manipulation is rejected.
       20. Existing financial balances and receipt integrity remain intact after failed requests.
       21. `ReversePublicPaymentRequest` authorization policy and validation invariants.
  5. **Verification & Regression Metrics**:
     - `php artisan test --filter=PublicPayment`: **59 passed (315 assertions)** across `PublicPaymentSubmissionTest` (21 tests) and `PublicPaymentTest` (38 tests).
     - Full application test suite (`php vendor/bin/phpunit`): **437 passed (2,447 assertions)** with ZERO failures, ZERO errors, and ZERO regressions.
     - Laravel Pint (`php vendor/bin/pint --test`): **100% PSR-12 code compliance** (0 issues).
  6. **Current Status & Strict Boundaries**:
     - **Increment 5 Step 1 is COMPLETED**.
     - Step 2 (Verification modal redesign, receipt print/download UI enhancements, payment reversal route/controller/UI) has NOT been implemented and remains on standby awaiting user approval.


---

### Public Portal — Increment 5, Step 2: Controller & Route Integration

- **Objective**: Complete backend controller and route integration for admin public payment verification, rejection, and devotee linking with strict transactional concurrency, cross-donor pledge sanitization on linking, support for both event-specific and general voluntary donations, and robust authorization boundaries.
- **Key Deliverables Implemented**:
  1. **Controller Integration (`PublicPortal\PublicPaymentController.php`)**:
     - `verify()`:
       - Uses `VerifyPublicPaymentRequest` for type-hinted validation and authorization.
       - Enclosed within an atomic `DB::transaction(...)` with pessimistic row locking (`lockForUpdate()`) on `CollectionPayment` and associated `Pledge`.
       - Rechecks payment status under lock (`STATUS_PENDING`); duplicate or concurrent verify attempts safely fail with status 422.
       - Handles dynamic allocation mapping: automatically selects `TARGET_VOLUNTARY_EVENT` (4020 Event Income) when `event_id` is present, or `TARGET_VOLUNTARY_GENERAL` (4010 General Income) when `event_id` is null.
       - Delegates authoritative financial posting and receipt creation to `CollectionPaymentService::verifyPayment()`. No financial posting logic is duplicated in the controller.
       - Generates structured audit trail entries for pledge payments, event voluntary donations, and general voluntary donations.
     - `reject()`:
       - Uses `RejectPublicPaymentRequest` enforcing reason validation and pending status.
       - Enclosed within `DB::transaction(...)` with `lockForUpdate()`.
       - Calls `CollectionPaymentService::rejectPayment()`, deactivates allocations, records rejection audit log, and creates no journal entries or receipts.
     - `linkDevotee()`:
       - Enforces active status check on selected `Person` profile.
       - Wrapped in `DB::transaction(...)` with `lockForUpdate()`.
       - Only pending payments can be linked; relinking verified, rejected, or reversed payments returns status 422.
       - **Cross-Donor Pledge Disassociation**: If the payment previously held an attached pledge belonging to another person, changing the devotee automatically disassociates the obsolete pledge (`pledge_id = null`) and converts active allocations to voluntary donations, preventing cross-donor pledge credit contamination.
  2. **Route & Authorization Verification**:
     - Admin payment routes confirmed in `routes/web.php` under `admin/portal/payments` (`portal.payments.`):
       - `portal.payments.index` (`GET /admin/portal/payments`): protected by `permission:view_public_payments`.
       - `portal.payments.search-persons` (`GET /admin/portal/payments/search-persons`): protected by `permission:view_public_payments`.
       - `portal.payments.show` (`GET /admin/portal/payments/{payment}`): protected by `permission:view_public_payments`.
       - `portal.payments.proof` (`GET /admin/portal/payments/{payment}/proof`): protected by `permission:view_public_payments`.
       - `portal.payments.verify` (`POST /admin/portal/payments/{payment}/verify`): protected by `permission:verify_public_payments`.
       - `portal.payments.reject` (`POST /admin/portal/payments/{payment}/reject`): protected by `permission:reject_public_payments|verify_public_payments`.
       - `portal.payments.link-devotee` (`POST /admin/portal/payments/{payment}/link-devotee`): protected by `permission:verify_public_payments`.
     - Preserved all existing route names and URLs.
     - Reversal routes remain strictly omitted in Step 2.
  3. **Automated Feature Tests Added (`tests/Feature/PublicPortal/PublicPaymentTest.php`)**:
     - Added `test_competing_verification_and_rejection_concurrency_protection()`: verifies competing state transitions under row locking.
     - Added `test_linking_devotee_disassociates_pledge_belonging_to_previous_person()`: verifies cross-donor pledge disassociation upon devotee re-linking.
     - Added `test_general_voluntary_donation_verification_without_event()`: verifies voluntary general temple donation verification and double-entry ledger posting to account 4010.
     - Total `PublicPaymentTest` suite expanded to **41 tests (220 assertions)**.
  4. **Verification & Regression Metrics**:
     - `php artisan test --filter=PublicPayment`: **62 passed (339 assertions)** across `PublicPaymentSubmissionTest` (21 tests) and `PublicPaymentTest` (41 tests).
     - Full application test suite (`php vendor/bin/phpunit`): **440 passed (2,457 assertions)** with ZERO failures, ZERO errors, and ZERO regressions.
     - Code style: **100% PSR-12 code compliance** verified via `php vendor/bin/pint --test`.
  5. **Current Status & Strict Boundaries**:
     - **Increment 5 Step 2 is COMPLETED**.
     - UI enhancements, verification modal redesign, receipt print/download buttons, and reversal routes/controllers/UI remain deferred to subsequent approved steps.
     - Increment 5 is NOT marked complete.

---

### Public Portal — Increment 5, Step 3: Admin Payment Verification UI

- **Objective**: Implement the Admin Payment Verification UI enhancements across the public payments DataTable listing, Quick Verify modal, Reject Payment modal, payment details view, and receipt action integrations.
- **Key Deliverables Implemented**:
  1. **Admin Payment DataTable Improvements (`resources/views/portal/payments/index.blade.php` & `app/Http/Controllers/PublicPortal/PublicPaymentController.php`)**:
     - Preserved server-side DataTables with compact layout and strictly without serial `#` column.
     - Eager-loaded `pledge` relationship in DataTable controller queries (`CollectionPayment::with(['payerPerson', 'event', 'receiverAccount', 'receipt', 'pledge'])`).
     - Enhanced `.btn-verify-payment` with complete data attributes: `data-donor`, `data-event`, `data-method`, `data-trx`, `data-proof`, `data-type`, `data-pledge-code`, `data-pledged`, `data-paid`, `data-due`, and `data-url`.
     - Enhanced `.btn-reject-payment` with contextual data attributes: `data-code`, `data-donor`, `data-event`, `data-amount`, and `data-url`.
     - **Strict Receipt Action Guard**: In `formatActionsColumn()`, View Receipt (`route('receipts.show', $receipt)`) and Print Receipt (`route('receipts.print', $receipt)`) actions are rendered ONLY when the payment is verified (`STATUS_VERIFIED`) AND has an issued receipt (`$payment->receipt`). Receipt action buttons are never rendered for pending or rejected submissions.
  2. **Quick Verify Modal Redesign (`resources/views/portal/payments/index.blade.php`)**:
     - Upgraded modal to structured 2-column layout (`modal-lg`).
     - **Eliminated Obsolete Guest Checkbox**: Completely removed `<input type="checkbox" name="create_guest_person">` from both UI view and DOM.
     - Displays comprehensive submission details: Donor Name, Event Name, Payment Code, Amount, Payment Method, and TrxID.
     - Added Proof Preview container (`#modalVerifyProofBox`) displaying image thumbnail with target="_blank" preview link via authorized route `portal.payments.proof`, or fallback badge when no proof is attached.
     - Added Allocation Details container (`#modalVerifyPledgeBox` & `#modalVerifyVoluntaryBox`):
       - If associated with a pledge, displays Pledge Code, Original Pledged, Total Verified Paid, and Remaining Due.
       - If voluntary donation, displays voluntary donation title and explanatory destination information (Event Fund vs. General Temple Fund).
     - Added reusable button loading spinner (`#btnSubmitVerifySpinner`) and Toastr notification feedback upon submission.
     - Server calculations remain 100% authoritative; JavaScript does not perform financial arithmetic.
  3. **Reject Payment Modal Improvements (`resources/views/portal/payments/index.blade.php` & `show.blade.php`)**:
     - Rejection modal displays Payment Code, Donor Name, Event Name, and Amount for context.
     - Requires mandatory rejection reason with clear placeholder.
     - Implemented button loading spinner and disabled state on submit.
     - Refreshes DataTable and displays Toastr notification on success.
  4. **Receipt Print & Download Actions (`resources/views/portal/payments/show.blade.php`)**:
     - Added View Receipt, Print Receipt (`target="_blank"`), and Download Receipt buttons in the payment details header when an issued receipt exists.
     - Enforced server-side permission gates: `view_receipts` for viewing and `print_receipts` for printing/downloading.
  5. **Devotee Linking UI (`resources/views/portal/payments/show.blade.php`)**:
     - Configured Select2 AJAX search targeting `route('portal.payments.search-persons')`.
     - Displays currently linked devotee profile with direct link to devotee details.
     - Provides a clean "Change Linked Devotee" collapsible interface for pending payments.
     - **Strict Status Guard**: Devotee linking form and change actions are visible ONLY when the payment is in pending status (`STATUS_PENDING`). For verified, rejected, or reversed payments, the linking form is completely hidden from the UI.
  6. **Voluntary Donation Support & Localization Parity**:
     - Enhanced `show.blade.php` allocation card to distinguish between Voluntary Event Donations (`voluntary_event`) and Voluntary General Temple Donations (`voluntary_general`).
     - Maintained 100% localization parity across `lang/en/portal.php` and `lang/bn/portal.php` for all new UI keys (`voluntary_general_donation`, `voluntary_general_donation_help`, `download_receipt`, `change_linked_devotee`, `current_linked_devotee`).
  7. **Automated Feature Tests (`tests/Feature/PublicPortal/PublicPaymentTest.php`)**:
     - Added 8 dedicated feature tests (92 assertions):
       1. `test_admin_payment_datatable_actions_include_receipt_actions_for_verified_payments`: verifies receipt actions appear only for verified payments.
       2. `test_admin_payment_datatable_actions_never_display_receipt_actions_for_pending_or_rejected_payments`: verifies receipt actions are never rendered on pending or rejected payments.
       3. `test_admin_payment_datatable_actions_include_rich_verification_data_attributes`: verifies verification and rejection data attributes in DataTable rows.
       4. `test_admin_payment_verification_modal_does_not_contain_obsolete_guest_checkbox`: verifies complete removal of obsolete guest checkbox and presence of structured modal sections.
       5. `test_payment_details_view_shows_receipt_actions_only_when_receipt_exists`: verifies receipt header action visibility.
       6. `test_payment_details_view_displays_voluntary_event_vs_voluntary_general_donation_cards`: verifies voluntary event vs general cards.
       7. `test_payment_details_view_devotee_linking_ui_state`: verifies linking form is visible for pending and hidden for verified/rejected payments.
       8. `test_localization_parity_for_step_3_new_ui_keys`: verifies bilingual key parity.
     - Total `PublicPaymentTest` suite expanded to **49 tests (318 assertions)**.
  8. **Verification & Regression Metrics**:
     - `php artisan test --filter=PublicPayment`: **70 passed (431 assertions)** across `PublicPaymentSubmissionTest` (21 tests) and `PublicPaymentTest` (49 tests).
     - Full application test suite (`php vendor/bin/phpunit`): **448 passed (2,563 assertions)** with ZERO failures, ZERO errors, and ZERO regressions.
     - Code style: **100% PSR-12 code compliance** verified via `php vendor/bin/pint --test`.
  9. **Current Status & Strict Boundaries**:
     - **Increment 5 Step 3 is COMPLETED**.

---

### Public Portal — Increment 5, Step 4: Payment Reversal UI, Routes & Controller Integration

- **Objective**: Integrate the existing payment reversal workflow into the Admin Panel with secure controller actions, routes, server authorization, and user interface.
- **Key Deliverables Implemented**:
  1. **Service Concurrency Hardening (`app/Services/CollectionPaymentService.php`)**:
     - Hardened `reversePayment()` by introducing a pessimistic lock status recheck inside `DB::transaction(...)`:
       ```php
       $lockedPayment = CollectionPayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();
       if ($lockedPayment->status !== CollectionPayment::STATUS_VERIFIED) {
           throw ValidationException::withMessages(['payment' => __('donations.only_verified_payments_can_be_reversed')]);
       }
       ```
     - Enforces full rollback on any failure, prevents concurrent double reversals, updates status to `STATUS_REVERSED`, records `reversed_by`, `reversed_at`, and `reversal_reason`.
     - Deactivates allocations (`is_active = false`), recalculates affected pledge status (restoring unpaid balance), voids official receipts (`voidReceipt()`), generates compensating balanced inverse journal vouchers via `FinancePostingService` / `JournalEntryService`, and records comprehensive audit logs.
  2. **Route Integration (`routes/web.php`)**:
     - Added `Route::post('/{payment}/reverse', [PublicPaymentController::class, 'reverse'])->name('reverse');` under the `portal.payments.` route group.
  3. **Controller & Authorization Integration (`app/Http/Controllers/PublicPortal/PublicPaymentController.php`)**:
     - Protected endpoint with middleware `permission:reverse_payments|reverse_collections`.
     - Handled requests via `ReversePublicPaymentRequest` (authorizes `super_admin`, `reverse_payments`, `reverse_collections`, `reverse_transactions` and validates `reversal_reason` min:3, max:1000).
     - Returns JSON `{ success: true, message: ..., payment: { id, payment_code, status } }` for AJAX / DataTables calls, or redirect with flash message for standard form submissions.
     - Eager-loaded `reverser` and `journalEntry.reversedByJournal.lines.account` in `show()` view query.
     - Eager-loaded `journalEntry` in `index()` query to determine reversal eligibility.
     - Updated `formatActionsColumn()`: Renders `.btn-reverse-payment` strictly when `$payment->status === CollectionPayment::STATUS_VERIFIED`, the payment has an active posted journal, and the user has reversal permission. Injects rich data attributes (`data-id`, `data-code`, `data-amount`, `data-donor`, `data-event`, `data-method`, `data-trx`, `data-receipt`, `data-type`, `data-pledge-code`, `data-pledged`, `data-paid`, `data-due`, `data-url`).
  4. **Admin Payment DataTable & Reversal Modal UI (`resources/views/portal/payments/index.blade.php`)**:
     - Added `#reversePaymentModal` dialog (`modal-lg`) with prominent warning banner (`reversal_warning_title`, `reversal_warning_body`), payment summary grid, pledge restoration vs. voluntary donation explanation, and mandatory `reversal_reason` textarea.
     - Added jQuery event handler for `.btn-reverse-payment` populating modal fields and configuring form action URL.
     - Added AJAX form submit handler with loading spinner (`#btnSubmitReverseSpinner`), Toastr feedback, and automatic DataTable refresh (`table.ajax.reload(null, false)`).
  5. **Payment Details View Reversal Presentation (`resources/views/portal/payments/show.blade.php`)**:
     - Header: If payment is reversed, displays a voided receipt badge (`receipt_status_voided`) rather than active receipt action buttons.
     - Reversal Information Banner: Displays a clear secondary/danger alert with `reversal_reason`, `reversed_by`, `reversed_at`, and direct link to the compensating reversal journal entry.
     - Compensating Reversal Ledger Voucher: Renders a dedicated card displaying the inverse balanced journal voucher with full account lines (Debits and Credits) and voucher badge.
     - Actions Sidebar: Renders Reverse Payment button only when payment is verified and user has reversal permission; hides reverse action when payment is already reversed.
     - Audit Sidebar: Displays `reversed_by`, `reversed_at`, and `reversal_reason` fields when reversed.
     - Modal: Added `#reversePaymentModal` with submit spinner for single payment detail page workflow.
  6. **Localization Parity (`lang/en/portal.php` & `lang/bn/portal.php`)**:
     - Added 100% bilingual parity for new UI keys: `reversal_warning_title`, `reversal_warning_body`, `reversal_pledge_restoration_help`, `reversal_voluntary_help`, and `reversal_receipt_voided_help`.
  7. **Automated Feature Tests (`tests/Feature/PublicPortal/PublicPaymentTest.php`)**:
     - Added 12 dedicated feature tests (143 assertions):
       1. `test_authorized_user_can_reverse_verified_payment_via_route`: Verifies both AJAX/JSON and standard redirect flows for authorized users (`treasurer` and `super_admin`).
       2. `test_unauthorized_user_cannot_reverse_payment`: Verifies 403 Forbidden for devotees and unauthorized admins, and redirect to login for guests.
       3. `test_pending_or_rejected_or_reversed_payment_cannot_be_reversed`: Verifies 422 Unprocessable Entity when attempting to reverse pending, rejected, or already reversed payments.
       4. `test_reversal_requires_valid_reason_between_3_and_1000_chars`: Verifies 422 validation failure on missing, whitespace-only, or too-short reasons (< 3 chars).
       5. `test_reversal_creates_balanced_compensating_journal_entry_and_preserves_original_journal`: Verifies original journal is preserved as `reversed` and compensating journal is created with exact inverted lines and balanced debits/credits.
       6. `test_reversal_restores_pledge_outstanding_balance`: Verifies allocation deactivation and pledge recalculation restoring outstanding balance and `unpaid` status.
       7. `test_voluntary_event_and_general_donation_reversal_leaves_pledges_untouched`: Verifies voluntary donations reversals do not mutate pledge balances.
       8. `test_reversal_preserves_original_receipt_and_marks_it_voided`: Verifies receipt is preserved in DB and marked `voided` with audit trail (`voided_by`, `void_reason`, `voided_at`).
       9. `test_reversed_payment_details_view_renders_reversal_audit_and_compensating_journal`: Verifies UI alerts, voided receipt badge, compensating journal voucher table, and hidden reverse actions on reversed payments.
       10. `test_datatable_actions_column_includes_reverse_button_only_for_verified_posted_payments`: Verifies reverse button is rendered exclusively for verified payments with posted journals and hidden for pending, rejected, reversed, and unauthorized states.
       11. `test_concurrency_lock_for_update_prevents_double_reversal`: Verifies second concurrent reversal attempt throws `ValidationException`.
       12. `test_localization_parity_for_step_4_reversal_ui_keys`: Verifies 100% key and text parity in EN and BN.
     - Total `PublicPaymentTest` suite expanded to **61 tests (461 assertions)**.
  8. **Verification & Regression Metrics**:
     - `php artisan test --filter=PublicPayment`: **82 passed (574 assertions)** across `PublicPaymentSubmissionTest` (21 tests) and `PublicPaymentTest` (61 tests).
     - Full application test suite (`php vendor/bin/phpunit`): **460 passed (2,706 assertions)** with ZERO failures, ZERO errors, and ZERO regressions.
     - Code style: **100% PSR-12 code compliance** verified via `php vendor/bin/pint --test`.
  9. **Current Status & Strict Boundaries**:
     - **Increment 5 Step 4 is COMPLETED**.
     - All four steps of Increment 5 (Step 1: Security & Validation, Step 2: Controller & Routes, Step 3: Admin Payment Verification UI, Step 4: Payment Reversal UI, Routes & Controller Integration) are implemented and fully verified.
     - **STOP Condition Applied**: Awaiting explicit user review and approval before considering Increment 5 complete or proceeding to Increment 6.


---

## Public Portal — Increment 6: Payment Details & Complaint Management (COMPLETED)

- **Objective**: Complete end-to-end implementation of Public Portal Increment 6 encompassing:
  1. **Feature A — Donor Payment Details & History Breakdown**: Complete breakdown of submitted payments with full status handling (`pending_verification`, `verified`, `rejected`, `reversed`), allocation targets, pledge balances, voluntary donation summaries, verified paid amounts, remaining due, and IDOR-protected View/Download receipt actions for active verified receipts.
  2. **Feature B — Authenticated Public Complaint Submission & History**: Structured 4 complaint categories (`payment_not_updated`, `wrong_paid_amount`, `wrong_due_amount`, `other`), unique non-sequential opaque tracking reference generation (`CMP-YYYY-XXXXXXXX`), anti-duplicate submission protection, payment ownership validation, donor complaint listing, detailed timeline with admin replies visible and internal notes strictly hidden.
  3. **Feature C — Admin Complaint Management**: DataTables-powered complaint index strictly without row number / `#` column, status/category/event/assignee filtering, quick actions for Mark Under Review, Respond to Complainant, Resolve (mandatory note), Reject (mandatory reason), Add Internal Note (isolated private visibility), and Close (terminal status immutability).
  4. **Strict Financial Integrity Invariants**: Complaints are strictly non-financial records. Complaint actions never mutate `CollectionPayment` records, allocations, pledge balances, journal entries, or receipt void states.

### Key Deliverables Implemented

1. **Database Schema & Migrations**:
   - `complaint_activities` table (`2026_11_15_000034_create_complaint_activities_table.php`):
     - `complaint_id`: Foreign key to `complaints` (`cascadeOnDelete`).
     - `actor_id`: Foreign key to `users` (`nullOnDelete`).
     - `activity_type`: Enum (`submission`, `reply`, `status_change`, `internal_note`, `assignment`, `resolution`, `rejection`).
     - `previous_status`, `new_status`: Strings.
     - `content`: Text of reply, note, or reason.
     - `visibility`: Enum (`donor`, `internal`) ensuring airtight segregation of internal committee notes from public devotee view.
     - Timestamps and composite index `['complaint_id', 'visibility']`.
   - Executed migration cleanly on MySQL database (`188.65ms DONE`).

2. **Domain Models**:
   - `app/Models/ComplaintActivity.php`:
     - Relationships: `complaint()`, `actor()`.
     - Scopes: `donorVisible()`, `internalOnly()`.
     - Helpers: `isDonorVisible()`, `isInternal()`.
   - `app/Models/Complaint.php`:
     - Added categories: `CATEGORY_PAYMENT_NOT_UPDATED`, `CATEGORY_WRONG_PAID_AMOUNT`, `CATEGORY_WRONG_DUE_AMOUNT`, `CATEGORY_OTHER`.
     - Added status: `STATUS_REPLIED`.
     - Added opaque non-sequential tracking generator: `generateDonorTrackingNumber()` (`CMP-YYYY-XXXXXXXX` with 8 random alphanumeric characters).
     - Preserved backward-compatible sequential generator: `generateTrackingNumber()` (`CMP-YYYY-0001`).
     - Added relationships: `activities()`, `donorVisibleActivities()`, `internalNotes()`.
     - Added status helper methods: `isSubmitted()`, `isUnderReview()`, `isReplied()`, `isResolved()`, `isRejected()`, `isClosed()`.

3. **Form Requests & Security**:
   - `app/Http/Requests/Public/StoreDonorComplaintRequest.php`:
     - Enforces donor authentication and directory linking.
     - Validates categories (`payment_not_updated`, `wrong_paid_amount`, `wrong_due_amount`, `other`).
     - Enforces server-side payment ownership check (devotee can only link payments they submitted).
     - Anti-duplicate submission check (blocks identical descriptions submitted within 10 minutes).
     - Attachment validation: images/PDF up to 5MB.
   - `app/Http/Requests/PublicPortal/AdminComplaintReplyRequest.php`:
     - RBAC authorization (`respond_complaints`, `reply_public_complaints`, `manage_complaints`, `super_admin`).
     - Terminal status immutability guard (blocks replying to `resolved`, `rejected`, or `closed` complaints).
     - Validates reply content (min:3, max:5000) and optional status progression (`under_review`, `replied`, `resolved`).
   - `app/Http/Requests/PublicPortal/AdminComplaintStatusRequest.php`:
     - RBAC authorization (`resolve_complaints`, `resolve_public_complaints`, `manage_complaints`, `super_admin`).
     - Auto-infers status from named routes (`portal.complaints.resolve`, `reject`, `close`, `under-review`).
     - Terminal status immutability guard.
     - Mandatory resolution note (min:3, max:5000).
     - Mandatory rejection reason (min:3, max:5000).
   - `app/Http/Requests/PublicPortal/AdminComplaintNoteRequest.php`:
     - RBAC authorization for private notes (`manage_complaints`, `view_complaints`, `super_admin`).
     - Validates note content (min:3, max:5000).

4. **Controllers & Routes**:
   - `app/Http/Controllers/Public/PaymentSubmissionController.php`:
     - Added `show(Request $request, string $paymentCode)`: Detailed payment view with IDOR protection, pledge breakdown, verified vs pending allocation segregation, and voluntary contribution card.
     - Added `downloadReceipt(string $receiptNumber)`: Dedicated receipt download endpoint with active receipt verification (`STATUS_VERIFIED` only, non-voided only) and IDOR ownership verification.
     - Hardened `receipt()` with non-voided verified status guard.
     - Updated `history()` query eager-loading (`allocations.pledge`, `allocations.event`, `reverser`, `rejecter`).
   - `app/Http/Controllers/Public/PublicComplaintController.php`:
     - `index()`: Authenticated devotee complaint listing with status, category, and search filters.
     - `create()`: Complaint submission form pre-loading donor's payments and published events.
     - `store()`: Atomic creation of complaint record and initial `ComplaintActivity` (`visibility: donor`), with audit logging.
     - `show()`: Complaint detail view with IDOR verification, strictly passing only `$complaint->donorVisibleActivities` (internal notes completely isolated).
   - `app/Http/Controllers/PublicPortal/ComplaintController.php`:
     - Added `markUnderReview()` action.
     - Updated `respond()`, `resolve()`, `rejectComplaint()`, `close()`, and `addInternalNote()` to record structured `ComplaintActivity` entries.
     - Enforced terminal status immutability across all endpoints.
     - DataTables formatters updated with `replied` status badge, payment context, and action buttons.
   - `routes/web.php`:
     - Registered `public.payments.show` (`GET /my-contributions/{paymentCode}`).
     - Registered `public.payments.receipt.download` (`GET /my-contributions/receipt/{receiptNumber}/download`).
     - Registered `public.complaints.index` (`GET /my-complaints`).
     - Registered `public.complaints.create` (`GET /my-complaints/create`).
     - Registered `public.complaints.store` (`POST /my-complaints`).
     - Registered `public.complaints.show` (`GET /my-complaints/{reference}`).
     - Registered admin route aliases: `under-review` and `reply`.

5. **User Interface Implementation**:
   - `resources/views/public/payments/show.blade.php`:
     - Full payment breakdown: payment reference, date/time, amount, method, sender mobile, transaction reference, receiver account details, status badge with contextual alerts (rejection reason, reversal info & voided receipt note, pending notice).
     - Allocation & pledge summary with verified paid amount and remaining due calculations.
     - Voluntary contribution card for unpledged event or general donations.
     - Receipt View and Download action buttons (rendered only for verified payments with active receipts).
     - Direct "Report Issue" link to pre-fill complaint form.
   - `resources/views/public/payments/history.blade.php`:
     - Added `reversed` filter option in status dropdown.
     - Added `reversed` badge styling.
     - Actions column updated with View Receipt and Download Receipt buttons for verified payments, and eye icon button linking to full payment details.
     - Modal updated with reversal information and "View Full Breakdown" button.
   - `resources/views/public/complaints/index.blade.php`:
     - Devotee complaint listing with reference code, category badge, related payment/event link, submitted date, status badge, latest admin reply, and view action.
     - Filter card for status, category, and search.
     - Empty state with direct "Submit Complaint" action.
   - `resources/views/public/complaints/create.blade.php`:
     - 4 complaint categories dropdown, optional related payment dropdown, optional related event dropdown, description textarea with dynamic character counter (min:5, max:2000), file attachment upload, financial disclaimer alert, and submit button loading state.
   - `resources/views/public/complaints/show.blade.php`:
     - Complaint details header with reference, status badge, category, submitted timestamp.
     - Complainant and related payment/event context card.
     - Original inquiry description and attachment download link.
     - Chronological communication timeline displaying donor-visible submissions, status changes, and official committee replies (strictly zero internal notes).
   - `resources/views/portal/complaints/index.blade.php`:
     - Server-side DataTables strictly without row number / `#` column.
     - Filter controls for status (including `replied`), category, priority, assigned staff, and event.
   - `resources/views/portal/complaints/show.blade.php`:
     - Official admin response drafting form with resolution checkbox.
     - Private internal notes thread.
     - Unified activity timeline with distinct visual styling for internal notes vs donor-visible activities.
     - Action buttons for Mark Under Review, Resolve (modal), Reject (modal), and Close.
     - Terminal status immutability alert when complaint is resolved, rejected, or closed.
   - `resources/views/layouts/public.blade.php`:
     - Added "My Complaints" navigation link alongside "My Contributions" for authenticated devotees.

6. **Bilingual Localization (EN & BN)**:
   - 100% key and text parity maintained across `lang/en/portal.php` and `lang/bn/portal.php` for:
     - Complaint categories: `payment_not_updated`, `wrong_paid_amount`, `wrong_due_amount`, `other`.
     - Complaint statuses: `submitted`, `under_review`, `replied`, `resolved`, `rejected`, `closed`.
     - Admin complaint module labels: `mark_under_review`, `terminal_status_error`, `activity_history`, `internal_note_badge`, `donor_visible_badge`, `rejection_reason_required`, `resolution_note_required`.
     - Public donor portal labels: `my_complaints`, `my_complaints_subtitle`, `submit_complaint`, `complaint_reference`, `timeline`, `original_inquiry`, `status_reversed`, `reversal_reason_label`, `reversed_at_label`, `anti_duplicate_error`, `invalid_payment_ownership`, `voluntary_contribution_details`.

7. **Automated Feature Tests**:
   - `tests/Feature/PublicPortal/DonorPaymentDetailsTest.php` (9 tests, 32 assertions):
     - Guest redirection to login from payment history and details.
     - Donor can view own payment history and filter by `reversed` status.
     - Donor can view own payment details breakdown page.
     - Cross-donor cannot view another donor's payment details (403 Forbidden).
     - Verified payment with receipt exposes View and Download receipt actions.
     - Reversed or pending payment does NOT expose active receipt; download receipt endpoint returns 403/404.
     - Cross-donor cannot download receipt of another donor (IDOR check).
     - Only verified allocations count towards paid amount; pending/rejected/reversed allocations do not.
     - Voluntary contributions displayed cleanly without pledge.
   - `tests/Feature/PublicPortal/DonorComplaintTest.php` (9 tests, 40 assertions):
     - Guest is redirected to login or denied.
     - Donor can view own complaints list.
     - Donor can submit valid complaint with `CMP-YYYY-XXXXXXXX` reference format.
     - Invalid complaint data is rejected (short description, invalid category).
     - Donor cannot attach another donor's payment.
     - Donor can view own complaint details and activity timeline.
     - Cross-donor cannot view another donor's complaint (403 Forbidden).
     - Admin replies are visible to donor while internal notes are strictly hidden.
     - Anti-duplicate submission is prevented (identical description within 10 minutes rejected).
   - `tests/Feature/PublicPortal/AdminComplaintManagementTest.php` (11 tests, 63 assertions):
     - Guest cannot access admin complaints.
     - Devotee without permission is denied.
     - Authorized admin can view complaints DataTable strictly without `#` column.
     - Admin can filter complaints by status, category, and event.
     - Admin can assign complaint to staff.
     - Admin can mark complaint under review.
     - Admin can submit reply to donor.
     - Admin can resolve complaint with mandatory note.
     - Admin can reject complaint with mandatory reason.
     - Terminal status immutability: resolved/rejected/closed complaints cannot be modified.
     - Admin can add private internal notes with isolated visibility.
   - `tests/Feature/PublicPortal/ComplaintFinancialIntegrityTest.php` (1 test, 10 assertions):
     - Full complaint lifecycle (submission, under review, reply, internal notes, resolution, closure) strictly never modifies payment status, payment amount, allocation status, allocation amount, pledge status, pledge amount, receipt void state, journal entries count, or journal lines count.

8. **Verification & Regression Metrics**:
   - Increment 6 Dedicated Test Suite: **30 passed (145 assertions)** across the 4 new test classes.
   - Total Public Portal Feature Test Suite: **221 passed (1,243 assertions)** with 100% PASS rate.
   - Full Application Test Suite (`php artisan test`): **490 passed (2,851 assertions)** with ZERO failures, ZERO errors, and ZERO regressions across the entire application.
   - Code Style (`php vendor/bin/pint --test`): **100% PSR-12 code compliance** (`{"tool":"pint","result":"passed"}`).

9. **Current Status**:
   - **Increment 6 is 100% COMPLETE**.
   - Awaiting explicit user review and approval before proceeding to subsequent increments.

---

## Public Portal - Increment 6: Post-Implementation Security & Integrity Corrections (COMPLETED)

- **Objective**: Address security, authorization, and data integrity items identified during post-implementation inspection of Increment 6 (Payment Details & Complaint Management), strictly preserving all approved Increment 5 and Increment 6 functionality without breaking regressions.

1. **Private Complaint Attachment Security**:
   - **Private Storage**: Updated `PublicComplaintController::store()` to save attachments directly to private storage (`local` disk: `storage/app/complaints/...`), completely avoiding the `public` storage symlink.
   - **Public Symlink Elimination**: Removed all direct `asset('storage/...')` and `Storage::disk('public')->url(...)` calls for complaint attachments. Updated `Complaint::getAttachmentUrlAttribute()` to reference the authenticated admin route.
   - **Dedicated Secure Donor Download**: Implemented `GET /my-complaints/{reference}/attachment` (`public.complaints.attachment`) in `PublicComplaintController::downloadAttachment()`.
     - Validates session authentication (`auth()->check()`).
     - Strictly enforces IDOR ownership: `$isOwner = ($donor && (int) $complaint->person_id === (int) $donor->id);` or authorized staff permission (`view_complaints|view_public_complaints`).
     - Cross-donor unauthorized download attempts return HTTP 403 Forbidden.
     - Unauthenticated requests are redirected to login.
     - Protects against directory traversal via `str_contains($path, '..')` (HTTP 400 Bad Request).
     - Checks `Storage::disk('local')` with safe backward-compatibility fallback to `Storage::disk('public')` for legacy files.
   - **Dedicated Secure Admin Download**: Implemented `GET /admin/portal/complaints/{complaint}/attachment` (`portal.complaints.attachment`) in `ComplaintController::downloadAttachment()`.
     - Gated by middleware `permission:view_complaints|view_public_complaints`.
     - Protected against path traversal.
     - Downloads and previews attachments safely without exposing raw server file paths.
   - **Server-Side Validation**: `StoreDonorComplaintRequest` validates `'attachment' => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120']`.

2. **Complaint Reference Database Uniqueness & Concurrency Safety**:
   - Verified that `complaints.tracking_number` possesses a database-level `UNIQUE` index in migration `2026_11_02_000030_create_complaints_table.php` (`$table->string('tracking_number', 32)->unique();`).
   - Hardened `PublicComplaintController::store()` with a 5-attempt concurrency retry loop catching `QueryException` (duplicate key / code 23000) ensuring atomic uniqueness even under extreme microsecond concurrency.

3. **Complaint Activity History Preservation**:
   - Updated migration `2026_11_15_000034_create_complaint_activities_table.php` foreign key constraint from `cascadeOnDelete()` to `restrictOnDelete()` (`$table->foreignId('complaint_id')->constrained('complaints')->restrictOnDelete()`).
   - Hard deletion (`$complaint->forceDelete()`) is now blocked at the database level by foreign key integrity constraints whenever activity logs exist.
   - Model `Complaint` uses `SoftDeletes`, ensuring ordinary deletion (`$complaint->delete()`) soft-deletes the record and preserves all activity and audit trails intact.
   - Confirmed no hard-delete actions or routes exist in `PublicComplaintController` or admin `ComplaintController`.

4. **Related Payment & Event Consistency**:
   - Enforced in `StoreDonorComplaintRequest::withValidator()`:
     - Payment ownership: verified that `payment_code` belongs to authenticated donor.
     - Event vs Payment linkage: if both `payment_code` and `event_id` are supplied, the selected event must match `$payment->event_id`. If payment has no event, selecting an event is rejected with `portal.public.event_payment_mismatch`.
     - Event publication status: selected `event_id` must be active, published (`publication_status === 'published'`), and not in draft status.
   - In `PublicComplaintController::store()`, `$event` is derived from `$payment->event` when a payment is linked.

5. **Admin Permissions & Assignment Hardening**:
   - In `ComplaintController::assign()`:
     - Enforces that `assigned_to` belongs to an active user (`status === 'active'`) with staff/admin roles (`super_admin`, `temple_admin`, `committee_member`, `treasurer`) or complaint permissions (`view_complaints`, `manage_complaints`, `assign_complaints`).
     - Ordinary devotees without permissions are strictly rejected from assignment (HTTP 422 / error).
     - Access to `assign` action is server-enforced by middleware `permission:assign_complaints|manage_complaints|manage_public_complaints`.
     - Creates `ComplaintActivity` (`assignment`) recording both the previous assignee and the new assignee: `"Assignment changed from [Previous] to [New]"` or `"Assigned to [New]"`.
     - Logs assignment action with actor, previous assignee, and new assignee in `AuditLogger`.
   - Updated staff user dropdown queries in `index()` and `show()` to filter out ordinary devotees.

6. **Anti-Duplicate Submission**:
   - Idempotency check in `StoreDonorComplaintRequest` strictly configured for a 10-minute duplicate window (`subMinutes(10)`).
   - Scoped strictly to `person_id === $donor->id`, ensuring different donors submitting similar or identical issue descriptions are never falsely blocked.

7. **Financial Ledger Invariants Reconfirmed**:
   - Zero complaint workflows modify `CollectionPayment`, `PaymentAllocation`, `Pledge`, `Receipt`, `JournalEntry`, or `JournalLine`.
   - Verified that all ledger entries remain 100% immutable throughout the entire complaint lifecycle.

8. **Localization Parity**:
   - Maintained full parity across `lang/en/portal.php` and `lang/bn/portal.php` for:
     - `no_attachment_file`, `event_payment_mismatch`, `invalid_or_unpublished_event`.
     - `status_updated`, `invalid_assignee`, `internal_note_saved`, `unassigned`.

9. **Verification & Regression Metrics**:
   - `tests/Feature/PublicPortal/DonorComplaintTest.php`: **16 passed (58 assertions)**.
   - `tests/Feature/PublicPortal/AdminComplaintManagementTest.php`: **15 passed (74 assertions)**.
   - `tests/Feature/PublicPortal/ComplaintFinancialIntegrityTest.php`: **1 passed (10 assertions)**.
   - `tests/Feature/PublicPortal/DonorPaymentDetailsTest.php`: **9 passed (32 assertions)**.
   - Dedicated Increment 6 Test Suite: **41 passed (174 assertions)**.
   - Full Application Test Suite (`php artisan test`): **501 passed (2,880 assertions)** with ZERO failures, ZERO errors, and ZERO regressions across the entire application.
   - Code Style (`php vendor/bin/pint --test`): **100% PSR-12 code compliance** (`{"tool":"pint","result":"passed"}`).

10. **Public Portal Demo Dataset & Manual QA Readiness**:
    - **Dedicated Demo Seeder**: Created `database/seeders/PublicPortalDemoSeeder.php` implementing safe, repeatable, and idempotent seeding of all Public Portal scenarios.
    - **Administrative Accounts**: Ensured `admin@temple.local` (`Admin@123456`, `super_admin`) and `manager@temple.local` (`Manager@123456`, `temple_admin`).
    - **Receiver Accounts**: Created 4 active, verified receiver accounts: bKash Merchant (`01711001122`), Nagad Merchant (`01811002233`), Rocket Merchant (`01911003344`), and Bank Current Account (`4401200033445`, Sonali Bank PLC).
    - **Events Catalog**: Created 4 distinct events:
      - `Sharodiya Durga Puja 2026` (`durga-puja-2026`, active, published, open for public payments).
      - `Shyama Kali Puja & Deepavali 2026` (`shyama-kali-puja-2026`, upcoming, published).
      - `Shri Krishna Janmashtami 2026` (`janmashtami-festival-2026`, closed, published archive).
      - `Saraswati Puja Planning 2027` (`saraswati-puja-planning-2027`, draft, unpublished — hidden from public).
    - **10 Fictional Donors with Linked Person & User Profiles** (password: `Donor@123456`):
      1. `subir.donor@temple.local` (Subir Ghosh, `public`, fully paid pledge: 10,000 / 10,000, 0 due, verified).
      2. `ananya.donor@temple.local` (Ananya Roy, `public`, partially paid: 8,000 / 15,000, 7,000 due, verified).
      3. `bikash.donor@temple.local` (Bikash Chandra Das, `anonymous`, fully paid: 12,000 / 12,000, masked as "Anonymous Donor").
      4. `sunil.donor@temple.local` (Sunil Kumar Sen, `private`, partially paid: 10,000 / 20,000, masked as "Private Devotee").
      5. `prabir.donor@temple.local` (Prabir Sengupta, `public`, pending verification: 8,000 BDT, private proof upload).
      6. `tapash.donor@temple.local` (Tapash Paul, `public`, rejected payment: 5,000 BDT, rejected reason displayed).
      7. `debabrata.donor@temple.local` (Debabrata Bhattacharya, `public`, reversed payment: 6,000 BDT, voided receipt, restored due).
      8. `madhuri.donor@temple.local` (Madhuri Chakraborty, `public`, voluntary event contribution without pledge: 3,000 BDT).
      9. `kalyan.donor@temple.local` (Kalyan Kumar Bose, `public`, excess payment: 7,000 BDT -> 5,000 pledge + 2,000 voluntary).
      10. `gopal.donor@temple.local` (Gopal Chandra Dey, `public`, unpaid pledge: 0 / 5,000, 5,000 due).
    - **Collection Summary Arithmetic**:
      - Total Pledged: `86,000.00 BDT`
      - Verified Pledge Collections: `45,000.00 BDT`
      - Excess Contributions: `2,000.00 BDT`
      - Voluntary Event Donations: `3,000.00 BDT`
      - Total Verified Collections: `50,000.00 BDT` (58.14%)
      - Pending Collections: `8,000.00 BDT`
      - Outstanding Pledges: `41,000.00 BDT`
    - **5 Complaint Management Scenarios**:
      1. `CMP-DEMO-001`: Payment Not Updated (`submitted`, Prabir Sengupta).
      2. `CMP-DEMO-002`: Wrong Paid Amount (`under_review`, Tapash Paul, with private PDF bank statement attachment).
      3. `CMP-DEMO-003`: Wrong Due Amount (`replied`, Ananya Roy, with admin response & internal note).
      4. `CMP-DEMO-004`: Other (`resolved`, Kalyan Kumar Bose, with resolution note).
      5. `CMP-DEMO-005`: General Inquiry (`rejected`, Gopal Chandra Dey, with policy rationale).
    - **Safe Cleanup Mechanism**: Created `app/Console/Commands/CleanupPublicPortalDemoData.php` (`php artisan demo:public-portal-cleanup`) safely removing ONLY demo-tagged records without modifying real financial transactions or users.
    - **Documentation**: Generated complete manual QA testing guide in `PUBLIC_PORTAL_DEMO_GUIDE.md`.
    - **Automated Verification**: Created `tests/Feature/PublicPortal/DemoDataSetupTest.php` passing all 30 assertions.

11. **STOP Condition Applied**:
    - Public Portal Demo Data Setup & Manual QA Readiness is 100% COMPLETE.
    - Increments 1 through 6 are fully verified and ready for manual owner exploration.
    - STRICT PROHIBITION OBSERVED: Zero business features modified. Increment 7 NOT started.
    - Awaiting explicit user feedback and approval.

12. **Devotee Name & Mobile Number Select2 Remote AJAX Search Integration**:
    - **Public Event Donor Search**: Replaced plain `#donorSearchInput` in `resources/views/public/events/show.blade.php` with a remote Select2 dropdown querying `route('public.events.donors.search', $event->slug)` in real time.
    - **Name & Mobile Search Backend**: Implemented `EventController::searchDonors` and updated `EventCollectionReportService::getDonorCollectionReport` to search directly against devotee names (English and Bengali), mobile numbers (`phone`, `public_donor_phone`, `sender_mobile`), and codes.
    - **Privacy Protection Maintained**: Respects `anonymous` and `private` visibility rules; personal mobile numbers and confidential identities are strictly masked from public suggestions.
    - **Real-Time Suggestions & Synchronized Table Filtering**: Select2 displays formatted suggestions with **Devotee Name on top** and **Mobile Number below** (`<i class="bi bi-telephone"></i>`). PLG numbers (`PLG-2026-XXXX`) have been completely removed from the selection options per UI requirements. Selecting a suggestion narrows and highlights the corresponding donor row in `#donorReportTable`; clearing restores all rows.
    - **Select2 Box Width & UI Layout Refinement**: Fixed the search box container width to properly fill the available header space (`flex-grow-1`, max-width: 650px) alongside the status filter without wrapping or leaving empty right margins. Added enhanced contrast styling on highlighted options for readability.
    - **Global Admin & Portal Devotee Search Parity**:
      - Updated `PersonController::search` (`route('people.search')`) to include mobile numbers in Select2 suggestion text (`{name} - {phone} ({code})`).
      - Updated `PublicPaymentController::searchPersons` (`route('portal.payments.search-persons')`) with mobile numbers and localized names.
      - Updated `resources/views/portal/complaints/show.blade.php` link devotee select with remote Select2 search.
    - **Select2 Asset Integration in Public Layout**: Added Select2 CSS and JS bundles to `resources/views/layouts/public.blade.php` along with bilingual `select2i18n` configurations in `TempleConfig`.
    - **Automated Test Coverage**: Created `tests/Feature/PublicPortal/EventDonorSearchTest.php` (8 tests, 25 assertions) verifying bilingual name search, phone search, privacy masking, and admin search endpoints. 100% clean pass across all 241 Public Portal tests and Laravel Pint PSR-12 code hygiene.

13. **Temple Emblem Icon & Sacred Symbol High-Contrast Display Fix**:
    - **Identified Root Cause**: In five key public UI locations (Home hero welcome badge, Hero showcase card, CTA card, Public footer, and Event details showcase banner), `.temple-emblem` had inline `background: var(--temple-gold);`, while the CSS class defined `color: var(--temple-gold);`. The identical text color and background color caused the sacred `ॐ` emblem symbol to render completely invisible (solid circle).
    - **High-Contrast Styling Enforced**: Explicitly added `color: #ffffff;` inline and updated `public/assets/css/temple-theme.css` with selector `.temple-emblem[style*="background: var(--temple-gold)"] { color: #ffffff !important; border-color: rgba(255, 255, 255, 0.5) !important; }`.
    - **Cross-Platform Font Stack**: Ensured robust Devanagari font fallbacks (`Hind Siliguri`, `Segoe UI`, `Noto Sans Devanagari`, Arial) for crisp glyph rendering across all operating systems and browsers.
    - **Full Verification**: All Public Portal tests pass (242 tests, 1,331 assertions) and Pint PSR-12 code style passed cleanly.

14. **Increment 7 — Previous Event Accounts & Public Financial Reports**:
    - **Dual-Source Financial Architecture**:
      - **Source 1 (System-Generated Financial Summary)**: Authoritative double-entry ledger calculation utilizing `FinancialReportService::getIncomeExpenseStatement` and `EventCollectionReportService::getMetrics`. Displays Total Verified Income, Total Approved Expenses, Net Surplus/Deficit, and Outstanding Pledges alongside categorized income/expense accounts and % shares. Integrates formal Event Settlement details (transferred surplus to General Temple Fund 3010). Graceful unrecorded state for historical periods without software ledger vouchers.
      - **Source 2 (Audited Statements & Official Documents)**: Admin-uploaded verified statements, statutory audit reports, expense category spreadsheets, and settlement summaries.
    - **Database Migration & Model Architecture**:
      - Added `uuid` (`CHAR(36)` unique), `report_category` (indexed string), and `financial_year` (indexed integer) to `event_documents` via migration `2026_11_17_234443_add_report_category_and_uuid_to_event_documents_table`.
      - Model constants: `CATEGORY_COMPLETE_FINANCIAL_STATEMENT`, `CATEGORY_INCOME_REPORT`, `CATEGORY_EXPENSE_REPORT`, `CATEGORY_EXPENSE_BREAKDOWN`, `CATEGORY_EVENT_SETTLEMENT`, `CATEGORY_OTHER`.
      - Automatic UUID generation and default `financial_year` hook via `booted()` callback.
    - **Admin Document Management Enhancement**:
      - Form Requests (`StoreEventDocumentRequest`, `UpdateEventDocumentRequest`) updated with strict validation for report categories, years, and file types (`pdf,jpg,jpeg,png,webp,xlsx,xls,csv`).
      - Admin views (`create.blade.php`, `edit.blade.php`, `show.blade.php`, `index.blade.php`) updated with Select2 event selection, category badges, Excel/CSV file icons, and DataTables AJAX filtering.
    - **Public Previous Events Archive & Show UI**:
      - Created `PreviousEventArchiveController` with `index`, `show`, `previewDocument`, and `downloadDocument`.
      - Archive Index (`resources/views/public/previous_events/index.blade.php`): Filterable by keyword search, category, financial year, and report category with published document count badges.
      - Event Financial Report Details (`resources/views/public/previous_events/show.blade.php`): Responsive dual-tab design, key financial summary cards, categorized income/expense tables, completed settlement card, audited documents list, and inline PDF/Image preview modal.
    - **Strict IDOR & Security Invariants**:
      - Public URLs strictly use event `$slug` and document `$uuid` (e.g. `/previous-events/{slug}/documents/{uuid}/preview`), eliminating sequential database integer ID exposure.
      - Dual publication guard: both event and document must be published and valid; unauthorized or draft access returns 404.
      - Path traversal guard: prevents `..` sequences and restricts file serving to `private/event_documents/` on `local` disk.
      - Download responses enforce `X-Content-Type-Options: nosniff`.
    - **Localization & Navigation Integration**:
      - Replaced disabled placeholder in `resources/views/layouts/public.blade.php` with active *“Previous Events & Financial Reports”* nav link and added footer link.
      - 100% bilingual parity between `lang/en/portal.php` and `lang/bn/portal.php`.
      - Permanent redirect from legacy `/event-accounts` to `/previous-events`.
    - **Comprehensive Automated Testing & Final Functional Verification**:
      - Updated `tests/Feature/PublicPortal/PreviousEventArchiveTest.php` with 22 tests (119 assertions), covering:
        1. Public archive guest access, search, and liturgical/report filtering.
        2. Double-entry ledger calculation integrity (exclusion of opening balances, asset transfers, and unposted/reversed vouchers).
        3. Completed fund settlement presentation and friendly unrecorded historical state handling.
        4. In-browser PDF preview stream and secure spreadsheet downloads (.xlsx, .xls, .csv).
        5. IDOR prevention (UUID + slug) and path traversal guard.
        6. **E2E Scenario 1**: Existing historical event upload, publish, and in-browser PDF preview verification.
        7. **E2E Scenario 2**: Historical event creation on-the-fly (`event_mode: new`) without ledger records, upload of final closing report, and immediate public archive availability.
        8. **E2E Scenario 3**: Excel report (.xlsx) upload, publish, and authenticated public download with proper attachment headers.
        9. **E2E Scenario 4**: Unpublish security test verifying immediate public block (HTTP 404 on preview and download) and re-publish access restoration.
      - **On-the-Fly Historical Event Creation**: Admin upload modal supports adding historical events directly (`event_mode: new`) without any prior ledger entries, pledges, or database records (`status: closed`, `publication_status: published`, sequential code `EVT-HIST-{year}-{random}`).
      - **Uploaded Document Final Authority**: Uploaded document is treated as the official final closing statement; Tab 1 is default active displaying the embedded PDF viewer (`<iframe>`) and direct Excel download buttons; Tab 2 displays supplementary double-entry ledger records.
      - **Full Test Suite Status**:
        - `PreviousEventArchiveTest`: 22 passed, 0 failed (119 assertions).
        - Total Public Portal Feature Suite (`tests/Feature/PublicPortal`): 264 passed, 0 failed (1,450 assertions).
        - Core Accounting Regression (`EventDonationAndSettlementTest`, `FinanceAndAccountingTest`): 33 passed, 0 failed (233 assertions).
      - **100% PSR-12 Code Compliance**: Verified via Laravel Pint (`php vendor/bin/pint`).
      - Operational guide updated in `PUBLIC_FINANCIAL_REPORTS_GUIDE.md`.

15. **Increment 7 Completion**:
    - Increment 7 — Previous Event Accounts & Public Financial Reports was 100% completed and approved.

16. **Increment 8 — Devotee Self-Service Portal & Event-wise Payment System**:
    - **Devotee Account Provisioning & Authentication**:
      - Automatic user account provisioning when Admin registers a devotee with a phone number in Directory (`PersonController::store` $\rightarrow$ `DevoteeCredentialService::provisionDevoteeAccount`).
      - Linked `person_id` foreign key (`nullable`, `unique`, `nullOnDelete`) on `users` table.
      - Made `users.email` nullable with unique index intact to accommodate devotees without email.
      - Phone number authentication with Bangladesh phone number normalization variants (`01XXXXXXXXX`, `+8801XXXXXXXXX`, `8801XXXXXXXXX`).
      - Ambiguous phone number guard prevents silent incorrect linking when multiple active devotees share identical numbers.
      - Devotees authenticate seamlessly using Phone Number + Password (no mandatory first-login password change).
      - Inactive devotee accounts rejected; unlinked users blocked by `EnsureDevoteeAccountLinked` middleware (`alias: devotee.linked`).
    - **Dedicated Password Storage Architecture**:
      - `users.password` holds standard Laravel one-way hash (`Hash::make`).
      - `devotee_credentials` table stores encrypted copy (`Crypt::encryptString`) linked strictly 1-to-1 to devotee user.
      - Centralized `DevoteeCredentialService` guarantees atomic synchronization between hash and encrypted copy inside database transactions (`DB::transaction`).
      - Admin password management: authorized admins (`view_devotee_passwords`) can reveal decrypted passwords with mandatory audit reasoning (`AuditLogger`).
      - Authorized admins (`update_devotee_passwords`) can reset passwords, updating both storage locations atomically.
      - Plaintext passwords strictly prohibited from appearing in DataTables, AJAX responses, and audit logs.
    - **Devotee Dashboard & Financial Summary**:
      - 4 responsive summary metrics: Total Pledged (across all events), Total Paid (verified payments only), Total Due (remaining obligations), and Pending Verification.
      - Pending payments strictly excluded from paid calculations and due reductions until formally verified by admin.
      - Running Event Cards: independently displays each running event with its own Pledge, Paid, Due, and direct "Pay Now" action.
      - Recent Payments feed with status badges and quick offcanvas link.
    - **Strict Server-Side Running Event Eligibility**:
      - Payments accepted strictly for **Running Events** satisfying:
        1. Status is strictly `active` (`Event::STATUS_ACTIVE`). Upcoming, closing, closed, or draft events reject payments.
        2. Publication status is `published` (`Event::PUB_STATUS_PUBLISHED`). Unpublished events return 404.
        3. Current date falls within collection window boundaries (`collection_start_date` and `collection_end_date`).
      - Implemented `Event::isRunningForPayment(): bool` and `Event::scopeRunningForPayment()`.
      - Enforced in `PaymentSubmissionController` (`create` redirects with warning, `store` aborts 404/validates).
    - **Event-Wise Payment Isolation & My Payments Ledger**:
      - Strict financial isolation: payment for Event A applies solely to Event A and never reduces Event B due.
      - My Payments DataTables (`/devotee/payments`): server-side AJAX with event, status, method, and date filters. Strictly NO serial `#` column.
      - Offcanvas payment details modal displaying full transaction timeline, MFS references, and verified receipt links.
      - IDOR protection: devotees can only access their own payments and receipts.
      - Double-entry ledger invariant: pending submissions create zero journal entries and zero receipts until verified.
    - **Devotee Profile Management & Email OTP Security**:
      - Devotees can view profile and update non-sensitive fields (`present_address`, `permanent_address`, `occupation`, `email`).
      - Self-service profile updates and password changes strictly require verifying a single-use Email OTP (`EmailOtpService`).
      - OTP specifications: 6 digits, cryptographically random, hashed in database (`sha256`), 10-minute expiry, single-use, max 5 attempts brute-force limit, 3 requests per 10 minutes rate limit.
      - Dedicated mailable and template: `DevoteeOtpMail` and `resources/views/emails/devotee_otp.blade.php`.
      - Password change requires verifying current password + valid OTP, synchronizing hash and encrypted copy atomically.
      - Devotees without registered email cannot self-update, must contact temple administration.
    - **Localization & Navigation Integration**:
      - 100% bilingual parity across `lang/en/devotee.php` and `lang/bn/devotee.php` (55+ keys).
      - Added login placeholder keys to `lang/en/auth.php` and `lang/bn/auth.php`.
      - Integrated Devotee Portal links into Admin Sidebar (`sidebar.blade.php`) and Public Navbar (`public.blade.php`).
    - **Automated Test Suite (100% Passing)**:
      - `tests/Feature/Devotee/DevoteeAccountAndAuthTest.php`: 9 tests (32 assertions).
      - `tests/Feature/Devotee/DevoteeCredentialTest.php`: 7 tests (27 assertions).
      - `tests/Feature/Devotee/DevoteeDashboardAndEligibilityTest.php`: 6 tests (23 assertions).
      - `tests/Feature/Devotee/DevoteePaymentAndHistoryTest.php`: 5 tests (22 assertions).
      - `tests/Feature/Devotee/DevoteeProfileAndOtpTest.php`: 8 tests (24 assertions).
      - **Total Devotee Test Suite**: 35 passed, 0 failed (128 assertions).
      - **Full Regression Suites**: `AuthenticationTest` (6 passed), `PublicPortal` (264 passed), `EventDonationAndSettlementTest` (15 passed), `FinanceAndAccountingTest` (18 passed).
    - **100% PSR-12 Code Compliance**: Verified via Laravel Pint (`php vendor/bin/pint`).

    - **Equal Card Height & Deity Banner Imagery Integration**:
      - Standardized equal card height (`h-100` flex column) across `/events`, `/previous-events`, and `public/home.blade.php`.
      - Clamped descriptions to max 3 lines (`-webkit-line-clamp: 3`) and standardized title heights (`min-height: 3.1rem`) with action buttons pinned cleanly to the card bottom (`mt-auto`).
      - Created high-resolution deity banner imagery (`public/assets/images/events/`): Durga Puja, Kali Puja, Krishna Janmashtami, Lakshmi Puja, Saraswati Puja, Shiva Ratri, and a general Temple sanctum fallback.
      - Implemented `getFeaturedImageUrlAttribute()` and `getBannerUrlAttribute()` on `App\Models\Event` for automatic fallback detection based on event name and category.
    - **Payment Method Official Logos (Full Logo & Short Logo Integration)**:
      - Integrated user-stored brand assets from `public/assets/images/methods/`:
        - `bkash-full-logo.png` & `bkash-short-logo.png`
        - `nagad-full-logo.png` & `nagad-short-logo.png` (with `nagod-*.png` alias support)
        - `rocket-full-logo.png` & `rocket-short-logo.png`
        - `bank.svg` & `cash.svg`
      - **Where Full Logo is Used**:
        1. **Payment Method Selection Radio Cards** (`/events/{slug}/pay`): Big, prominent, recognizable full logos inside `.payment-method-card` with customized brand hover and active borders.
      - **Where Short Logo (Icon) is Used**:
        1. **Inline Payment Badges & Reports**: `partials.payment-method-badge` and `components.payment-method-badge` with `variant="short"` (square icon next to localized method name).
        2. **Receiver Account Preview Card**: Short logo badge in header.
        3. **DataTables, Drawers & Lists**: Public payment history, Devotee dashboard & payments, Admin portal payments, Receiver accounts list & show, Collections register, Official receipts, and Finance income/expense reports.
      - Support for `variant="full"` or `variant="short"` in `payment-method-badge`.
      - All 571 tests passing (3,210 assertions) with 100% PSR-12 compliance via Laravel Pint.

---

## Phase 6 — Procurement Reports Module: Complete Implementation, Integration & Audit

1. **Objective & Scope**:
   - Integrated and completed the Procurement Reports section of the Temple Management System Admin Panel under the parent menu **Procurement Reports / ক্রয় সংক্রান্ত প্রতিবেদন** (formerly "সংগ্রহ সংক্রান্ত প্রতিবেদন"):
     1. **Purchase Requisition Report** (`procurement.reports.requests`)
     2. **Purchase Order Report** (`procurement.reports.orders`)
     3. **Outstanding Vendor Bills Report** (`procurement.reports.outstanding-bills`)
   - Strictly read-only, non-mutating reporting layer maintaining server-enforced security, zero duplicate calculations, and full double-entry financial integrity.

2. **Database Migration & Granular Permissions**:
   - Migration `2026_11_25_000035_add_procurement_report_permissions.php`:
     - Registered 5 granular permissions: `view_procurement_reports`, `view_purchase_requisition_reports`, `view_purchase_order_reports`, `view_outstanding_vendor_bill_reports`, `export_procurement_reports`.
     - Seeded into `RoleAndPermissionSeeder.php` and mapped to `super_admin`, `temple_admin`, `treasurer`, and `committee_member`.

3. **Centralized Service Layer (`App\Services\ProcurementReportService`)**:
   - **Purchase Requisitions Report**:
     - `getPurchaseRequestsQuery(array $filters)`: Dynamic filters for date range, requisition status (`draft`, `submitted`, `approved`, `rejected`, `cancelled`), requester, approver, amount range, and search.
     - `getPurchaseRequestsSummary(array $filters)`: Live aggregate totals (Total Count, Total Estimated Amount, Draft, Pending, Approved, and Rejected/Cancelled counts).
   - **Purchase Orders Report**:
     - `getPurchaseOrdersQuery(array $filters)`: Filters by vendor, date range, status (`draft`, `approved`, `sent`, `completed`, `cancelled`), requisition reference, amount range, and search.
     - `getPurchaseOrdersSummary(array $filters)`: Strict segregation of metrics — Total Historical PO Value, Active Committed Procurement (Approved + Sent, excluding draft and cancelled), Completed Orders, Draft Orders, and Cancelled Orders.
   - **Outstanding Vendor Bills Report**:
     - Authoritative calculation: **`Outstanding Amount = Approved Bill Amount − Net Posted Payment Allocations`**.
     - ANSI-SQL correlated subquery filtering strictly `vendor_payments.status = 'posted' AND vendor_payments.deleted_at IS NULL` to ensure pending, submitted, rejected, or reversed payments never reduce outstanding debt.
     - Payment reversals automatically restore bill outstanding balance.
     - Handles multiple payments allocated to one bill and single payments disbursed across multiple bills without data duplication or join Cartesian products.
     - Filters: Vendor, Bill Date Range, Due Date Range, Payment Status (`unpaid`, `partially_paid`, `paid`, `overdue`), Overdue toggle (`due_date < CURDATE() AND outstanding_amount > 0.00`), Amount Range, and Search.
     - Summary Cards: Total Approved Bills, Total Paid Amount, Total Outstanding Balance, Unpaid Bills count, Partially Paid Bills count, and Overdue Bills count with overdue amount.

4. **Controller & Route Architecture (`ProcurementReportController`)**:
   - Implemented 12 endpoints under `procurement.reports.*`:
     - Requisitions: `requests`, `requests.details`, `requests.export`, `requests.print`
     - Orders: `orders`, `orders.details`, `orders.export`, `orders.print`
     - Outstanding Bills: `outstanding-bills`, `outstanding-bills.details`, `outstanding-bills.export`, `outstanding-bills.print`
   - Authorization: protected via `$request->user()->hasAnyPermission(...)` and Super Admin bypass.
   - Export: Streamed CSV with UTF-8 BOM (`\xEF\xBB\xBF`) ensuring Microsoft Excel compatibility with Bengali Unicode characters and currency symbols.
   - Print: Clean print-friendly views with media queries hiding UI buttons and optimizing table layouts.

5. **Blade Views & UI Standards**:
   - Follows TempleUI guidelines: `TempleUI.initDataTable` with server-side pagination, searching, sorting, and dynamic `drawCallback` summary synchronization.
   - Strictly NO serial `#` column (all report tables feature clean business entity identifiers with proper sortable attributes).
   - Bootstrap 5 Offcanvas drawers for instant read-only drilldown into line items, requester/approver metadata, vendor details, and full payment allocation histories with reversal notes.
   - Responsive filter cards with Select2 dropdowns, date pickers, amount ranges, and one-click reset buttons.
   - Sidebar navigation:
     - Parent menu **Procurement Reports / ক্রয় সংক্রান্ত প্রতিবেদন** with collapsible submenu and dynamic active-state detection that remains open when sublinks or drilldowns are visited.

6. **100% Localization Parity**:
   - Full bilingual parity across `lang/en/procurement.php` and `lang/bn/procurement.php` (90+ keys added for titles, subtitles, summary cards, filter options, table headers, and offcanvas details).
   - Menu translation standardized to "ক্রয় সংক্রান্ত প্রতিবেদন" in Bengali and "Procurement Reports" in English.

7. **Automated Feature Tests**:
   - `tests/Feature/ProcurementReportTest.php`: 9 comprehensive test cases (81 assertions) covering:
     - Access control (guests, unauthorized devotees, authorized treasurer, super admin).
     - Server-side DataTables AJAX payload and dynamic summary computation.
     - Requisitions report filtering, line items offcanvas, CSV export, and print view.
     - Purchase orders report segregation of active vs cancelled orders, offcanvas, and export.
     - Outstanding bills authoritative calculation, multi-allocation tracking, payment reversal restoration, overdue logic, offcanvas, and export.
   - `tests/Feature/SidebarMenuHierarchyTest.php`: 11 tests (81 assertions) verifying menu expansion, active highlighting, and Bengali localization.
   - Regression suites: `VendorBillTest` (23 passed), `VendorPaymentTest` (25 passed), `PurchaseOrderTest` (14 passed), `PurchaseRequestTest` (15 passed).
   - 100% PSR-12 code compliance verified via Laravel Pint.

18. **STOP Condition Applied**:
    - Admin Panel — Procurement Reports Module is 100% COMPLETE, fully integrated, verified, and tested against SQLite in-memory and MySQL schemas.
    - Awaiting explicit user feedback and approval.
