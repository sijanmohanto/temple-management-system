# Temple Management System — Phase 2: People & Committee Management

A modular, secure enterprise web application designed for a Bengali Hindu temple and its local community. Built with **Laravel 11**, **MySQL**, **Blade**, **Bootstrap 5**, and **jQuery**, featuring native English and Bengali (`bn`) localization, centralized devotee registry, family management, committee governance, and server-enforced role-based access control.

---

## Technology Stack

- **Backend**: Laravel 11.57+ / PHP 8.3.14
- **Database**: MySQL 9.1.0 (utf8mb4 encoding)
- **Frontend**: Blade Templates, Bootstrap 5.3.3, jQuery 3.7.1, Bootstrap Icons
- **Authentication**: Laravel Session-Based Authentication & Permissions
- **Localization**: English (`en`) Default & Bengali (`bn`) Secondary
- **Version Control**: Git

---

## Current Status & Implemented Modules

### 1. Phase 1: Foundation (Completed & Verified)
- Modular architectural separation (`app/Services`, `app/Models`, `app/Http/Controllers`, `app/Http/Requests`, `app/Policies`).
- Roles & Granular Permissions (`manage_users`, `manage_roles`, `view_audit_logs`, `view_people`, `manage_people`, `manage_committee`, etc.).
- Offline Bootstrap 5.3.3, jQuery 3.7.1, and Bootstrap Icons.
- Dual-language engine (English default, Bengali secondary).
- Audit trails tracking critical actions.

### 2. Phase 2: People, Families & Committees (Completed & Verified)
- **Centralized Devotee Registry (`people`)**:
  - Decoupled from system login accounts. Devotees are people, not necessarily system users.
  - Auto-generated unique devotee codes (`DEV-XXXXX`).
  - Demographic, lineage (gotra, parents, spouse), contact, address, occupation, public remarks, and confidential private notes.
  - Safe active/inactive toggle (records are never hard-deleted if referenced).
  - Search by code, name, phone, gotra; filter by classification, family, and status with pagination.
- **Duplicate Profile Detection**:
  - Proactive duplicate detection checking exact phone, email, and name matches (with DOB or parent name).
  - Surface duplicate warnings with reasons without auto-merging records.
  - Client-side real-time check and server-side override confirmation.
- **Family Unit Management (`families`)**:
  - Unique family codes (`FAM-XXXX`).
  - Assign existing devotees to families with family relationships (head, spouse, son, daughter, etc.).
  - Designated primary contact person.
  - Protected from unsafe deletion if active members exist.
- **Committee & Term Governance (`committees` & `committee_memberships`)**:
  - Configurable committee codes, bilingual titles, and term start/end dates.
  - Assign existing devotees without creating duplicate person records.
  - Multi-term support preserving historical records when memberships conclude or expire.
- **Simultaneous Person Classifications (`classifications`)**:
  - Resident, Donor, Committee Member, and Volunteer.
  - Single devotee can hold multiple classifications concurrently without duplicate assignments.

### 3. Phase 2.1: UI Standardization (Completed & Verified)
- **DataTables**:
  - jQuery DataTables with Bootstrap 5 theme and responsive plugins bundled offline.
  - Server-side processing for high-volume People and Family listings with `deferLoading` for instant initial rendering.
  - Client-side DataTables with localized empty/pagination controls on Committee memberships, Family member listings, and Audit logs.
  - Strict server-enforced authorization and query eager-loading (`with(['family', 'classifications'])`).
- **Global Toastr Notifications**:
  - Centralized in `temple-ui.js` and `window.TempleConfig`.
  - Replaced duplicate Bootstrap alert banners with elegant Toastr notifications for all session flashes and AJAX actions.
  - Field-level form validation styling (`is-invalid` / `invalid-feedback`) preserved for accessibility without alert banner duplication.
- **Button Loading Indicators**:
  - Global form submit listener adding spinner and localized loading label (`__('common.loading')`).
  - Auto-disables action buttons to prevent accidental double submissions.
  - Automatically resets button state on AJAX errors.
- **Select2 Dropdowns**:
  - Searchable dropdowns with Bootstrap 5 styling across People, Family, and Committee forms and filters.
  - Modal-aware dropdown parenting (`dropdownParent`).
  - Dedicated search endpoints (`/people/search`, `/families/search`) with server authorization.
- **Dual Localization**:
  - Full English and Bengali parity in `lang/en/datatables.php`, `lang/bn/datatables.php`, `lang/en/select2.php`, and `lang/bn/select2.php`.

---

## Directory & Setup

The project is hosted in WAMP64:
- **Project Directory**: `E:\wamp64\www\temple-management-system`
- **Convenience Junction**: `E:\wamp64\temple-management-system` (points directly to `www\temple-management-system`)

### 1. Database Migration & Seeding

Run migrations (safe, non-destructive):
```bash
php artisan migrate
```

Seed initial foundation and Phase 2 demo data:
```bash
php artisan db:seed
```

### 2. Running Automated Tests

Run the full automated test suite (67 tests, 232 assertions, executed with in-memory SQLite):
```bash
php artisan test
```

Run code style & formatting checks:
```bash
php ./vendor/bin/pint --test
```

---

## Demo Accounts for Testing

| Role | Email | Password | Primary Duties |
|---|---|---|---|
| **Super Admin** | `admin@temple.local` | `Admin@123456` | Complete system governance, audit trail inspection, people & committee management |
| **Temple Admin** | `manager@temple.local` | `Manager@123456` | Member registration, family assignments, committee setups, daily temple seva |
| **Treasurer** | `treasurer@temple.local` | `Treasurer@123456` | Central financial ledger, collection verification, expense vouchers, member view |
| **Committee Member**| `member@temple.local`| `Member@123456` | Committee meetings, directory view, report reviews |
| **Volunteer** | `volunteer@temple.local` | `Volunteer@123456` | Temple duty shifts, prasad seva, collection desk aid |
| **Devotee** | `devotee@temple.local` | `Devotee@123456` | Devotee self-service portal, donation receipts |

---

## Git Workflow

1. Always branch off `main` for feature work (`feature/module-name`).
2. Run `php artisan test` and `php ./vendor/bin/pint --test` before committing.
3. Commit message convention:
   - `feat: add committee contribution service`
   - `fix: resolve ledger debit/credit balance check`
   - `docs: update phase progress tracking`
4. Never commit `.env` credentials, tokens, or personal secrets.
