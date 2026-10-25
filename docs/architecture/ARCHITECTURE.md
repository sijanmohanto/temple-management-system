# Temple Management System — Long-Term Modular Architecture

## 1. Executive Overview

The Temple Management System is a comprehensive, modular enterprise application built for a Bengali Hindu temple and its surrounding community. It centralizes temple administration, religious festivals, devotee relationships, facility bookings, and transparent financial stewardship under an immutable, audited double-entry ledger.

---

## 2. Long-Term Modules & Data Ownership Boundaries

```
┌────────────────────────────────────────────────────────────────────────┐
│                        TEMPLE SYSTEM CORE                              │
├─────────────────────┬──────────────────────────┬───────────────────────┤
│ Auth & Access       │ Localization             │ Audit Trail           │
│ Users, Roles, Perms │ English & Bengali (bn)   │ Immutable Event Logs  │
└──────────┬──────────┴────────────┬─────────────┴───────────┬───────────┘
           │                       │                         │
┌──────────▼──────────┐ ┌──────────▼───────────┐ ┌───────────▼───────────┐
│ Module 1: People    │ │ Module 2: Events     │ │ Module 4: Central     │
│ & Relationships     │ │ & Religious Puja     │ │ Financial Ledger      │
│ - People Registry   │ │ - Puja Editions      │ │ - Double-Entry Ledger │
│ - Family Units      │ │ - Puja Schedules     │ │ - Accounts / Funds    │
│ - Committee Terms   │ │ - Seva Allocations   │ │ - Receipts & Vouchers │
│ - Monthly Dues Def  │ │ - Event Budgets      │ │ - Payment Allocation  │
└──────────┬──────────┘ └──────────┬───────────┘ └───────────▲───────────┘
           │                       │                         │
           │            ┌──────────▼───────────┐             │
           │            │ Module 3: Pledges    │             │
           │            │ & Collections        ├─────────────┤
           │            │ - Manat & Pledges    │             │
           │            │ - Partial Payments   │             │
           │            │ - Collection Desks   │             │
           │            └──────────────────────┘             │
           │                                                 │
┌──────────▼──────────┐ ┌──────────────────────┐             │
│ Module 7: Venues    │ │ Module 6: Procurement│             │
│ & Hall Rentals      │ │ & Inventory          │             │
│ - Hall Availability │ │ - Puja Ingredients   │             │
│ - Rental Bookings   │ │ - Assets & Facilities├─────────────┤
│ - Security Deposits │ │ - Vendor Payables    │             │
│ - Refunds           │ └──────────────────────┘             │
└──────────┬──────────┘                                      │
           │                                                 │
           └─────────────────────────────────────────────────┘
```

### Module 1: People, Family, Donor, and Committee Management
- **Data Owned**:
  - `people`: First name, last name, Bengali name, phone, email, date of birth, blood group, address, photo.
  - `families`: Family grouping, primary contact / head of household, household members.
  - `committee_terms`: Tenures (e.g. 2024-2026), executive designations (President, General Secretary, Treasurer, Executive Member).
  - `committee_monthly_dues`: Monthly pledge amounts committed by committee members.
- **Architectural Isolation Rule**:
  - **Person records are decoupled from User accounts.** A devotee or committee member exists in `people` even if they never log into the software. A `user_id` foreign key is optional on `people`. Roles and permissions apply to authenticated system users, never to person records directly.

### Module 2: Religious Events and Puja Editions
- **Data Owned**:
  - `puja_events`: Master religious ceremonies (Durga Puja, Kali Puja, Saraswati Puja, Janmashtami, Shivaratri, etc.).
  - `puja_editions`: Specific instances by Bengali/Gregorian calendar year (e.g., "Sharodiya Durga Puja 1433 / 2026").
  - `puja_schedules`: Detailed daily and prahar-wise tithi timings, pushpanjali, sandhya aarti, and bhog distribution times.
  - `puja_budgets`: Itemized expenditure limits and projected collection targets.

### Module 3: Event-Specific Pledges, Collections, Partial Payments & Receipts
- **Data Owned**:
  - `event_pledges`: Pledges made by devotees/families towards a specific puja edition (e.g., Prasad offering, Pratima sponsor, lighting).
  - `pledge_installments`: Partial payment tracking, recorded amounts, and remaining balances.
  - `official_receipts`: Serially numbered, printed receipts with unique verification codes.
- **Financial Boundary**:
  - When payment is confirmed, an immutable transaction voucher is routed to the **Central Financial Ledger (Module 4)**.

### Module 4: Centralized Financial Ledger & Treasury
- **Data Owned**:
  - `ledger_accounts`: Chart of accounts categorized by Assets, Liabilities, Equity/Funds, Revenues, and Expenses.
  - `funds`: Dedicated restricted/unrestricted reserves (e.g., General Temple Fund, Durga Puja Fund, Temple Construction Fund, Bhog Seva Fund).
  - `journal_entries`: Immutable double-entry records with balanced debits and credits.
  - `transactions`: Physical or electronic money movements (Cash in Hand, Bank Accounts, Mobile Financial Services like bKash/Nagad).
  - `vouchers`: Cash Receipt (CR), Cash Payment (CP), Bank Receipt (BR), Bank Payment (BP), and Journal Voucher (JV).
  - `fiscal_years` & `annual_closings`: Historical closings with audited balance carryover.
- **Core Rules**:
  - **Double-Entry Balance Enforced**: `sum(debits) === sum(credits)` within database transactions.
  - **Explicit Payment Allocation**: Pledges and monthly dues map to distinct voucher line items. A transaction cannot be double-counted.
  - **Immutability**: Confirmed posted vouchers cannot be altered. Corrections must be handled via authorized reversal entries (`reversal_of_id`).

### Module 5: Committee Contributions vs. Event Donations
- **Data Owned**:
  - `committee_contributions`: Monthly fixed recurring contributions from executive members.
- **Boundary**:
  - Kept completely separate from festival/event donations. Committee contributions fund regular recurring operations (priest salaries, electricity, daily prasad), while event pledges fund festival-specific budgets.

### Module 6: Procurement, Inventory, Assets & Vendor Management
- **Data Owned**:
  - `vendors`: Supplier directory (sound systems, tent/pandal makers, florists, sweetmakers, decorators).
  - `inventory_items`: Puja goods (ghee, camphor, incenses, rice, fabric, brass idols, utensils).
  - `assets`: Fixed temple real estate, silver ornaments, brass lamps, sound systems, maintenance logs.

### Module 7: Venue & Hall Bookings
- **Data Owned**:
  - `venues`: Community halls, prayer rooms, dining sheds, meeting rooms.
  - `venue_bookings`: Reservations (weddings, shraddha ceremonies, annaprashan, satsang), booking dates, security deposit status.
  - `security_deposits` & `refunds`: Refund tracking with inspection sign-off.

### Module 8: Daily Temple Operations, Seva & Volunteers
- **Data Owned**:
  - `daily_puja_logs`: Completion confirmation of daily nitya puja and bhog offerings.
  - `volunteers`: Registered seva volunteers, contact details, skill categories.
  - `volunteer_duty_shifts`: Shift assignments during major festivals and daily temple operations.

### Module 9: Public Read-Only Portal
- **Data Owned**:
  - Public announcements, daily puja timings, executive committee directory, permitted donor recognition lists (respecting privacy preferences), and approved quarterly/annual financial summary reports.

### Module 10: Governance, Security & Audit
- **Data Owned**:
  - `users`, `roles`, `permissions`, `audit_logs`, `system_settings`.
  - Comprehensive, tamper-evident audit logs capturing who performed what action, timestamp, affected model, and IP address.

---

## 3. Financial Integrity & Zero-Duplication Architecture

1. **Isolation of Intent**: A payment received is assigned a singular `voucher_id`.
2. **Explicit Allocation Table**:
   - `voucher_allocations`:
     - `voucher_id` (foreign key)
     - `allocatable_type` (e.g., `EventPledge`, `CommitteeContribution`, `VenueBooking`)
     - `allocatable_id` (primary key of the respective entity)
     - `allocated_amount` (decimal: 14, 2)
3. **Database Integrity**:
   - High-precision monetary types (`DECIMAL(14, 2)` or integer cents).
   - Foreign keys with `ON DELETE RESTRICT` on all posted financial items.
   - Database transactions wrap all voucher creations and ledger postings.

---

## 4. Localization Strategy (English & Bengali)

- **Default Locale**: `en` (English)
- **Secondary Locale**: `bn` (বাংলা)
- **Execution Rules**:
  - User-facing text resides in `lang/en/` and `lang/bn/`.
  - Stored in files by domain: `common.php`, `nav.php`, `auth.php`, `roles.php`, `dashboard.php`.
  - Session-level tracking via `SetLocale` middleware.
  - Authenticated user preferences persist across devices via `users.preferred_locale`.
  - Database multilingual support: Dual fields (`name_en`, `name_bn`, `description_en`, `description_bn`) for master records like roles, events, and venues.
