# Increment 7 — Previous Event Accounts & Public Financial Reports Guide

**Project:** Temple Management System  
**Stack:** Laravel 11, Blade, Bootstrap 5, jQuery, DataTables, MySQL  
**Scope:** Increment 7 — Historical Event Financial Transparency & Audited Reports  
**Verification Status:** 100% Tested & Verified (22 tests, 119 assertions in `PreviousEventArchiveTest`, 264 total Public Portal tests passing)

---

## 1. Overview & Business Purpose

The **Previous Event Accounts & Public Financial Reports** module (Increment 7) delivers a unified, audit-verified public transparency hub for past festivals, pujas, and religious events.

In traditional community and temple settings, past event accounts are often dispersed across paper ledgers, scanned PDFs, or unpublished committee records. Increment 7 establishes complete public accountability through a **Dual-Source Financial Architecture**:

1. **System-Generated Authoritative Financial Summary**:
   - Calculated dynamically from balanced double-entry General Ledger vouchers (`FinancialReportService` & `JournalLine`).
   - Displays real-time Total Verified Income, Total Approved Expenses, Net Surplus/Deficit, and Outstanding Pledges.
   - Categorized income and expense breakdowns with percentage distribution.
   - Formal Event Settlement tracking (surplus fund transfers to the General Temple Fund).
   - If historical ledger records were not recorded in the software, displays an informative state rather than misleading zeros, directing devotees to official uploaded statements.

2. **Official Audited Statements & Historical Documents**:
   - Published financial reports, statutory audit statements, expense breakdown spreadsheets, and settlement records uploaded by authorized temple administrators.
   - Secure private storage on the `local` disk (`private/event_documents/`).
   - Opaque UUID-based public URLs preventing sequential ID harvesting (IDOR).
   - In-browser PDF & image preview modal alongside safe attachment downloads for Excel/CSV/PDF files.

---

## 2. Database Schema & Architecture

### Migration: `2026_11_17_234443_add_report_category_and_uuid_to_event_documents_table`

The existing `event_documents` table was extended with three essential attributes:

| Column | Type | Index | Description |
|---|---|---|---|
| `uuid` | `CHAR(36)` | Unique | Opaque, cryptographically secure identifier used in all public URLs to prevent database ID exposure and enumeration attacks. Auto-generated on creation via model hook. |
| `report_category` | `VARCHAR(50)` | Indexed | Classification of financial reports (`complete_financial_statement`, `income_report`, `expense_report`, `expense_breakdown`, `event_settlement`, `other`). Defaults to `complete_financial_statement`. |
| `financial_year` | `INT UNSIGNED` | Indexed | Liturgical or financial year associated with the report. Auto-defaults to parent event's `event_year`. |

### Model: `App\Models\EventDocument`

- **Enums / Constants**:
  - `CATEGORY_COMPLETE_FINANCIAL_STATEMENT = 'complete_financial_statement'`
  - `CATEGORY_INCOME_REPORT = 'income_report'`
  - `CATEGORY_EXPENSE_REPORT = 'expense_report'`
  - `CATEGORY_EXPENSE_BREAKDOWN = 'expense_breakdown'`
  - `CATEGORY_EVENT_SETTLEMENT = 'event_settlement'`
  - `CATEGORY_OTHER = 'other'`
- **Automatic UUID Generation**:
  ```php
  protected static function booted(): void
  {
      static::creating(function (EventDocument $document) {
          if (empty($document->uuid)) {
              $document->uuid = (string) Str::uuid();
          }
          if (empty($document->financial_year) && $document->event_id) {
              $document->financial_year = $document->event?->event_year;
          }
      });
  }
  ```
- **Helper Scopes**:
  - `scopeOfCategory($query, $category)`: Filters by report category.
  - `scopeForYear($query, $year)`: Filters by financial year.
  - `scopePublished($query)`: Filters by `publication_status === 'published'`.

---

## 3. Admin Document Management Interface

Administrators with `manage_public_portal` or `manage_event_documents` permissions can manage financial documents at `/admin/portal/documents`:

- **Dual Upload Mode & Historical Event Creation**:
  - **Mode 1 (Select Existing Event)**: Select an existing closed, closing, or active event via Select2 dropdown.
  - **Mode 2 (Add New Historical Event)**: When uploading reports for historical events not present in the database, admin selects the "Add New Historical Event" radio toggle. Entering the event name, category, liturgical year, venue, and description creates the historical event record atomically (`status: closed`, `publication_status: published`, auto-generated sequential code `EVT-HIST-{year}-{random}`) with zero financial transactions or ledger entries required. The uploaded document serves as the official final closing authority.
- **Upload & Classification**:
  - Choose Document Type (`financial_report`, `audit_report`, `supporting_document`, `other`).
  - Choose Report Category (`complete_financial_statement`, `income_report`, `expense_report`, `expense_breakdown`, `event_settlement`, `other`).
  - Set Financial Year (defaults to event year).
  - Allowed file formats: PDF (`.pdf`), Images (`.jpg, .jpeg, .png, .webp`), Excel/CSV spreadsheets (`.xlsx, .xls, .csv`) up to 10MB.
- **Publication Lifecycle**:
  - Explicit publication toggle (`published` vs `unpublished`/`draft`). Only published documents appear in public archive views. Unpublishing immediately denies both public listing and direct preview/download access with HTTP 404.
- **Document Edit & Safe File Replacement**:
  - Metadata can be edited without re-uploading the file.
  - Safe file replacement: stores the new file on disk before deleting the old file, preventing data loss.
- **Server-Side DataTables**:
  - Filters by Event, Document Type, Publication Status, and Report Category.
  - File extension icon rendering (PDF, Excel, Image, Generic).

---

## 4. Public Previous Event Archive (`/previous-events`)

### Route: `GET /previous-events` (`public.previous-events.index`)

- **Accessibility**: Open to guests and public devotees without authentication.
- **Filter Bar**:
  1. **Keyword Search**: Matches event English/Bengali names and event code (`EVT-YYYY-XXXX`).
  2. **Category Filter**: Filter by liturgical category (Puja, Cultural, Religious, etc.).
  3. **Financial Year**: Filter across distinct event years.
  4. **Report Category**: Filter events that possess published reports in a specific category.
- **Event Cards**:
  - High-resolution banner image (or themed Bengali temple emblem fallback).
  - Event liturgical status badge (`closed`, `closing`, `active`).
  - Event fund settlement status badge (`Fund Settled`).
  - Published reports counter badge (`X Official Reports`).
  - Event dates, venue, and bilingual short description.
  - Direct CTA button: *“View Financial Reports”* (`route('public.previous-events.show', $event->slug)`).
- **Navigation Integration**:
  - Top navigation bar: Active link *“Previous Events & Financial Reports”* (`lang/en` & `lang/bn`).
  - Footer quick links: Added direct navigation link.
  - Legacy redirect: `/event-accounts` permanently redirects to `/previous-events`.

---

## 5. Public Financial Report Details (`/previous-events/{slug}`)

### Route: `GET /previous-events/{slug}` (`public.previous-events.show`)

Dual-tab responsive interface presenting complete financial transparency, with the official uploaded reports taking precedence as the final authority:

### Tab 1: Official Closing Reports & Audited Statements (Default Active Tab)
- **Authority**: The uploaded closing document is the official final closing statement of that event. It does not require software transactions or matching ledger balances.
- **Embedded In-Page PDF Viewer**: Primary PDF reports (such as Complete Financial Statements) are embedded directly in an interactive responsive `<iframe>` viewer, allowing visitors to inspect audited reports without leaving the page.
- **Document List & Direct Downloads**:
  - Format badges and icons (PDF, XLSX, XLS, CSV, Image).
  - Report Category & Financial Year pills.
  - Direct secure attachment download buttons for spreadsheets and audited packets.
  - In-browser modal preview for PDF/images.

### Tab 2: System-Generated Financial Summary
- **Data Source**: Real-time aggregation of balanced double-entry journal lines (`FinancialReportService::getIncomeExpenseStatement`) and collection report metrics (`EventCollectionReportService::getMetrics`).
- **Core Summary Cards**:
  1. **Total Income**: Sum of credit balances on income accounts (`4010`-`4040`) for this event.
  2. **Total Expenses**: Sum of debit balances on approved expense accounts (`5010`-`5080`) for this event.
  3. **Net Surplus / Deficit**: Calculated as `Total Income - Total Expenses`, with clear badges (Surplus / Deficit / Balanced).
  4. **Outstanding Pledge Dues**: Total uncollected pledge amounts.
- **Detailed Account Breakdowns**:
  - **Income Category Breakdown**: Account code, category name (bilingual), total amount, percentage share of revenue, and grand total.
  - **Expense Category Breakdown**: Account code, category name (bilingual), total amount, percentage share of expenditure, and grand total.
- **Event Fund Settlement Details (if completed)**:
  - Settlement number (`SETTLE-YYYY-XXXX`).
  - Settlement date.
  - Transferred surplus amount.
  - Destination account (`General Temple Fund 3010`).
  - Authorized settlement remarks and notes.
- **Unrecorded State Handling**:
  - If no ledger entries or verified payments exist for a historical event, displays an informative notice explaining that software ledger records were not recorded for that historical period, advising users to view the official uploaded statements in Tab 1.

---

## 6. Secure Document Preview & Download Architecture

### Endpoints
- **Preview**: `GET /previous-events/{slug}/documents/{uuid}/preview` (`public.previous-events.document.preview`)
- **Download**: `GET /previous-events/{slug}/documents/{uuid}/download` (`public.previous-events.document.download`)

### Security Invariants & Protections
1. **Opaque Identifiers (No IDOR)**:
   - URLs strictly use `$slug` (event) and `$uuid` (document). Internal database integer IDs are never exposed in public routes or markup.
2. **Double Publication Validation**:
   - The event must be published (`publication_status === 'published'`) and not in draft status.
   - The document must be published (`publication_status === 'published'`) and belong to the specified event.
   - Any access to unpublished documents or documents on unpublished events returns HTTP 404.
3. **Path Traversal Protection**:
   - Paths must strictly start with `private/event_documents/`.
   - File paths containing `..` or forbidden directory traversal sequences are immediately aborted with HTTP 404.
4. **MIME Sniffing Prevention**:
   - Serves files with `X-Content-Type-Options: nosniff`.
   - Explicit `Content-Type` matching MIME type.
   - Inline disposition for viewable files (`application/pdf`, `image/jpeg`, `image/png`, `image/webp`).
   - Attachment disposition (`Content-Disposition: attachment; filename="..."`) for spreadsheet/data files.

---

## 7. Bilingual Parity (EN & BN)

All user-facing strings are strictly localized with 100% key parity between `lang/en/portal.php` and `lang/bn/portal.php`:

| Key | English (`en`) | Bengali (`bn`) |
|---|---|---|
| `previous_events_reports` | Previous Events & Financial Reports | পূর্ববর্তী উৎসব ও আর্থিক প্রতিবেদন |
| `archive_title` | Previous Events & Financial Reports | পূর্ববর্তী উৎসব ও আর্থিক প্রতিবেদন |
| `archive_subtitle` | Transparent historical festival accounts, double-entry financial statements, and audited reports | অতীত উৎসবসমূহের আর্থিক স্বচ্ছতা, দ্বি-পক্ষীয় দাখিলা খতিয়ানের বিবরণী ও নিরীক্ষিত হিসাব |
| `system_financial_summary` | System-Generated Financial Summary | সিস্টেম-উদ্ভুত আর্থিক সারসংক্ষেপ |
| `audited_statements_tab` | Audited Statements & Official Documents | নিরীক্ষিত বিবরণী ও অফিশিয়াল নথিপত্র |
| `settled_badge` | Fund Settled | তহবিল নিষ্পত্তিকৃত |
| `report_categories.complete_financial_statement` | Complete Financial Statement | পূর্ণাঙ্গ আর্থিক বিবরণী |
| `report_categories.income_report` | Income Report | আয় সংক্রান্ত বিবরণী |
| `report_categories.expense_report` | Expense Report | ব্যয় সংক্রান্ত বিবরণী |
| `report_categories.expense_breakdown` | Expense Category Breakdown | ব্যয়ের খাতওয়ারি বিবরণী |
| `report_categories.event_settlement` | Event Settlement Report | ইভেন্ট তহবিল নিষ্পত্তি বিবরণী |

---

## 8. Automated Feature Test Suite

The test suite in `tests/Feature/PublicPortal/PreviousEventArchiveTest.php` covers 22 critical scenarios (119 assertions):

```bash
php artisan test tests/Feature/PublicPortal/PreviousEventArchiveTest.php
```

| Test Method | Scenario Verified |
|---|---|
| `test_public_archive_index_is_accessible_to_guests` | Guest accessibility; published events visible; draft/unpublished events excluded. |
| `test_public_archive_shows_published_documents_count` | Published documents count accurate; unpublished documents excluded from badge. |
| `test_public_archive_filters_by_category_and_year` | Category, year, and keyword search filters return accurate event subsets. |
| `test_public_show_displays_event_details_and_tabs` | Route resolution by slug; header badges; dual-source tab rendering. |
| `test_public_show_displays_double_entry_ledger_income_and_expense_breakdown` | Real double-entry posted journal vouchers calculate accurate Income, Expense, Surplus, and category rows. |
| `test_opening_balances_and_fund_transfers_are_excluded_from_event_income_and_expenses` | Invariant verified: opening balance equity entries and asset transfers are strictly excluded from event income/expenses. |
| `test_reversed_and_unposted_journal_entries_are_excluded_from_public_totals` | Invariant verified: unposted draft and reversed journal vouchers are strictly excluded from published totals. |
| `test_public_report_generation_does_not_modify_journal_entries_or_financial_records` | Read-only guarantee: generating public reports creates zero journal vouchers and mutates no ledger records. |
| `test_public_show_displays_fund_settlement_when_completed` | Completed settlement displays settlement number, date, and transferred surplus. |
| `test_public_show_displays_unrecorded_ledger_state_when_no_records_exist` | Friendly unrecorded state banner when no ledger vouchers exist. |
| `test_public_document_preview_stream_for_pdf` | PDF stream preview returns 200, Content-Type `application/pdf`, inline disposition, nosniff. |
| `test_public_document_download_for_excel` | Excel download returns 200 with attachment header and nosniff header. |
| `test_public_document_preview_or_download_denied_for_unpublished_document` | Unpublished document preview/download returns 404. |
| `test_public_document_denied_if_event_is_unpublished` | Published document on unpublished event returns 404. |
| `test_public_document_preview_blocked_against_path_traversal` | Path traversal attempts (`..`) are blocked and return 404. |
| `test_admin_can_upload_document_with_report_category_and_financial_year` | Admin document creation stores `report_category`, `financial_year`, and auto-generates UUID. |
| `test_admin_can_update_document_report_category_and_financial_year` | Admin document update modifies report category and year cleanly. |
| `test_legacy_event_accounts_route_redirects_to_previous_events` | Legacy `/event-accounts` route redirects to `/previous-events`. |
| `test_e2e_scenario_1_existing_historical_event_upload_publish_and_preview_pdf` | **E2E Scenario 1**: Admin selects existing historical event, uploads PDF financial report, publishes, verifies appearance in public archive, and renders in-browser PDF preview. |
| `test_e2e_scenario_2_historical_event_not_in_database_upload_publish_and_public_archive` | **E2E Scenario 2**: Admin creates historical event on-the-fly (`event_mode: new`) without any prior ledger entries or pledges, uploads final closing report, and verifies immediate public availability. |
| `test_e2e_scenario_3_excel_report_upload_publish_and_download` | **E2E Scenario 3**: Admin uploads detailed expense breakdown spreadsheet (.xlsx), publishes it, and public visitor securely downloads the exact file with attachment headers. |
| `test_e2e_scenario_4_unpublish_security_blocks_direct_public_access` | **E2E Scenario 4**: Admin unpublishes an official report; immediate 404 response blocks public archive listing, preview URL, and download URL; subsequent publishing cleanly restores access. |
