<?php

namespace Tests\Feature;

use App\Models\AccountingPeriod;
use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\User;
use App\Services\CollectionPaymentService;
use App\Services\EventCollectionReportService;
use App\Services\EventSettlementService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EventDonationAndSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $devotee;

    protected Event $event;

    protected Person $person;

    protected CollectionPaymentService $paymentService;

    protected EventSettlementService $settlementService;

    protected EventCollectionReportService $reportService;

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

        $category = EventCategory::first();

        $this->event = Event::create([
            'event_code' => 'EVT-2026-0001',
            'name_en' => 'Kali Puja 2026',
            'name_bn' => 'কালীপূজা ২০২৬',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'venue' => 'Main Temple Mandap',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
            'collection_start_date' => '2026-09-01',
            'collection_end_date' => '2026-11-15',
            'status' => Event::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->person = Person::create([
            'person_code' => 'DEV-2026-0001',
            'name_en' => 'Subhashish Roy',
            'name_bn' => 'সুভাশীষ রায়',
            'phone' => '01712345678',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->paymentService = app(CollectionPaymentService::class);
        $this->settlementService = app(EventSettlementService::class);
        $this->reportService = app(EventCollectionReportService::class);
    }

    public function test_guest_visitor_can_make_voluntary_event_donation_without_profile_or_pledge(): void
    {
        $response = $this->actingAs($this->treasurer)->post(route('collections.store'), [
            'collection_type' => 'event_donation',
            'event_id' => $this->event->id,
            'payer_name_override' => 'Amal Guest Visitor',
            'payer_phone_override' => '01899999999',
            'amount' => 2500.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect();

        $payment = CollectionPayment::where('payer_name_override', 'Amal Guest Visitor')->first();
        $this->assertNotNull($payment);
        $this->assertNull($payment->payer_person_id);
        $this->assertNull($payment->payer_family_id);
        $this->assertEquals(2500.00, (float) $payment->amount);
        $this->assertEquals('Amal Guest Visitor', $payment->payer_display_name);

        // Assert no pledge created or fictitious dues
        $this->assertEquals(0, Pledge::where('event_id', $this->event->id)->count());

        // Verify payment
        $this->paymentService->verifyPayment($payment, $this->superAdmin->id);
        $payment->refresh();
        $this->assertTrue($payment->isVerified());

        // Assert posted to Event Income (4020)
        $journal = JournalEntry::where('source_type', CollectionPayment::class)->where('source_id', $payment->id)->first();
        $this->assertNotNull($journal);
        $eventIncomeLine = $journal->lines()->whereHas('financialAccount', fn ($q) => $q->where('account_code', '4020'))->first();
        $this->assertNotNull($eventIncomeLine);
        $this->assertEquals(2500.00, (float) $eventIncomeLine->amount);
        $this->assertEquals($this->event->id, $eventIncomeLine->event_id);
    }

    public function test_guest_visitor_can_make_general_donation_without_event_or_directory_profile(): void
    {
        $response = $this->actingAs($this->treasurer)->post(route('collections.store'), [
            'collection_type' => 'general_donation',
            'payer_name_override' => 'Bimal Guest',
            'payer_phone_override' => '01911112222',
            'amount' => 1500.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect();

        $payment = CollectionPayment::where('payer_name_override', 'Bimal Guest')->first();
        $this->assertNotNull($payment);
        $this->assertNull($payment->event_id);

        $this->paymentService->verifyPayment($payment, $this->superAdmin->id);
        $journal = JournalEntry::where('source_type', CollectionPayment::class)->where('source_id', $payment->id)->first();
        $this->assertNotNull($journal);

        // Assert posted to General Income (4010) with no event_id
        $genIncomeLine = $journal->lines()->whereHas('financialAccount', fn ($q) => $q->where('account_code', '4010'))->first();
        $this->assertNotNull($genIncomeLine);
        $this->assertEquals(1500.00, (float) $genIncomeLine->amount);
        $this->assertNull($genIncomeLine->event_id);
    }

    public function test_excess_contribution_splits_and_routes_to_current_event_fund(): void
    {
        // Pledged ৳5,000 for Kali Puja
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-10-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);

        // Contributor pays ৳7,000 with excess destination = voluntary_event
        $response = $this->actingAs($this->treasurer)->post(route('collections.store'), [
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 7000.00,
            'excess_target_type' => 'voluntary_event',
            'payment_date' => '2026-10-15',
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect();

        $payment = CollectionPayment::latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(7000.00, (float) $payment->amount);

        // Verify allocations split: ৳5,000 to pledge, ৳2,000 to voluntary_event
        $allocations = $payment->allocations()->get();
        $this->assertCount(2, $allocations);

        $pledgeAlloc = $allocations->where('target_type', PaymentAllocation::TARGET_PLEDGE)->first();
        $excessAlloc = $allocations->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_EVENT)->first();

        $this->assertNotNull($pledgeAlloc);
        $this->assertEquals(5000.00, (float) $pledgeAlloc->amount);

        $this->assertNotNull($excessAlloc);
        $this->assertEquals(2000.00, (float) $excessAlloc->amount);
        $this->assertEquals($this->event->id, $excessAlloc->event_id);

        // Verify payment and check pledge status
        $this->paymentService->verifyPayment($payment, $this->superAdmin->id);
        $pledge->refresh();

        $this->assertEquals(Pledge::STATUS_PAID, $pledge->status);
        $this->assertEquals(5000.00, (float) $pledge->verified_paid_amount);
        $this->assertEquals(2000.00, (float) $pledge->excess_contributed_amount);
        $this->assertEquals(0.00, (float) $pledge->outstanding_amount);

        // Ledger check: both allocations posted under 4020 (Event Income)
        $journal = JournalEntry::where('source_type', CollectionPayment::class)->where('source_id', $payment->id)->first();
        $this->assertNotNull($journal);
        $eventIncomeLines = $journal->lines()->whereHas('financialAccount', fn ($q) => $q->where('account_code', '4020'))->get();
        $this->assertEquals(7000.00, (float) $eventIncomeLines->sum('amount'));
    }

    public function test_excess_contribution_splits_and_routes_to_general_temple_fund(): void
    {
        // Pledged ৳5,000 for Kali Puja
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0002',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-10-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);

        // Contributor pays ৳7,000 with excess destination = voluntary_general
        $response = $this->actingAs($this->treasurer)->post(route('collections.store'), [
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 7000.00,
            'excess_target_type' => 'voluntary_general',
            'payment_date' => '2026-10-15',
            'payment_method' => 'cash',
        ]);

        $response->assertRedirect();

        $payment = CollectionPayment::latest('id')->first();
        $allocations = $payment->allocations()->get();
        $this->assertCount(2, $allocations);

        $pledgeAlloc = $allocations->where('target_type', PaymentAllocation::TARGET_PLEDGE)->first();
        $excessAlloc = $allocations->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_GENERAL)->first();

        $this->assertNotNull($pledgeAlloc);
        $this->assertEquals(5000.00, (float) $pledgeAlloc->amount);

        $this->assertNotNull($excessAlloc);
        $this->assertEquals(2000.00, (float) $excessAlloc->amount);
        $this->assertNull($excessAlloc->event_id); // Not tagged to event

        // Verify payment
        $this->paymentService->verifyPayment($payment, $this->superAdmin->id);
        $pledge->refresh();
        $this->assertEquals(Pledge::STATUS_PAID, $pledge->status);

        // Ledger check: ৳5,000 to 4020 (Event Income), ৳2,000 to 4010 (General Income)
        $journal = JournalEntry::where('source_type', CollectionPayment::class)->where('source_id', $payment->id)->first();
        $this->assertNotNull($journal);

        $eventIncomeLine = $journal->lines()->whereHas('financialAccount', fn ($q) => $q->where('account_code', '4020'))->first();
        $generalIncomeLine = $journal->lines()->whereHas('financialAccount', fn ($q) => $q->where('account_code', '4010'))->first();

        $this->assertNotNull($eventIncomeLine);
        $this->assertEquals(5000.00, (float) $eventIncomeLine->amount);
        $this->assertEquals($this->event->id, $eventIncomeLine->event_id);

        $this->assertNotNull($generalIncomeLine);
        $this->assertEquals(2000.00, (float) $generalIncomeLine->amount);
        $this->assertNull($generalIncomeLine->event_id);
    }

    public function test_partial_payment_balance_calculated_first_before_determining_excess_on_subsequent_payment(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0003',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-10-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);

        // Payment 1: ৳2,000 partial payment
        $payment1 = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 2000.00,
            'payment_date' => '2026-10-05',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 2000.00],
        ], $this->treasurer->id);

        $this->paymentService->verifyPayment($payment1, $this->superAdmin->id);
        $pledge->refresh();

        $this->assertEquals(Pledge::STATUS_PARTIAL, $pledge->status);
        $this->assertEquals(2000.00, (float) $pledge->verified_paid_amount);
        $this->assertEquals(3000.00, (float) $pledge->outstanding_amount);

        // Payment 2: ৳4,000 payment (exceeds outstanding ৳3,000 by ৳1,000)
        $payment2 = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 4000.00,
            'payment_date' => '2026-10-10',
            'payment_method' => 'cash',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_PLEDGE,
                'pledge_id' => $pledge->id,
                'amount' => 4000.00,
                'excess_target_type' => 'voluntary_event',
            ],
        ], $this->treasurer->id);

        $allocs2 = $payment2->allocations()->get();
        $this->assertCount(2, $allocs2);

        $pledgePortion = $allocs2->where('target_type', PaymentAllocation::TARGET_PLEDGE)->first();
        $excessPortion = $allocs2->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_EVENT)->first();

        // Must allocate remaining balance ৳3,000 to pledge, and ৳1,000 to excess
        $this->assertEquals(3000.00, (float) $pledgePortion->amount);
        $this->assertEquals(1000.00, (float) $excessPortion->amount);

        $this->paymentService->verifyPayment($payment2, $this->superAdmin->id);
        $pledge->refresh();

        $this->assertEquals(Pledge::STATUS_PAID, $pledge->status);
        $this->assertEquals(5000.00, (float) $pledge->verified_paid_amount);
        $this->assertEquals(1000.00, (float) $pledge->excess_contributed_amount);
        $this->assertEquals(0.00, (float) $pledge->outstanding_amount);
    }

    public function test_event_collection_metrics_calculation(): void
    {
        // 1. Pledge 1: ৳10,000 pledged, ৳7,000 verified paid
        $p1 = Pledge::create([
            'pledge_code' => 'PLG-2026-0010',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-10-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);
        $pay1 = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $p1->id,
            'event_id' => $this->event->id,
            'amount' => 7000.00,
            'payment_date' => '2026-10-05',
            'payment_method' => 'cash',
        ], [['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $p1->id, 'amount' => 7000.00]], $this->treasurer->id);
        $this->paymentService->verifyPayment($pay1, $this->superAdmin->id);

        // 2. Pledge 2: ৳5,000 pledged, ৳7,000 paid (৳5,000 pledge + ৳2,000 excess to event)
        $p2 = Pledge::create([
            'pledge_code' => 'PLG-2026-0011',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-10-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);
        $pay2 = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $p2->id,
            'event_id' => $this->event->id,
            'amount' => 7000.00,
            'payment_date' => '2026-10-06',
            'payment_method' => 'cash',
        ], [['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $p2->id, 'amount' => 7000.00, 'excess_target_type' => 'voluntary_event']], $this->treasurer->id);
        $this->paymentService->verifyPayment($pay2, $this->superAdmin->id);

        // 3. Voluntary Guest Donation: ৳3,000 verified
        $pay3 = $this->paymentService->submitPayment([
            'collection_type' => 'event_donation',
            'event_id' => $this->event->id,
            'payer_name_override' => 'Guest Donator',
            'amount' => 3000.00,
            'payment_date' => '2026-10-07',
            'payment_method' => 'cash',
        ], [['target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT, 'event_id' => $this->event->id, 'amount' => 3000.00]], $this->treasurer->id);
        $this->paymentService->verifyPayment($pay3, $this->superAdmin->id);

        // 4. Pending Payment: ৳1,000 unverified
        $this->paymentService->submitPayment([
            'collection_type' => 'event_donation',
            'event_id' => $this->event->id,
            'payer_name_override' => 'Unverified Payer',
            'amount' => 1000.00,
            'payment_date' => '2026-10-08',
            'payment_method' => 'cash',
        ], [['target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT, 'event_id' => $this->event->id, 'amount' => 1000.00]], $this->treasurer->id);

        // 5. Approved Event Expense: ৳4,000
        $expAccount = FinancialAccount::findByCode('5010');
        $cashAccount = FinancialAccount::findByCode('1010');
        Expense::create([
            'expense_number' => 'EXP-2026-0001',
            'expense_date' => '2026-10-09',
            'financial_account_id' => $expAccount->id,
            'payment_account_id' => $cashAccount->id,
            'amount' => 4000.00,
            'title_en' => 'Pratima Crafting & Clay',
            'payment_method' => 'cash',
            'event_id' => $this->event->id,
            'status' => Expense::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
        ]);

        $metrics = $this->reportService->getMetrics($this->event);

        $this->assertEquals(15000.00, $metrics['total_pledged']); // 10,000 + 5,000
        $this->assertEquals(12000.00, $metrics['verified_pledge_collections']); // 7,000 + 5,000
        $this->assertEquals(2000.00, $metrics['event_excess_contributions']); // 2,000
        $this->assertEquals(3000.00, $metrics['voluntary_event_donations']); // 3,000
        $this->assertEquals(17000.00, $metrics['total_verified_collections']); // 12k + 2k + 3k
        $this->assertEquals(3000.00, $metrics['outstanding_pledges']); // 15,000 - 12,000
        $this->assertEquals(1000.00, $metrics['pending_collections']); // 1,000
        $this->assertEquals(4000.00, $metrics['total_approved_expenses']); // 4,000
        $this->assertEquals(13000.00, $metrics['net_surplus']); // 17,000 - 4,000
        $this->assertTrue($metrics['is_above_target']); // 17,000 > 15,000
        $this->assertEquals(2000.00, $metrics['above_target_amount']); // 17,000 - 15,000
    }

    public function test_above_target_donors_report_and_outstanding_dues_report(): void
    {
        $pledge1 = Pledge::create([
            'pledge_code' => 'PLG-2026-0020',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-10-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);

        $pay = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge1->id,
            'event_id' => $this->event->id,
            'amount' => 7000.00,
            'payment_date' => '2026-10-05',
            'payment_method' => 'cash',
        ], [['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge1->id, 'amount' => 7000.00, 'excess_target_type' => 'voluntary_event']], $this->treasurer->id);
        $this->paymentService->verifyPayment($pay, $this->superAdmin->id);

        $pledge2 = Pledge::create([
            'pledge_code' => 'PLG-2026-0021',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-10-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);

        // Above target report test
        $aboveTarget = $this->reportService->getAboveTargetDonors($this->event);
        $this->assertCount(1, $aboveTarget);
        $this->assertEquals('Subhashish Roy', $aboveTarget[0]['donor_name']);
        $this->assertEquals(2000.00, $aboveTarget[0]['excess_amount']);

        // Outstanding dues report test
        $outstandingDues = $this->reportService->getOutstandingDues($this->event);
        $this->assertCount(1, $outstandingDues);
        $this->assertEquals('PLG-2026-0021', $outstandingDues[0]['pledge_code']);
        $this->assertEquals(10000.00, $outstandingDues[0]['outstanding_amount']);

        // Excess contribution report test
        $excessReport = $this->reportService->getExcessContributionReport($this->event);
        $this->assertCount(1, $excessReport);
        $this->assertEquals(2000.00, $excessReport[0]['amount']);
    }

    public function test_event_settlement_fails_if_event_is_active_or_upcoming(): void
    {
        $this->expectException(ValidationException::class);

        $this->settlementService->settleEvent($this->event, [
            'settlement_date' => '2026-11-20',
            'notes' => 'Attempting settlement on active event',
        ], $this->superAdmin->id);
    }

    public function test_event_settlement_succeeds_when_event_is_closing_or_closed_and_posts_balanced_transfer(): void
    {
        // Transition event to closing
        $this->event->update(['status' => Event::STATUS_CLOSING]);

        // Add ৳10,000 verified collections
        $pay = $this->paymentService->submitPayment([
            'collection_type' => 'event_donation',
            'event_id' => $this->event->id,
            'payer_name_override' => 'Devotee Fund',
            'amount' => 10000.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'cash',
        ], [['target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT, 'event_id' => $this->event->id, 'amount' => 10000.00]], $this->treasurer->id);
        $this->paymentService->verifyPayment($pay, $this->superAdmin->id);

        // Add ৳3,000 approved expense
        $expAccount = FinancialAccount::findByCode('5010');
        $cashAccount = FinancialAccount::findByCode('1010');
        Expense::create([
            'expense_number' => 'EXP-2026-0099',
            'expense_date' => '2026-10-16',
            'financial_account_id' => $expAccount->id,
            'payment_account_id' => $cashAccount->id,
            'amount' => 3000.00,
            'title_en' => 'Temple Decor',
            'payment_method' => 'cash',
            'event_id' => $this->event->id,
            'status' => Expense::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
        ]);

        // Reconciled surplus = ৳7,000
        $settlement = $this->settlementService->settleEvent($this->event, [
            'settlement_date' => '2026-11-20',
            'notes' => 'Closing event and settling surplus into General Temple Fund',
        ], $this->superAdmin->id);

        $this->assertNotNull($settlement);
        $this->assertEquals(7000.00, (float) $settlement->surplus_amount);
        $this->assertEquals(10000.00, (float) $settlement->total_collections);
        $this->assertEquals(3000.00, (float) $settlement->total_expenses);
        $this->assertTrue($settlement->isCompleted());

        // Check balanced journal entry
        $journal = $settlement->journalEntry;
        $this->assertNotNull($journal);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $journal->status);
        $this->assertEquals(7000.00, (float) $journal->total_debit);
        $this->assertEquals(7000.00, (float) $journal->total_credit);

        // Verify debits = credits
        $lines = $journal->lines;
        $this->assertCount(2, $lines);
        $debitLine = $lines->where('entry_type', 'debit')->first();
        $creditLine = $lines->where('entry_type', 'credit')->first();

        $this->assertEquals(7000.00, (float) $debitLine->amount);
        $this->assertEquals($this->event->id, $debitLine->event_id);

        $this->assertEquals(7000.00, (float) $creditLine->amount);
        $this->assertNull($creditLine->event_id);
    }

    public function test_event_settlement_cannot_be_duplicated_idempotent(): void
    {
        $this->event->update(['status' => Event::STATUS_CLOSED]);

        // First settlement
        $this->settlementService->settleEvent($this->event, [
            'settlement_date' => '2026-11-20',
            'notes' => 'First settlement',
        ], $this->superAdmin->id);

        // Second settlement must fail
        $this->expectException(ValidationException::class);
        $this->settlementService->settleEvent($this->event, [
            'settlement_date' => '2026-11-20',
            'notes' => 'Duplicate settlement attempt',
        ], $this->superAdmin->id);
    }

    public function test_unauthorized_user_cannot_settle_event_funds(): void
    {
        $this->event->update(['status' => Event::STATUS_CLOSING]);

        $response = $this->actingAs($this->devotee)->post(route('events.settle-funds', $this->event), [
            'settlement_date' => '2026-11-20',
            'notes' => 'Devotee trying to settle funds',
        ]);

        $response->assertForbidden();
    }

    public function test_authorized_user_can_settle_event_funds_via_post_route(): void
    {
        $this->event->update(['status' => Event::STATUS_CLOSING]);

        $pay = $this->paymentService->submitPayment([
            'collection_type' => 'event_donation',
            'event_id' => $this->event->id,
            'payer_name_override' => 'Payer Route',
            'amount' => 5000.00,
            'payment_date' => '2026-10-15',
            'payment_method' => 'cash',
        ], [['target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT, 'event_id' => $this->event->id, 'amount' => 5000.00]], $this->treasurer->id);
        $this->paymentService->verifyPayment($pay, $this->superAdmin->id);

        $response = $this->actingAs($this->superAdmin)->post(route('events.settle-funds', $this->event), [
            'settlement_date' => '2026-11-25',
            'notes' => 'Settling event via post route',
        ]);

        $response->assertRedirect(route('events.show', $this->event));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('event_settlements', [
            'event_id' => $this->event->id,
            'status' => 'completed',
            'surplus_amount' => 5000.00,
        ]);
    }

    public function test_event_settlement_with_zero_surplus_records_settlement_without_transfer_lines(): void
    {
        $this->event->update(['status' => Event::STATUS_CLOSING]);

        // Zero collections and zero expenses = ৳0.00 surplus
        $settlement = $this->settlementService->settleEvent($this->event, [
            'settlement_date' => '2026-11-25',
            'notes' => 'Settlement with zero activity',
        ], $this->superAdmin->id);

        $this->assertEquals(0.00, (float) $settlement->surplus_amount);
        $this->assertNull($settlement->journal_entry_id);
        $this->assertTrue($settlement->isCompleted());
    }

    public function test_event_closing_does_not_close_accounting_period(): void
    {
        $period = AccountingPeriod::create([
            'period_name' => 'FY 2026-2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_closed' => false,
        ]);

        $this->actingAs($this->superAdmin)->post(route('events.close', $this->event), [
            'closing_mode' => 'close_and_lock',
            'closing_reason' => 'Puja celebrations successfully completed.',
        ]);

        $this->event->refresh();
        $this->assertTrue($this->event->isClosed());

        // Assert accounting period remains open
        $period->refresh();
        $this->assertFalse($period->is_closed);
        $this->assertNull($period->closed_at);
    }

    public function test_event_details_page_renders_all_metrics_tabs_and_modals(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('events.show', $this->event));

        $response->assertOk();
        $response->assertSee('Event Collections &amp; Financial Overview', false);
        $response->assertSee('donorComparisonTable');
        $response->assertSee('aboveTargetModal');
        $response->assertSee('outstandingDuesModal');
        $response->assertSee('excessReportModal');
    }
}
