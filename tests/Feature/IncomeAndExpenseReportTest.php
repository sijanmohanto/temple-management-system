<?php

namespace Tests\Feature;

use App\Models\CollectionPayment;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\ManualIncome;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Receipt;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeAndExpenseReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $devotee;

    protected Person $registeredPerson;

    protected Person $committeePerson;

    protected Event $event;

    protected FinancialAccount $expenseAccount;

    protected FinancialAccount $cashAccount;

    protected FinancialAccount $incomeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin', 'email' => 'super@example.com']);
        $this->superAdmin->assignRole('super_admin');

        $this->treasurer = User::factory()->create(['name' => 'Treasurer', 'email' => 'treasurer@example.com']);
        $this->treasurer->assignRole('treasurer');

        $this->devotee = User::factory()->create(['name' => 'Devotee', 'email' => 'devotee@example.com']);
        $this->devotee->assignRole('devotee');

        $this->registeredPerson = Person::factory()->create([
            'person_code' => 'P-2026-0001',
            'name_en' => 'Rahul Sharma',
            'name_bn' => 'রাহুল শর্মা',
            'phone' => '01711000001',
        ]);

        $this->committeePerson = Person::factory()->create([
            'person_code' => 'P-2026-0002',
            'name_en' => 'Anil Mukherjee',
            'name_bn' => 'অনিল মুখার্জী',
            'phone' => '01711000002',
        ]);

        $committee = Committee::factory()->create([
            'name_en' => 'Executive Committee',
            'name_bn' => 'কার্যনির্বাহী কমিটি',
            'status' => 'active',
        ]);

        CommitteeMembership::create([
            'committee_id' => $committee->id,
            'person_id' => $this->committeePerson->id,
            'designation_en' => 'Vice President',
            'start_date' => now()->startOfYear(),
            'status' => 'active',
        ]);

        $category = EventCategory::first();
        $this->event = Event::create([
            'event_code' => 'EVT-2026-TEST',
            'name_en' => 'Durga Puja 2026',
            'name_bn' => 'দুর্গাপূজা ২০২৬',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'venue' => 'Main Temple',
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-20',
            'status' => Event::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->expenseAccount = FinancialAccount::where('account_type', FinancialAccount::TYPE_EXPENSE)->first();
        $this->cashAccount = FinancialAccount::where('account_code', FinancialAccount::CODE_CASH)->first();
        $this->incomeAccount = FinancialAccount::where('account_type', FinancialAccount::TYPE_INCOME)->first();
    }

    public function test_guest_cannot_access_income_or_expense_reports(): void
    {
        $this->get(route('finance.reports.income'))->assertRedirect(route('login'));
        $this->get(route('finance.reports.expenses'))->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_cannot_access_reports(): void
    {
        $this->actingAs($this->devotee)->get(route('finance.reports.income'))->assertForbidden();
        $this->actingAs($this->devotee)->get(route('finance.reports.expenses'))->assertForbidden();
    }

    public function test_authorized_users_can_view_income_and_expense_report_pages(): void
    {
        $resIncome = $this->actingAs($this->treasurer)->get(route('finance.reports.income'));
        $resIncome->assertStatus(200);
        $resIncome->assertSee('Income Report');
        $resIncome->assertSee('Verified Income Total');
        $resIncome->assertSee('incomeReportTable');

        $resExpense = $this->actingAs($this->treasurer)->get(route('finance.reports.expenses'));
        $resExpense->assertStatus(200);
        $resExpense->assertSee('Expense Report');
        $resExpense->assertSee('Paid / Posted Expense Total');
        $resExpense->assertSee('expenseReportTable');
    }

    public function test_sidebar_includes_separate_income_and_expense_report_links(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('finance.reports.income'));
        $response->assertStatus(200);
        $response->assertSee(route('finance.reports.income'));
        $response->assertSee(route('finance.reports.expenses'));
        $response->assertSee('Income Report');
        $response->assertSee('Expense Report');
    }

    public function test_income_datatable_server_side_response_has_strictly_no_row_number_column(): void
    {
        // Create verified payment
        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0001',
            'payer_person_id' => $this->registeredPerson->id,
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->event->id,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 5000.00,
            'payment_method' => CollectionPayment::METHOD_CASH,
            'status' => CollectionPayment::STATUS_VERIFIED,
            'submitted_by' => $this->treasurer->id,
            'verified_by' => $this->superAdmin->id,
            'verified_at' => now(),
            'created_by' => $this->treasurer->id,
        ]);

        $response = $this->actingAs($this->treasurer)->getJson(route('finance.reports.income', ['draw' => 1]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
            'summary' => [
                'verified_total',
                'verified_total_formatted',
                'pending_amount',
                'pending_amount_formatted',
                'transaction_count',
                'transaction_count_formatted',
            ],
        ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $row = $data[0];

        // STRICTLY 7 columns, NO row number, NO serial number, NO '#' column
        $this->assertArrayHasKey('donor_details', $row);
        $this->assertArrayHasKey('purpose', $row);
        $this->assertArrayHasKey('event', $row);
        $this->assertArrayHasKey('amount', $row);
        $this->assertArrayHasKey('date', $row);
        $this->assertArrayHasKey('status', $row);
        $this->assertArrayHasKey('actions', $row);

        $this->assertArrayNotHasKey('#', $row);
        $this->assertArrayNotHasKey('sl', $row);
        $this->assertArrayNotHasKey('row_number', $row);
        $this->assertArrayNotHasKey('serial_number', $row);

        $this->assertStringContainsString('Rahul Sharma', $row['donor_details']);
        $this->assertStringContainsString('P-2026-0001', $row['donor_details']);
        $this->assertStringContainsString('৳5,000.00', $row['amount']);
        $this->assertStringContainsString('Verified', $row['status']);
    }

    public function test_guest_donation_appears_correctly_in_income_report(): void
    {
        // Guest donation without person profile
        CollectionPayment::create([
            'payment_code' => 'PAY-2026-0002',
            'payer_person_id' => null,
            'payer_name_override' => 'Suresh Roy (Guest)',
            'payer_phone_override' => '01888000000',
            'collection_type' => CollectionPayment::TYPE_GENERAL_DONATION,
            'event_id' => null,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 1500.00,
            'payment_method' => CollectionPayment::METHOD_BKASH,
            'status' => CollectionPayment::STATUS_VERIFIED,
            'submitted_by' => $this->treasurer->id,
            'verified_by' => $this->superAdmin->id,
            'verified_at' => now(),
            'created_by' => $this->treasurer->id,
        ]);

        $response = $this->actingAs($this->treasurer)->getJson(route('finance.reports.income', [
            'donor_type' => 'guest',
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Suresh Roy (Guest)', $data[0]['donor_details']);
        $this->assertStringContainsString('01888000000', $data[0]['donor_details']);
        $this->assertStringContainsString('General Temple Fund', $data[0]['event']);
        $this->assertStringContainsString('৳1,500.00', $data[0]['amount']);
    }

    public function test_income_summary_cards_calculate_verified_and_pending_accurately(): void
    {
        // 1 Verified payment: ৳3,000
        CollectionPayment::create([
            'payment_code' => 'PAY-2026-0010',
            'payer_person_id' => $this->registeredPerson->id,
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->event->id,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 3000.00,
            'payment_method' => CollectionPayment::METHOD_CASH,
            'status' => CollectionPayment::STATUS_VERIFIED,
            'submitted_by' => $this->treasurer->id,
            'verified_by' => $this->superAdmin->id,
            'verified_at' => now(),
            'created_by' => $this->treasurer->id,
        ]);

        // 1 Pending payment: ৳2,000 (must NOT count as verified)
        CollectionPayment::create([
            'payment_code' => 'PAY-2026-0011',
            'payer_person_id' => $this->registeredPerson->id,
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->event->id,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 2000.00,
            'payment_method' => CollectionPayment::METHOD_CASH,
            'status' => CollectionPayment::STATUS_PENDING,
            'submitted_by' => $this->treasurer->id,
            'created_by' => $this->treasurer->id,
        ]);

        // 1 Rejected payment: ৳1,000 (must NOT count as verified or pending)
        CollectionPayment::create([
            'payment_code' => 'PAY-2026-0012',
            'payer_person_id' => $this->registeredPerson->id,
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->event->id,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 1000.00,
            'payment_method' => CollectionPayment::METHOD_CASH,
            'status' => CollectionPayment::STATUS_REJECTED,
            'submitted_by' => $this->treasurer->id,
            'rejected_by' => $this->superAdmin->id,
            'rejected_at' => now(),
            'rejection_reason' => 'Invalid currency',
            'created_by' => $this->treasurer->id,
        ]);

        // 1 Posted Manual Income: ৳4,000 (counts towards verified income total)
        ManualIncome::create([
            'income_number' => 'INC-2026-0001',
            'income_date' => now()->toDateString(),
            'deposit_account_id' => $this->cashAccount->id,
            'income_account_id' => $this->incomeAccount->id,
            'amount' => 4000.00,
            'payer_name' => 'Anonymous Donor',
            'payment_method' => ManualIncome::METHOD_CASH,
            'status' => ManualIncome::STATUS_POSTED,
            'created_by' => $this->treasurer->id,
        ]);

        $response = $this->actingAs($this->treasurer)->getJson(route('finance.reports.income'));
        $summary = $response->json('summary');

        // Verified = 3,000 + 4,000 = 7,000
        $this->assertEquals(7000.00, $summary['verified_total']);
        $this->assertEquals('৳7,000.00', $summary['verified_total_formatted']);

        // Pending = 2,000
        $this->assertEquals(2000.00, $summary['pending_amount']);
        $this->assertEquals('৳2,000.00', $summary['pending_amount_formatted']);

        // Transactions count = 4
        $this->assertEquals(4, $summary['transaction_count']);
    }

    public function test_expense_datatable_server_side_response_has_strictly_no_row_number_column(): void
    {
        // 1 Approved Expense: ৳2,500
        Expense::create([
            'expense_number' => 'EXP-2026-0001',
            'expense_date' => now()->toDateString(),
            'financial_account_id' => $this->expenseAccount->id,
            'payment_account_id' => $this->cashAccount->id,
            'amount' => 2500.00,
            'title_en' => 'Temple Lighting & Electricals',
            'vendor_name' => 'Star Electric Works',
            'vendor_phone' => '01712000000',
            'payment_method' => Expense::METHOD_CASH,
            'status' => Expense::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
        ]);

        // 1 Pending Expense: ৳1,200
        Expense::create([
            'expense_number' => 'EXP-2026-0002',
            'expense_date' => now()->toDateString(),
            'financial_account_id' => $this->expenseAccount->id,
            'payment_account_id' => $this->cashAccount->id,
            'amount' => 1200.00,
            'title_en' => 'Prasad Distribution Supplies',
            'vendor_name' => 'Sweet Mart',
            'payment_method' => Expense::METHOD_CASH,
            'status' => Expense::STATUS_PENDING,
            'created_by' => $this->treasurer->id,
        ]);

        $response = $this->actingAs($this->treasurer)->getJson(route('finance.reports.expenses', ['draw' => 1]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
            'summary' => [
                'paid_total',
                'paid_total_formatted',
                'pending_amount',
                'pending_amount_formatted',
                'transaction_count',
                'transaction_count_formatted',
            ],
        ]);

        $summary = $response->json('summary');
        $this->assertEquals(2500.00, $summary['paid_total']);
        $this->assertEquals('৳2,500.00', $summary['paid_total_formatted']);
        $this->assertEquals(1200.00, $summary['pending_amount']);
        $this->assertEquals('৳1,200.00', $summary['pending_amount_formatted']);
        $this->assertEquals(2, $summary['transaction_count']);

        $data = $response->json('data');
        $this->assertCount(2, $data);
        $row = $data[0];

        // STRICTLY 7 columns, NO row number, NO serial number, NO '#' column
        $this->assertArrayHasKey('payee_details', $row);
        $this->assertArrayHasKey('purpose', $row);
        $this->assertArrayHasKey('event', $row);
        $this->assertArrayHasKey('amount', $row);
        $this->assertArrayHasKey('date', $row);
        $this->assertArrayHasKey('status', $row);
        $this->assertArrayHasKey('actions', $row);

        $this->assertArrayNotHasKey('#', $row);
        $this->assertArrayNotHasKey('sl', $row);
        $this->assertArrayNotHasKey('row_number', $row);
        $this->assertArrayNotHasKey('serial_number', $row);
    }

    public function test_income_offcanvas_details_endpoint_returns_sections_a_through_e(): void
    {
        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0050',
            'payer_person_id' => $this->registeredPerson->id,
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->event->id,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 4500.00,
            'payment_method' => CollectionPayment::METHOD_BANK,
            'transaction_reference' => 'TXN-998877',
            'bank_name' => 'City Bank',
            'status' => CollectionPayment::STATUS_VERIFIED,
            'submitted_by' => $this->treasurer->id,
            'verified_by' => $this->superAdmin->id,
            'verified_at' => now(),
            'verification_note' => 'Bank statement confirmed',
            'created_by' => $this->treasurer->id,
        ]);

        Receipt::create([
            'receipt_number' => 'REC-2026-0050',
            'collection_payment_id' => $payment->id,
            'receipt_year' => 2026,
            'issued_at' => now(),
            'issued_by' => $this->superAdmin->id,
            'status' => Receipt::STATUS_VALID,
        ]);

        PaymentAllocation::create([
            'collection_payment_id' => $payment->id,
            'target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT,
            'event_id' => $this->event->id,
            'amount' => 4500.00,
            'is_active' => true,
        ]);

        $url = route('finance.reports.income.details', ['type' => 'collection', 'id' => $payment->id]);
        $response = $this->actingAs($this->treasurer)->get($url, ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);

        // Section A: Transaction Info
        $response->assertSee('PAY-2026-0050');
        $response->assertSee('৳4,500.00');
        $response->assertSee('City Bank');
        $response->assertSee('TXN-998877');

        // Section B: Donor Info
        $response->assertSee('Rahul Sharma');
        $response->assertSee('P-2026-0001');

        // Section C: Event & Fund Allocation
        $response->assertSee('Durga Puja 2026');
        $response->assertSee('Voluntary Event');

        // Section D: Verification & Audit Info
        $response->assertSee('Super Admin');
        $response->assertSee('Bank statement confirmed');

        // Section E: Related Records & Actions
        $response->assertSee('REC-2026-0050');
        $response->assertSee(route('receipts.print', 1));
    }

    public function test_expense_offcanvas_details_endpoint_returns_sections_a_through_e(): void
    {
        $expense = Expense::create([
            'expense_number' => 'EXP-2026-0080',
            'expense_date' => now()->toDateString(),
            'financial_account_id' => $this->expenseAccount->id,
            'payment_account_id' => $this->cashAccount->id,
            'amount' => 3200.00,
            'title_en' => 'Puja Flowers and Garlands',
            'vendor_name' => 'Maa Tara Florist',
            'vendor_phone' => '01715000000',
            'invoice_number' => 'INV-8877',
            'reference_number' => 'REF-FLOWER-1',
            'payment_method' => Expense::METHOD_CASH,
            'notes' => 'Direct cash purchase for morning aarti',
            'status' => Expense::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
            'event_id' => $this->event->id,
        ]);

        $url = route('finance.reports.expenses.details', ['id' => $expense->id]);
        $response = $this->actingAs($this->treasurer)->get($url, ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);

        // Section A: Transaction Info
        $response->assertSee('EXP-2026-0080');
        $response->assertSee('৳3,200.00');
        $response->assertSee('INV-8877');
        $response->assertSee('REF-FLOWER-1');

        // Section B: Payee & Event Info
        $response->assertSee('Maa Tara Florist');
        $response->assertSee('01715000000');
        $response->assertSee('Durga Puja 2026');

        // Section C: Financial Info
        $response->assertSee($this->expenseAccount->account_code);
        $response->assertSee('Puja Flowers and Garlands');
        $response->assertSee('Direct cash purchase for morning aarti');

        // Section D: Approval & Audit Info
        $response->assertSee('Super Admin');

        // Section E: Attachments & Actions
        $response->assertSee(route('finance.expenses.show', $expense->id));
    }

    public function test_people_search_select2_endpoint(): void
    {
        $response = $this->actingAs($this->treasurer)->getJson(route('finance.reports.people-search', ['q' => 'Rahul']));

        $response->assertStatus(200);
        $results = $response->json('results');
        $this->assertNotEmpty($results);
        $this->assertEquals($this->registeredPerson->id, $results[0]['id']);
        $this->assertStringContainsString('Rahul Sharma', $results[0]['text']);
        $this->assertStringContainsString('P-2026-0001', $results[0]['text']);
    }

    public function test_bengali_locale_renders_clean_localized_labels_without_raw_translation_keys(): void
    {
        app()->setLocale('bn');

        // Create monthly contribution payment
        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0099',
            'payer_person_id' => $this->registeredPerson->id,
            'collection_type' => CollectionPayment::TYPE_MONTHLY_CONTRIBUTION,
            'event_id' => null,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 1000.00,
            'payment_method' => CollectionPayment::METHOD_CHEQUE,
            'status' => CollectionPayment::STATUS_VERIFIED,
            'submitted_by' => $this->treasurer->id,
            'verified_by' => $this->superAdmin->id,
            'verified_at' => now(),
            'created_by' => $this->treasurer->id,
        ]);

        // Manual income
        $manual = ManualIncome::create([
            'income_number' => 'INC-2026-0099',
            'income_date' => now()->toDateString(),
            'deposit_account_id' => $this->cashAccount->id,
            'income_account_id' => $this->incomeAccount->id,
            'amount' => 5000.00,
            'payer_name' => 'Kollan Trust',
            'payment_method' => ManualIncome::METHOD_CHEQUE,
            'status' => ManualIncome::STATUS_POSTED,
            'created_by' => $this->treasurer->id,
        ]);

        $tableRes = $this->actingAs($this->treasurer)
            ->withSession(['locale' => 'bn'])
            ->getJson(route('finance.reports.income'));

        $tableRes->assertStatus(200);
        $data = $tableRes->json('data');
        $this->assertNotEmpty($data);

        $jsonStr = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('donations.', $jsonStr);
        $this->assertStringNotContainsString('finance.', $jsonStr);
        $this->assertStringContainsString('কমিটির মাসিক চাঁদা', $jsonStr);
        $this->assertStringContainsString('যাচাইকৃত', $jsonStr);

        // Test offcanvas drawer for manual income in Bengali
        $drawerRes = $this->actingAs($this->treasurer)
            ->withSession(['locale' => 'bn'])
            ->get(route('finance.reports.income.details', ['type' => 'manual', 'id' => $manual->id]), [
                'X-Requested-With' => 'XMLHttpRequest',
            ]);

        $drawerRes->assertStatus(200);
        $drawerRes->assertDontSee('finance.payment_method');
        $drawerRes->assertDontSee('finance.credit_account');
        $drawerRes->assertDontSee('finance.debit_account');
        $drawerRes->assertDontSee('common.phone');
        $drawerRes->assertSee('পরিশোধের মাধ্যম');
        $drawerRes->assertSee('ক্রেডিট হিসাব (আয়)');
        $drawerRes->assertSee('ডেবিট হিসাব (জমা)');
        $drawerRes->assertSee('চেক');
    }
}
