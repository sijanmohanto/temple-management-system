# AGENTS.md — Engineering & Agent Operational Guidelines

## 1. Project Purpose & Stance
You are working on the **Temple Management System**, a modular enterprise system for a Bengali Hindu temple and its local community.
Always maintain high standards of code hygiene, server-enforced security, architectural separation, and dignified, respectful cultural context.

## 2. Confirmed Technology Stack
- **Framework**: Laravel 11.x on PHP 8.2+ (Targeting PHP 8.3)
- **Database**: MySQL 9.x (WAMP default: `temple_management_system` on port 3306)
- **Frontend**: Blade templates, HTML5, CSS3, Bootstrap 5.3+, jQuery 3.7+, standard JavaScript
- **Icons**: Bootstrap Icons (offline bundle available in `public/assets/vendor/`)
- **Authentication**: Laravel session-based authentication
- **Localization**: English (`en`) default, Bengali (`bn`) secondary
- **Version Control**: Git & GitHub

> **STRICT BAN**: Do NOT introduce React, Vue, Next.js, Inertia, SPA frontends, microservices, or complex build requirements. The application must run cleanly via PHP artisan and standard Apache/WAMP web serving.

## 3. Directory Layout & Environments
- **Physical Web Root**: `E:\wamp64\www\temple-management-system`
- **WAMP Directory Junction**: `E:\wamp64\temple-management-system` (linked to `www\temple-management-system`)
- **Local Development URL**: `http://localhost/temple-management-system/public` or `php artisan serve`

## 4. Fundamental Architectural Rules
1. **Person vs. User Decoupling**:
   - Devotees, donors, volunteers, and committee members are **people**, not necessarily system users.
   - Do NOT force every person in the directory to have a `users` table record.
   - `users` is solely for system authentication, security roles, and permissions.
2. **Centralized Financial Ledger**:
   - Every financial transaction must be recorded through balanced double-entry accounting.
   - Never count a transaction twice.
   - Separate monthly committee dues from event/puja-specific donations.
   - Posted financial records are **immutable**. Corrections require authorized reversal entries.
3. **Server-Side Authorization**:
   - Never rely solely on hiding buttons in Blade templates.
   - Protect all mutating endpoints with Form Requests, Middleware (`role:name`, `permission:slug`), and Policies.
4. **Localization Enforcement**:
   - Never hardcode user-facing strings in Blade views, controllers, or notifications.
   - Maintain parity between `lang/en/` and `lang/bn/`.
   - Use standard Laravel helper `__('common.key')` or `trans(...)`.
5. **Database Integrity**:
   - Use decimal types (`DECIMAL(14, 2)`) for money.
   - Enforce database foreign keys, cascading or restricted deletes appropriately.
   - Wrap multi-table operations in database transactions (`DB::transaction(...)`).

## 5. Coding & Workflow Standards
- Run tests (`php artisan test`) after any architectural change.
- All unit and feature tests must run cleanly against SQLite in-memory (`phpunit.xml`) to keep CI/test cycles fast, while migrations must remain 100% MySQL-compatible.
- Document any schema changes and maintain `PROJECT_PROGRESS.md`.
- Never start subsequent phases without explicit user approval.
- Never commit credentials or expose secrets in documentation or Git.
