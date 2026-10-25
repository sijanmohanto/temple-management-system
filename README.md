# Temple Management System — Phase 1: Project Foundation

A modular, secure web application designed for a Bengali Hindu temple and its local community. Built with **Laravel 11**, **MySQL**, **Blade**, **Bootstrap 5**, and **jQuery**, featuring native English and Bengali (`bn`) localization and server-enforced role-based access control.

---

## Technology Stack

- **Backend**: Laravel 11.57+ / PHP 8.3.14
- **Database**: MySQL 9.1.0 (utf8mb4 encoding)
- **Frontend**: Blade Templates, Bootstrap 5.3.3, jQuery 3.7.1, Bootstrap Icons
- **Authentication**: Laravel Session-Based Authentication
- **Localization**: English (`en`) Default & Bengali (`bn`) Secondary
- **Version Control**: Git

---

## Directory & Setup

The project is hosted in WAMP64:
- **Project Directory**: `E:\wamp64\www\temple-management-system`
- **Convenience Junction**: `E:\wamp64\temple-management-system` (points directly to `www\temple-management-system`)

### 1. Environment Configuration

Copy `.env.example` to `.env` (already preconfigured for local WAMP):
```env
APP_NAME="Temple Management System"
APP_ENV=local
APP_TIMEZONE=Asia/Dhaka
APP_URL=http://localhost/temple-management-system/public

APP_LOCALE=en
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=temple_management_system
DB_USERNAME=root
DB_PASSWORD=
```

### 2. Database Migration & Seeding

Ensure MySQL is running on port 3306, then execute:
```bash
php artisan migrate:fresh --seed
```

This sets up:
- Standard users & sessions tables
- Roles, permissions, and audit logs
- Core roles (`super_admin`, `temple_admin`, `treasurer`, `committee_member`, `volunteer`, `devotee`)
- Initial seed accounts for verification

### 3. Running Automated Tests

Run the full automated test suite (executed with in-memory SQLite):
```bash
php artisan test
```

All 22 unit & feature tests pass without warnings or errors.

---

## Demo Accounts for Phase 1 Testing

For convenience, the login page features quick-autofill buttons for testing each user role:

| Role | Email | Password | Primary Duties |
|---|---|---|---|
| **Super Admin** | `admin@temple.local` | `Admin@123456` | Complete system governance, audit trail inspection |
| **Temple Admin** | `manager@temple.local` | `Manager@123456` | Daily temple seva, puja coordination, volunteer duties |
| **Treasurer** | `treasurer@temple.local` | `Treasurer@123456` | Central financial ledger, collection verification, expense vouchers |
| **Committee Member**| `member@temple.local`| `Member@123456` | Committee meetings, monthly dues, financial review |
| **Volunteer** | `volunteer@temple.local` | `Volunteer@123456` | Temple duty shifts, prasad seva, collection desk aid |
| **Devotee** | `devotee@temple.local` | `Devotee@123456` | Devotee self-service portal, donation receipts |

---

## Phase 1 Deliverables Summary

- [x] Clean Laravel 11 modular architecture
- [x] MySQL database and connection verified (`temple_management_system`)
- [x] Bootstrap 5.3.3 and jQuery 3.7.1 bundled locally (offline-ready)
- [x] Complete English (`en`) and Bengali (`bn`) localization with instant language switcher
- [x] Session-based authentication with audit logging and rate limiting
- [x] Server-enforced role and permission middleware (`role:super_admin`, `role:super_admin,treasurer`)
- [x] Dignified temple UI theme (deep maroon, saffron gold, responsive drawer navigation)
- [x] Profile management (name, email, phone, preferred language, password updates)
- [x] Comprehensive long-term architecture design (`docs/architecture/ARCHITECTURE.md`)
- [x] 22 Automated feature and unit tests

---

## Git Workflow

1. Always branch off `main` for feature work (`feature/module-name`).
2. Run `php artisan test` before committing.
3. Commit message convention:
   - `feat: add committee contribution service`
   - `fix: resolve ledger debit/credit balance check`
   - `docs: update phase progress tracking`
4. Never commit `.env` credentials, tokens, or personal secrets.
