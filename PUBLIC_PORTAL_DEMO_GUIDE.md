# Public Portal — Complete Demo Data & End-to-End Manual QA Guide

**Project:** Temple Management System  
**Scope:** Public Portal Increments 1 through 6  
**Target Environment:** Local (`APP_ENV=local`, PHP 8.3+, MySQL 9.x / WAMP)  
**Database Seeder:** `database/seeders/PublicPortalDemoSeeder.php`  
**Cleanup Command:** `php artisan demo:public-portal-cleanup`  

---

## 1. Quick Start Commands

```bash
# 1. Seed complete Public Portal demo dataset (idempotent, safe for existing records)
php artisan db:seed --class=PublicPortalDemoSeeder

# 2. Start local development server (if not using Apache/WAMP web root)
php artisan serve

# 3. Clean up ONLY the demo dataset when manual QA is finished
php artisan demo:public-portal-cleanup --force
```

---

## 2. Authentication & Demo Credentials

All demo devotee passwords are set to `Donor@123456`. Admin passwords use standard credentials.

### Administrative Accounts
| Role | Email | Password | Access / Purpose |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `admin@temple.local` | `Admin@123456` | Full governance, ledger audit, verification & reversal authority |
| **Temple Admin** | `manager@temple.local` | `Manager@123456` | Verification, complaints resolution, event management |

### Fictional Devotee Accounts (10 Test Personas)
| Devotee Name | Email | Password | Phone | Privacy Mode | Scenario Focus |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Subir Ghosh** | `subir.donor@temple.local` | `Donor@123456` | `+8801711100001` | Public | Fully paid pledge (10k / 10k) |
| **Ananya Roy** | `ananya.donor@temple.local` | `Donor@123456` | `+8801711100002` | Public | Partially paid pledge (8k / 15k, 7k due) + Replied Complaint |
| **Bikash Chandra Das** | `bikash.donor@temple.local` | `Donor@123456` | `+8801711100003` | Anonymous | Anonymous donor privacy masking ("Anonymous Donor") |
| **Sunil Kumar Sen** | `sunil.donor@temple.local` | `Donor@123456` | `+8801711100004` | Private | Private devotee privacy masking ("Private Devotee") |
| **Prabir Sengupta** | `prabir.donor@temple.local` | `Donor@123456` | `+8801711100005` | Public | Pending payment verification + Submitted Complaint |
| **Tapash Paul** | `tapash.donor@temple.local` | `Donor@123456` | `+8801711100006` | Public | Rejected payment with reason + Complaint with attachment |
| **Debabrata Bhattacharya** | `debabrata.donor@temple.local` | `Donor@123456` | `+8801711100007` | Public | Verified then reversed payment, voided receipt, restored due |
| **Madhuri Chakraborty** | `madhuri.donor@temple.local` | `Donor@123456` | `+8801711100008` | Public | Voluntary walk-in / ad-hoc event donation without pledge (3k) |
| **Kalyan Kumar Bose** | `kalyan.donor@temple.local` | `Donor@123456` | `+8801711100009` | Public | Excess payment auto-split (5k pledge + 2k voluntary) + Resolved Complaint |
| **Gopal Chandra Dey** | `gopal.donor@temple.local` | `Donor@123456` | `+8801711100010` | Public | Fully unpaid pledge (5k due) + Rejected commercial inquiry |

---

## 3. Key Portal URLs

| Feature / Page | URL Path | Required Auth |
| :--- | :--- | :--- |
| **Public Portal Home** | `/` | Guest / Public |
| **Events Catalog** | `/events` | Guest / Public |
| **Durga Puja 2026 Details & Report** | `/events/durga-puja-2026` | Guest / Public |
| **Kali Puja 2026 (Upcoming)** | `/events/shyama-kali-puja-2026` | Guest / Public |
| **Janmashtami 2026 (Closed Archive)** | `/events/janmashtami-festival-2026` | Guest / Public |
| **Online Payment Submission Form** | `/events/durga-puja-2026/pay` | Devotee Login |
| **Donor My Contributions Dashboard** | `/my-contributions` | Devotee Login |
| **Donor Payment Details View** | `/my-contributions/{payment_code}` | Devotee Login (Owner) |
| **Official Receipt Download** | `/receipts/{receipt_number}/download` | Devotee Login (Owner) or Admin |
| **Submit Public Complaint Form** | `/complaints/create` | Guest or Devotee |
| **Donor My Complaints Dashboard** | `/my-complaints` | Devotee Login |
| **Donor Complaint Details View** | `/my-complaints/{tracking_number}` | Devotee Login (Owner) |
| **Admin Payment Verification Listing** | `/admin/portal/payments` | Admin (`verify_portal_payments`) |
| **Admin Payment Verification Details** | `/admin/portal/payments/{id}` | Admin (`verify_portal_payments`) |
| **Admin Complaint Management Listing** | `/admin/portal/complaints` | Admin (`manage_complaints`) |
| **Admin Complaint Detail & Reply** | `/admin/portal/complaints/{id}` | Admin (`manage_complaints`) |

---

## 4. Demo Events Catalog

1. **Sharodiya Durga Puja 2026 (`durga-puja-2026`)**
   - **Code:** `EVT-DEMO-DP26`
   - **Lifecycle Status:** `active`
   - **Publication Status:** `published`
   - **Dates:** 15 Oct 2026 – 20 Nov 2026 (Collection: 01 Oct 2026 – 30 Nov 2026)
   - **State:** Open for online payment submissions, pledges, donor list, and collection summary.
2. **Shyama Kali Puja & Deepavali 2026 (`shyama-kali-puja-2026`)**
   - **Code:** `EVT-DEMO-KP26`
   - **Lifecycle Status:** `upcoming`
   - **Publication Status:** `published`
   - **Dates:** 20 Nov 2026 – 25 Nov 2026 (Collection: 01 Nov 2026 – 15 Dec 2026)
   - **State:** Displays countdown/schedule, open for early contributions.
3. **Shri Krishna Janmashtami 2026 (`janmashtami-festival-2026`)**
   - **Code:** `EVT-DEMO-JM26`
   - **Lifecycle Status:** `closed`
   - **Publication Status:** `published`
   - **Dates:** 20 Aug 2026 – 26 Aug 2026 (Collection: 01 Aug 2026 – 31 Aug 2026)
   - **State:** Archived celebration, payment form is closed.
4. **Saraswati Puja Planning 2027 (`saraswati-puja-planning-2027`)**
   - **Code:** `EVT-DEMO-SP27`
   - **Lifecycle Status:** `draft`
   - **Publication Status:** `draft` (Unpublished)
   - **State:** Internal draft event. **Must NOT appear** in public listings (confirms publication security filter).

---

## 5. Receiver Accounts Configured

| Method | Account Name | Account Number | Account Type | Ledger Account | Display Order |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **bKash** | Central Temple bKash Merchant | `01711001122` | Merchant | 1030 (MFS Asset) | 1 |
| **Nagad** | Central Temple Nagad Merchant | `01811002233` | Merchant | 1030 (MFS Asset) | 2 |
| **Rocket** | Central Temple Rocket Merchant | `01911003344` | Merchant | 1030 (MFS Asset) | 3 |
| **Bank Transfer** | Uttara Central Temple Management Fund | `4401200033445` | Current (Sonali Bank) | 1020 (Main Bank) | 4 |

---

## 6. Financial Ledger & Collection Arithmetic Verification

For **Sharodiya Durga Puja 2026 (`durga-puja-2026`)**:

| Metric | Amount (BDT) | Formula / Source |
| :--- | :--- | :--- |
| **Total Pledges Committed** | `86,000.00` | Subir (10k) + Ananya (15k) + Bikash (12k) + Sunil (20k) + Prabir (8k) + Tapash (5k) + Debabrata (6k) + Kalyan (5k) + Gopal (5k) |
| **Verified Pledge Collections** | `45,000.00` | Subir (10k) + Ananya (8k) + Bikash (12k) + Sunil (10k) + Kalyan (5k) |
| **Excess Contributions** | `2,000.00` | Kalyan Kumar Bose excess beyond pledge (7k - 5k) |
| **Voluntary Event Donations** | `3,000.00` | Madhuri Chakraborty voluntary walk-in (no pledge) |
| **Total Verified Collections** | `50,000.00` | `45,000.00 + 2,000.00 + 3,000.00` |
| **Pending Unverified Collections**| `8,000.00` | Prabir Sengupta submitted bKash payment awaiting admin review |
| **Outstanding Pledges** | `41,000.00` | `86,000.00 (Total Pledged) - 45,000.00 (Verified Collections)` |
| **Net Collection Progress** | `58.14%` | `50,000.00 / 86,000.00` |

---

## 7. Step-by-Step Manual QA Scenarios

### Scenario 1: Fully Paid Pledge (Subir Ghosh)
1. Navigate to `/login` and sign in as `subir.donor@temple.local` / `Donor@123456`.
2. Visit `/my-contributions`:
   - Verify Durga Puja pledge status badge displays **Paid** (Green).
   - Verify Pledged: `10,000.00`, Paid: `10,000.00`, Outstanding: `0.00`.
3. Click into payment details view (`/my-contributions/PAY-2026-0001` or link from table).
   - Verify payment status displays **Verified**.
   - Verify payment method is **bKash** with TrxID `BK927164901`.
   - Click **Download Receipt** button. Verify receipt PDF/print view loads cleanly with receipt number.

### Scenario 2: Partially Paid Pledge & Outstanding Due (Ananya Roy)
1. Sign in as `ananya.donor@temple.local` / `Donor@123456`.
2. Visit `/my-contributions`:
   - Verify pledge status badge displays **Partial** (Yellow/Warning).
   - Verify Pledged: `15,000.00`, Paid: `8,000.00`, Outstanding: `7,000.00`.
3. Click "Pay Remaining Due" to test prefilled payment form.

### Scenario 3: Anonymous Donor Privacy Protection (Bikash Chandra Das)
1. Open an Incognito window (unauthenticated guest).
2. Visit `/events/durga-puja-2026`.
3. Scroll to the **Donor List & Contributions** section:
   - Verify Bikash's payment of `12,000.00 BDT` appears under name **"Anonymous Donor"** (or **"বেনামী দাতা"** in Bengali).
   - Verify Bikash's real name, phone, and email are strictly hidden.
4. Verify total event collection includes the `12,000.00 BDT` in aggregated financial metrics.

### Scenario 4: Private Devotee Privacy Protection (Sunil Kumar Sen)
1. Open an Incognito window (unauthenticated guest).
2. Visit `/events/durga-puja-2026`.
3. Locate Sunil's contribution of `10,000.00 BDT`:
   - Verify name displays as **"Private Devotee"** (or **"ব্যক্তিগত ভক্ত"** in Bengali).
   - Verify phone and email are not exposed.
4. Verify the `10,000.00 BDT` is counted towards the `50,000.00 BDT` total verified collections.

### Scenario 5: Admin Payment Verification Queue (Prabir Sengupta)
1. Sign in as Admin (`manager@temple.local` / `Manager@123456`).
2. Navigate to `/admin/portal/payments`:
   - Verify Prabir Sengupta's pending payment (`8,000.00 BDT`, bKash, TrxID `BK771092441`) appears in the **Pending Verification** tab.
3. Click **Review Payment**:
   - Inspect payment details, sender mobile, receiver account.
   - Click **Download Proof** — verify private proof file downloads safely without 404 or path traversal.
   - Click **Verify Payment** — confirm receipt is issued and ledger journal voucher is generated.

### Scenario 6: Rejected Payment Handling (Tapash Paul)
1. Sign in as `tapash.donor@temple.local` / `Donor@123456`.
2. Visit `/my-contributions`:
   - Locate payment with status **Rejected** (Red badge).
   - Click to view details: verify rejection reason is displayed clearly: *"MFS Transaction ID could not be matched with bank statement. Please submit valid TrxID."*
   - Verify pledge outstanding due remains `5,000.00 BDT`.

### Scenario 7: Payment Reversal & Audit Integrity (Debabrata Bhattacharya)
1. Sign in as `debabrata.donor@temple.local` / `Donor@123456`.
2. Visit `/my-contributions`:
   - Locate payment with status **Reversed** (Secondary badge).
   - Click details: verify reversal reason *"Disputed bank chargeback / erroneous duplicate transfer"*.
   - Verify receipt is marked **Voided** and pledge outstanding balance was restored to `6,000.00 BDT`.
3. Sign in as Admin (`admin@temple.local`):
   - Check Financial Ledger / Journal Entries: verify balanced reversal journal entry was automatically posted.

### Scenario 8: Voluntary Walk-in Contribution Without Prior Pledge (Madhuri Chakraborty)
1. Sign in as `madhuri.donor@temple.local` / `Donor@123456`.
2. Visit `/my-contributions`:
   - Verify payment of `3,000.00 BDT` is marked as **Event Voluntary Donation**.
   - Verify official receipt is issued and downloadable.

### Scenario 9: Excess Payment Auto-Allocation (Kalyan Kumar Bose)
1. Sign in as `kalyan.donor@temple.local` / `Donor@123456`.
2. Visit `/my-contributions`:
   - Pledge was `5,000.00 BDT`, payment was `7,000.00 BDT`.
   - Verify allocations: `5,000.00 BDT` settled pledge (status: **Paid**), and `2,000.00 BDT` was automatically split as voluntary contribution!
   - Verify single combined receipt was issued for `7,000.00 BDT`.

### Scenario 10: Unpaid Pledge Tracking (Gopal Chandra Dey)
1. Sign in as `gopal.donor@temple.local` / `Donor@123456`.
2. Visit `/my-contributions`:
   - Verify pledge status displays **Unpaid** (Red/Danger badge).
   - Verify outstanding due is `5,000.00 BDT`.
   - Click "Make Payment" to verify form opens with preselected pledge.

---

## 8. Complaint Management QA Scenarios

| Tracking # | Complainant | Category | Status | Priority | Verification Focus |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **`CMP-DEMO-001`** | Prabir Sengupta | Payment Not Updated | `submitted` | Medium | In admin unassigned/new queue; devotee sees "Submitted" badge |
| **`CMP-DEMO-002`** | Tapash Paul | Wrong Paid Amount | `under_review` | High | Private PDF bank attachment downloadable by Admin & Donor |
| **`CMP-DEMO-003`** | Ananya Roy | Wrong Due Amount | `replied` | Medium | Devotee sees official response; admin sees private internal note |
| **`CMP-DEMO-004`** | Kalyan Kumar Bose | Other | `resolved` | Low | Resolution timestamp and note displayed; resolved badge |
| **`CMP-DEMO-005`** | Gopal Chandra Dey | General Inquiry | `rejected` | Low | Clear polite rejection rationale provided |

### Complaint Workflow Testing Steps:
1. **Donor View:**
   - Log in as `ananya.donor@temple.local`, visit `/my-complaints`.
   - Click `CMP-DEMO-003`: verify official admin reply is visible, but internal notes are strictly hidden.
2. **Admin View:**
   - Log in as `manager@temple.local`, visit `/admin/portal/complaints`.
   - Filter by status (`under_review`, `replied`, `resolved`, `rejected`).
   - Open `CMP-DEMO-002`: verify download of `demo_complaint_tapash.pdf`.
   - Post an internal note or status update; verify timeline activity is logged.

---

## 9. Cleanup & Restoration

When QA testing is finished, execute:
```bash
php artisan demo:public-portal-cleanup --force
```
This safely removes all demo people (`DEV-DEMO-%`), demo users (`%.donor@temple.local`), demo events (`EVT-DEMO-%`), demo receiver accounts, demo payments, receipts, journal entries, and private storage files without altering any real records.
