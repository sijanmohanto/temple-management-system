# Temple Management System — Phase 3: Complete Puja & Event Management

A modular, secure enterprise web application designed for a Bengali Hindu temple and its local community. Built with **Laravel 11**, **MySQL**, **Blade**, **Bootstrap 5**, and **jQuery**, featuring native English and Bengali (`bn`) localization, centralized devotee registry, family management, committee governance, complete puja & event lifecycle management, and server-enforced role-based access control.

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
  - Server-side processing for high-volume People, Family, and Event listings with `deferLoading`.
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
- **Bilingual Localization**:
  - 100% parity across `lang/en/events.php` and `lang/bn/events.php`.

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

Run the full automated test suite (80 tests, 321 assertions, executed with in-memory SQLite):
```bash
php artisan test
```

Run code style & formatting checks:
```bash
php ./vendor/bin/pint --test
```
