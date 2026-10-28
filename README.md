# Temple Management System — Phase 4: Donation, Pledge & Collection Management

A modular, secure enterprise web application designed for a Bengali Hindu temple and its local community. Built with **Laravel 11**, **MySQL**, **Blade**, **Bootstrap 5**, and **jQuery**, featuring native English and Bengali (`bn`) localization, centralized devotee registry, family management, committee governance, complete puja & event lifecycle management, donation & pledge tracking, official sequential receipts, and server-enforced role-based access control.

---

## Technology Stack

- **Backend**: Laravel 11.57+ / PHP 8.3.14
- **Database**: MySQL 9.1.0 (utf8mb4 encoding)
- **Frontend**: Blade Templates, Bootstrap 5.3.3, jQuery 3.7.1, Bootstrap Icons, DataTables, Select2, Toastr
- **Authentication**: Laravel Session-Based Authentication & Permissions
- **Localization**: English (`en`) Default & Bengali (`bn`) Secondary
- **Version Control**: Git

---

## Current Status & Implemented Modules

### 1. Phase 1: Foundation (Completed & Verified)
- Modular architectural separation (`app/Services`, `app/Models`, `app/Http/Controllers`, `app/Http/Requests`, `app/Policies`).
- Roles & Granular Permissions (`manage_users`, `manage_roles`, `view_audit_logs`, `view_people`, `manage_people`, `manage_committee`, `view_events`, `manage_events`, etc.).
- Offline Bootstrap 5.3.3, jQuery 3.7.1, and Bootstrap Icons.
- Dual-language engine (English default, Bengali secondary).
- Centralized audit trails tracking critical actions.

### 2. Phase 2: People, Families & Committees (Completed & Verified)
- **Centralized Devotee Registry (`people`)**:
  - Decoupled from system login accounts. Devotees are people, not necessarily system users.
  - Auto-generated unique devotee codes (`DEV-XXXXX`).
  - Lineage, demographics, contact, address, occupation, remarks, and confidential private notes.
  - Safe active/inactive toggle (records are never hard-deleted if referenced).
- **Duplicate Profile Detection**:
  - Real-time detection on phone, email, and name matches (with DOB or parent name) without auto-merging records.
- **Family Unit Management (`families`)**:
  - Unique family codes (`FAM-XXXX`), relationships, primary contact person, safe deletion protection.
- **Committee & Term Governance (`committees` & `committee_memberships`)**:
  - Configurable committee codes, bilingual titles, multi-term support preserving historical records.
- **Simultaneous Person Classifications (`classifications`)**:
  - Resident, Donor, Committee Member, and Volunteer held simultaneously with unique constraints.

### 3. Phase 2.1: UI Standardization (Completed & Verified)
- **DataTables**:
  - Offline jQuery DataTables with Bootstrap 5 theme and responsive plugins.
  - Server-side processing for high-volume People, Family, Event, Payment, Pledge, and Receipt listings with `deferLoading`.
- **Global Toastr Notifications**:
  - Centralized in `temple-ui.js` and `window.TempleConfig`.
  - Replaced duplicate Bootstrap alert banners with elegant Toastr notifications for all session flashes and AJAX actions.
- **Button Loading Indicators**:
  - Global form submit listener adding spinner and localized loading label (`__('common.loading')`).
  - Auto-disables action buttons to prevent accidental double submissions.
- **Select2 Dropdowns**:
  - Searchable dropdowns with Bootstrap 5 styling across forms and filters.

### 4. Phase 3: Complete Puja & Event Management (Completed & Verified)
- **Configurable Event Categories & Types (`event_categories`)**:
  - Supports arbitrary religious ceremonies (Durga Puja, Lakshmi Puja, Kali Puja, Saraswati Puja, Shivaratri) and cultural community events.
  - Safe deletion checks preventing deletion of categories referenced by events.
- **Multi-Event & Multi-Year Lifecycle Management (`events`)**:
  - Unique sequential event codes per year (`EVT-YYYY-0001`) with race-condition prevention (`lockForUpdate`).
  - Full support for multiple events operating concurrently.
  - Independent event years, event dates, and collection window dates (e.g., event in Oct 2027 with collections beginning Dec 2026).
- **Lifecycle Transition State Machine**:
  - Strict states: `draft` -> `upcoming` -> `active` -> `closing` -> `closed`.
  - Atomic transitions enforced via `EventLifecycleService`.
  - Direct transition to `closed` is blocked; formal closing workflow is required.
- **Two Distinct Closing Modes**:
  - **Mode A (`close_and_lock`)**: Complete immutability; blocks standard and post-closing edits; requires privileged reopening for changes.
  - **Mode B (`close_with_authorized_adjustments`)**: Marks event closed; blocks ordinary CRUD; permits authorized post-closing metadata adjustments (venue and descriptions) with mandatory justification and diff logging.
- **Privileged Event Reopening Workflow**:
  - Permission-controlled reopening (`reopen_events`) requiring mandatory justification and target status (`active` or `upcoming`).
  - Historical closing details (previous mode, reason, timestamp, closer) are permanently preserved in `event_lifecycle_logs`.
- **Event Lifecycle Audit History (`event_lifecycle_logs`)**:
  - Tracks all state transitions, closures, reopenings, and post-closing diffs.

### 5. Phase 4: Donation, Pledge & Collection Management (Completed & Verified)
- **Event-Specific Pledges & Manat Tracking (`pledges`)**:
  - Single owner rule: individual devotee (`person_id`) OR family unit (`family_id`).
  - Unique sequential pledge codes (`PLG-YYYY-0001`).
  - Support for concurrent event pledges for the same devotee without collision.
  - Status lifecycle: `unpaid`, `partial`, `paid`, and `cancelled` (with mandatory audit reason).
- **Voluntary Donations Without Mandatory Pledge**:
  - Voluntary event donations and general temple donations recorded without fictitious pledges or outstanding balances.
- **Submission & Verification Workflow with Strict Separation of Duties**:
  - Payments submitted in `pending_verification` status; unverified collections never affect outstanding balances or count toward verified totals.
  - Self-verification prohibition: submitters (`submitted_by`) are strictly blocked from verifying their own submissions unless `super_admin`.
  - Authorized verifiers review and atomically approve submissions, issuing official money receipts.
  - Rejection workflow with mandatory `rejection_reason` and audit logging.
- **Official Sequential Receipts (`receipts`)**:
  - Unique sequential receipt numbers (`RCP-YYYY-0001`) issued exclusively upon verification.
  - Print-ready receipt document with `@media print` layout, dual English/Bengali header, amounts in numbers and words, and authorized signatory block.
  - Reversal workflow (`reverse_collections`): payment marked `reversed`, payment allocations marked `is_active = false`, obligation balances recalculated, and receipt marked `status = voided` with `voided_at`, `voided_by`, and `void_reason`. No financial records are deleted.
- **Payment Allocations & Integrity Rules (`payment_allocations`)**:
  - Foreign key `collection_payment_id` uses `restrictOnDelete()` to prevent silent cascading deletes.
  - Strict allocation sum constraint: sum of active allocations must match the payment amount.
  - Allocations cannot exceed outstanding balance of pledges or monthly committee dues, protected by row locking (`lockForUpdate`).
- **Monthly Committee Contributions (`monthly_committee_contributions`)**:
  - Non-refundable temple fund income (not liabilities, savings, or DPS).
  - Unique composite constraint: `(committee_membership_id, contribution_month)`.
  - Concurrency-safe code format: `MCC-YYYYMM-0001`.
  - Bulk generation for active committee members skipping existing records.
  - Historical contributions preserved after membership concludes.
  - Waiver workflow with mandatory reason.
- **Reports & UI Standardization**:
  - Event-wise collection and pledge summary report (`/collections-reports/event-summary`).
  - Outstanding pledges ledger report (`/collections-reports/outstanding-pledges`).
  - 12-month committee contributions ledger report (`/collections-reports/committee-contributions`).
  - 100% parity across `lang/en/donations.php` and `lang/bn/donations.php`.

### 6. Phase 5: Finance & Accounting Management (Completed & Verified)
- **Centralized Double-Entry Ledger (`financial_accounts`, `journal_entries`, `journal_lines`)**:
  - Structured 5-type Chart of Accounts (`asset`, `liability`, `equity`, `income`, `expense`).
  - Strict mathematical invariant: $\sum \text{Debit} = \sum \text{Credit}$ enforced on every entry. Unbalanced vouchers are rejected by the engine.
  - Posted journal vouchers are immutable. Concurrency-safe numbering format: `JRN-YYYY-0001`.
  - Idempotent posting boundary via unique index on `['source_type', 'source_id']`.
- **Automated Collection Posting via `FinancePostingService`**:
  - Atomically executed upon payment verification by an independent verifier.
  - Credits corresponding revenue accounts (`4010` General Income, `4020` Event Income, `4030` Committee Income) and debits asset accounts (`1010` Cash in Hand, `1020` Bank Account, `1030` Mobile Financial Services).
  - Excess donation allocations posted cleanly without fictitious pledges or balances.
  - Payment reversal generates an inverse balanced journal voucher (`JRN-YYYY-0002`), preserving historical trail without record deletion.
- **Expense Voucher Management (`expenses`)**:
  - Complete requisition workflow: `pending_approval` -> `approved` / `rejected` / `reversed`.
  - Requisition submission cannot be self-approved (separation of duties).
  - Approved expenses automatically post double-entry vouchers: debits designated expense category account (`5010`-`5080`) and credits source asset account (`1010`-`1030`).
  - Expense reversal creates an inverse balanced journal voucher.
- **Fund Transfers Between Asset Accounts (`fund_transfers`)**:
  - Direct balance transfers between Cash, Bank, and MFS accounts without touching income or expense accounts.
  - Supports optional transfer fees/bank charges posted directly to Bank Charges expense account (`5070`).
  - Strict validation preventing transfers between identical accounts or non-asset accounts.
- **Miscellaneous Manual Income (`manual_incomes`)**:
  - Direct recording of miscellaneous temple income (premises rental, scrap sales, interest) debiting deposit asset accounts and crediting miscellaneous income (`4040`).
- **Controlled Opening Balance Setup**:
  - Controlled entry of initial asset, liability, and equity account balances balancing strictly against `Opening Balance Equity` (`3020`).
  - Duplicate opening balance prevention per account.
  - Excluded from periodic income & expense calculations to prevent distortion.
- **Executive Financial Dashboard**:
  - Real-time balances of liquid assets (Cash, Bank, MFS).
  - Monthly income vs. expense comparison, net cash flow, pending approvals, and unposted collections tracker.
- **7 Core Financial Reports & Statements**:
  1. **Trial Balance**: Debit and credit reconciliation with balanced status indicator.
  2. **General Ledger**: Account-wise chronological transactions with running balance.
  3. **Income & Expense Statement**: Operational revenue vs. operational expenditure and net surplus/deficit.
  4. **Cash Book**: Daily cash transactions register.
  5. **Bank & MFS Account Statement**: Filtered account register.
  6. **Account Balances Summary**: High-level balance overview across all accounts.
  7. **Event-wise Financial Summary**: Puja/event-specific revenue, direct expense, and net surplus calculation.
- **Granular RBAC & 100% Bilingual Localization**:
  - 16 granular permissions guarding every financial action.
  - 100% parity across `lang/en/finance.php` and `lang/bn/finance.php`.

---

## Directory & Setup

The project is hosted in WAMP64:
- **Physical Web Root**: `E:\wamp64\www\temple-management-system`
- **Convenience Junction**: `E:\wamp64\temple-management-system` (points directly to `www\temple-management-system`)

### 1. Database Migration & Seeding

Run migrations (safe, non-destructive):
```bash
php artisan migrate
```

Seed initial foundation, event categories, and permissions:
```bash
php artisan db:seed
```

### 2. Running Automated Tests

Run the full automated test suite (119 tests, 658 assertions, executed with in-memory SQLite):
```bash
php artisan test
```

Run code style & formatting checks:
```bash
php ./vendor/bin/pint --test
```
