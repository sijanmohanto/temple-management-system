<?php

namespace Tests\Feature\PublicPortal;

use App\Http\Requests\PublicPortal\ReversePublicPaymentRequest;
use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\Receipt;
use App\Models\ReceiverAccount;
use App\Models\User;
use App\Services\CollectionPaymentService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PublicPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $templeAdmin;

    protected User $devotee;

    protected Event $event;

    protected ReceiverAccount $receiverAccount;

    protected Person $registeredDonor;

    protected Pledge $defaultPledge;

    protected CollectionPaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);
        Storage::fake('public');

        $this->paymentService = app(CollectionPaymentService::class);

        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->assignRole('super_admin');

        $this->treasurer = User::factory()->create(['status' => 'active']);
        $this->treasurer->assignRole('treasurer');

        $this->templeAdmin = User::factory()->create(['status' => 'active']);
        $this->templeAdmin->assignRole('temple_admin');

        $this->devotee = User::factory()->create(['status' => 'active']);
        $this->devotee->assignRole('devotee');

        $category = EventCategory::first();
        $this->event = Event::create([
            'event_code' => 'EVT-KP-2026',
            'name_en' => 'Kali Puja 2026',
            'name_bn' => 'শ্যামাপূজা ২০২৬',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-15',
            'status' => Event::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $mfsAccount = FinancialAccount::where('account_code', FinancialAccount::CODE_MFS)->first();

        $this->receiverAccount = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BKASH,
            'account_name' => 'Sri Sri Kali Mandir Official bKash',
            'account_number' => '01700000000',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'financial_account_id' => $mfsAccount?->id,
            'event_id' => $this->event->id,
            'is_active' => true,
            'is_verified' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->registeredDonor = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Biplob Kumar Das',
            'name_bn' => 'বিপ্লব কুমার দাস',
            'phone' => '01711223344',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->defaultPledge = Pledge::create([
            'pledge_code' => 'PLG-KP-001',
            'person_id' => $this->registeredDonor->id,
            'event_id' => $this->event->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledge_date' => '2026-10-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    protected function createPublicSubmission(array $overrides = [], ?Pledge $pledge = null): CollectionPayment
    {
        $amount = $overrides['amount'] ?? 1500.00;
        $pledge = $pledge ?? $this->defaultPledge;

        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'event_id' => $this->event->id,
            'payer_person_id' => $this->registeredDonor->id,
            'payment_date' => '2026-11-11',
            'amount' => $amount,
            'payment_method' => $overrides['payment_method'] ?? 'bkash',
            'transaction_reference' => $overrides['transaction_reference'] ?? 'TRX'.rand(100000, 999999),
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_PLEDGE,
                'pledge_id' => $pledge->id,
                'event_id' => $this->event->id,
                'amount' => $amount,
            ],
        ], $this->superAdmin->id);

        $payment->update(array_merge([
            'is_public_submission' => true,
            'receiver_account_id' => $this->receiverAccount->id,
            'pledge_id' => $pledge->id,
            'sender_mobile' => '01711223344',
            'public_donor_name' => $this->registeredDonor->name_en,
            'public_donor_phone' => $this->registeredDonor->phone,
        ], $overrides));

        return $payment->fresh();
    }

    public function test_guest_cannot_access_public_payments_admin(): void
    {
        $response = $this->get(route('portal.payments.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_cannot_access_public_payments(): void
    {
        $response = $this->actingAs($this->devotee)->get(route('portal.payments.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_public_payments_index(): void
    {
        $response = $this->actingAs($this->treasurer)->get(route('portal.payments.index'));
        $response->assertStatus(200);
        $response->assertSee(__('portal.payments.title'));
    }

    public function test_datatable_server_side_response_strictly_has_no_row_number_column(): void
    {
        $this->createPublicSubmission();

        $response = $this->actingAs($this->treasurer)->getJson(route('portal.payments.index'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                '*' => [
                    'donor_info',
                    'event_details',
                    'amount_details',
                    'transaction_details',
                    'proof',
                    'status',
                    'submitted_at',
                    'actions',
                ],
            ],
        ]);

        $firstRow = $response->json('data.0');
        $this->assertArrayNotHasKey('#', $firstRow);
        $this->assertArrayNotHasKey('sl', $firstRow);
        $this->assertArrayNotHasKey('id', $firstRow);
        $this->assertStringContainsString('Biplob Kumar Das', $firstRow['donor_info']);
    }

    public function test_filter_public_payments_by_status_and_receiver(): void
    {
        $pendingPayment = $this->createPublicSubmission(['transaction_reference' => 'TRXPENDING01']);

        $verifiedPayment = $this->createPublicSubmission(['transaction_reference' => 'TRXVERIFIED01']);
        $this->paymentService->verifyPayment($verifiedPayment, $this->treasurer->id);

        // Filter by pending_verification
        $response = $this->actingAs($this->treasurer)->getJson(route('portal.payments.index', [
            'status' => 'pending_verification',
        ]));
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsFiltered'));

        // Filter by verified
        $response = $this->actingAs($this->treasurer)->getJson(route('portal.payments.index', [
            'status' => 'verified',
        ]));
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsFiltered'));
    }

    public function test_pending_payment_not_affecting_verified_collection_or_pledge(): void
    {
        // Initial setup: Pledge ৳5,000, initial verified payment ৳1,000
        $initialPayment = $this->createPublicSubmission(['amount' => 1000.00, 'transaction_reference' => 'TRXINIT001']);
        $this->paymentService->verifyPayment($initialPayment, $this->treasurer->id);

        $this->defaultPledge->refresh();
        $this->assertEquals(1000.00, $this->defaultPledge->verified_paid_amount);
        $this->assertEquals(4000.00, $this->defaultPledge->outstanding_amount);

        // Create new pending public payment of ৳2,000
        $pendingPayment = $this->createPublicSubmission(['amount' => 2000.00, 'transaction_reference' => 'TRXPENDING002']);
        $this->assertTrue($pendingPayment->isPending());

        // Invariant: Pending payments must never reduce pledge outstanding or inflate verified collection
        $this->defaultPledge->refresh();
        $this->assertEquals(1000.00, $this->defaultPledge->verified_paid_amount);
        $this->assertEquals(4000.00, $this->defaultPledge->outstanding_amount);
        $this->assertEquals(Pledge::STATUS_PARTIAL, $this->defaultPledge->status);
    }

    public function test_scenario_a_partial_payment_verification(): void
    {
        // Pledge: ৳5,000. Previously verified: ৳1,000. Remaining: ৳4,000.
        $initialPayment = $this->createPublicSubmission(['amount' => 1000.00, 'transaction_reference' => 'TRXINIT01']);
        $this->paymentService->verifyPayment($initialPayment, $this->treasurer->id);

        // New submitted payment: ৳2,000
        $payment = $this->createPublicSubmission(['amount' => 2000.00, 'transaction_reference' => 'TRXPARTIAL02']);

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'Partial payment verified',
        ]);
        $response->assertStatus(200);

        $this->defaultPledge->refresh();
        // Total verified paid: ৳3,000
        $this->assertEquals(3000.00, $this->defaultPledge->total_contributed_amount);
        $this->assertEquals(3000.00, $this->defaultPledge->verified_paid_amount);
        // Remaining pledge: ৳2,000
        $this->assertEquals(2000.00, $this->defaultPledge->outstanding_amount);
        // Extra contribution: ৳0
        $this->assertEquals(0.00, $this->defaultPledge->excess_contributed_amount);
        $this->assertEquals(Pledge::STATUS_PARTIAL, $this->defaultPledge->status);
    }

    public function test_scenario_b_full_pledge_payment_verification(): void
    {
        // Pledge: ৳5,000. Previously verified: ৳1,000. Remaining: ৳4,000.
        $initialPayment = $this->createPublicSubmission(['amount' => 1000.00, 'transaction_reference' => 'TRXINIT02']);
        $this->paymentService->verifyPayment($initialPayment, $this->treasurer->id);

        // New submitted payment: ৳4,000
        $payment = $this->createPublicSubmission(['amount' => 4000.00, 'transaction_reference' => 'TRXFULL03']);

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'Full settlement verified',
        ]);
        $response->assertStatus(200);

        $this->defaultPledge->refresh();
        // Total verified paid: ৳5,000
        $this->assertEquals(5000.00, $this->defaultPledge->total_contributed_amount);
        $this->assertEquals(5000.00, $this->defaultPledge->verified_paid_amount);
        // Remaining pledge: ৳0
        $this->assertEquals(0.00, $this->defaultPledge->outstanding_amount);
        // Extra contribution: ৳0
        $this->assertEquals(0.00, $this->defaultPledge->excess_contributed_amount);
        $this->assertEquals(Pledge::STATUS_PAID, $this->defaultPledge->status);
    }

    public function test_scenario_c_payment_exceeds_remaining_pledge_with_extra_contribution(): void
    {
        // Pledge: ৳5,000. Previously verified: ৳1,000. Remaining: ৳4,000.
        $initialPayment = $this->createPublicSubmission(['amount' => 1000.00, 'transaction_reference' => 'TRXINIT03']);
        $this->paymentService->verifyPayment($initialPayment, $this->treasurer->id);

        // New submitted payment: ৳5,000 (exceeds remaining pledge by ৳1,000)
        $payment = $this->createPublicSubmission(['amount' => 5000.00, 'transaction_reference' => 'TRXEXCESS04']);

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'Payment exceeding pledge verified',
        ]);
        $response->assertStatus(200);

        $this->defaultPledge->refresh();

        // 1. Original Pledged Amount remains unchanged
        $this->assertEquals(5000.00, (float) $this->defaultPledge->pledged_amount);

        // 2. Total Verified Paid: ৳6,000 (Pledge ৳5,000 + Extra ৳1,000)
        $this->assertEquals(6000.00, $this->defaultPledge->total_contributed_amount);
        $this->assertEquals(5000.00, $this->defaultPledge->verified_paid_amount);

        // 3. Remaining Pledge Due = max(Pledged Amount - Total Verified Paid, 0) = 0
        $this->assertEquals(0.00, $this->defaultPledge->outstanding_amount);

        // 4. Extra Contribution = max(Total Verified Paid - Pledged Amount, 0) = ৳1,000
        $this->assertEquals(1000.00, $this->defaultPledge->excess_contributed_amount);
        $this->assertEquals(Pledge::STATUS_PAID, $this->defaultPledge->status);

        // 5. Check payment allocations: split into ৳4,000 pledge and ৳1,000 voluntary event
        $payment->refresh();
        $this->assertEquals(2, $payment->allocations()->count());

        $pledgeAlloc = $payment->allocations()->where('target_type', PaymentAllocation::TARGET_PLEDGE)->first();
        $this->assertNotNull($pledgeAlloc);
        $this->assertEquals(4000.00, (float) $pledgeAlloc->amount);

        $excessAlloc = $payment->allocations()->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_EVENT)->first();
        $this->assertNotNull($excessAlloc);
        $this->assertEquals(1000.00, (float) $excessAlloc->amount);
        $this->assertEquals($this->event->id, $excessAlloc->event_id);

        // 6. Check ledger entry: balanced debit ৳5,000 and credit lines totaling ৳5,000
        $journal = JournalEntry::where('source_type', CollectionPayment::class)
            ->where('source_id', $payment->id)
            ->first();
        $this->assertNotNull($journal);
        $this->assertEquals(5000.00, (float) $journal->total_debit);
        $this->assertEquals(5000.00, (float) $journal->total_credit);
        $this->assertTrue($journal->isBalanced());
    }

    public function test_duplicate_verification_prevention_idempotent(): void
    {
        $payment = $this->createPublicSubmission(['amount' => 2000.00, 'transaction_reference' => 'TRXIDEM001']);

        // First verification
        $response1 = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment));
        $response1->assertStatus(200);

        // Second verification attempt on already processed payment
        $response2 = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment));
        $response2->assertStatus(422);
        $response2->assertJson(['success' => false]);
    }

    public function test_duplicate_transaction_reference_prevention(): void
    {
        // First payment verified with TRX_UNIQUE_100
        $payment1 = $this->createPublicSubmission([
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRX_UNIQUE_100',
        ]);
        $this->paymentService->verifyPayment($payment1, $this->treasurer->id);

        // Second payment submitted with same bkash transaction reference
        $payment2 = $this->createPublicSubmission([
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRX_UNIQUE_100',
        ]);

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment2));
        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_authorized_user_can_link_existing_devotee_to_payment(): void
    {
        $newPerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Subhashis Roy',
            'phone' => '01755443322',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $payment = $this->createPublicSubmission();
        $payment->update(['payer_person_id' => null]);

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.link-devotee', $payment), [
            'person_id' => $newPerson->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $payment->refresh();
        $this->assertEquals($newPerson->id, $payment->payer_person_id);
    }

    public function test_authorized_user_can_reject_payment_with_mandatory_reason(): void
    {
        $payment = $this->createPublicSubmission();

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $payment), [
            'rejection_reason' => 'Transaction ID not found in bKash statement',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $payment->refresh();
        $this->assertTrue($payment->isRejected());
        $this->assertEquals($this->treasurer->id, $payment->rejected_by);
        $this->assertNotNull($payment->rejected_at);
        $this->assertEquals('Transaction ID not found in bKash statement', $payment->rejection_reason);

        // Must NOT create receipt or ledger entry
        $this->assertNull($payment->receipt);
        $this->assertNull($payment->finance_posted_at);
    }

    public function test_rejection_requires_reason(): void
    {
        $payment = $this->createPublicSubmission();

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $payment), [
            'rejection_reason' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('rejection_reason');
    }

    public function test_devotee_without_permission_cannot_verify_or_reject_payment(): void
    {
        $payment = $this->createPublicSubmission();

        $response = $this->actingAs($this->devotee)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'Unauthorized attempt',
        ]);
        $response->assertStatus(403);

        $response = $this->actingAs($this->devotee)->postJson(route('portal.payments.reject', $payment), [
            'rejection_reason' => 'Unauthorized attempt',
        ]);
        $response->assertStatus(403);
    }

    public function test_no_duplicate_income_or_ledger_entries_on_repeat(): void
    {
        $payment = $this->createPublicSubmission(['amount' => 1500.00, 'transaction_reference' => 'TRXREPEAT01']);

        $this->paymentService->verifyPayment($payment, $this->treasurer->id);

        $journalCount = JournalEntry::where('source_type', CollectionPayment::class)
            ->where('source_id', $payment->id)
            ->count();
        $this->assertEquals(1, $journalCount);
    }

    public function test_bengali_locale_renders_cleanly_without_missing_keys(): void
    {
        app()->setLocale('bn');

        $arun = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Arun Kumar Das',
            'name_bn' => 'অরুণ কুমার দাস',
            'phone' => '01711998877',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $arunPledge = Pledge::create([
            'pledge_code' => 'PLG-ARUN-001',
            'person_id' => $arun->id,
            'event_id' => $this->event->id,
            'pledge_year' => 2026,
            'pledged_amount' => 2000.00,
            'pledge_date' => '2026-10-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);

        $payment = $this->createPublicSubmission([
            'payer_person_id' => $arun->id,
            'public_donor_name' => 'অরুণ কুমার দাস',
            'public_donor_phone' => '01711998877',
        ], $arunPledge);

        $response = $this->actingAs($this->treasurer)->get(route('portal.payments.show', $payment));
        $response->assertStatus(200);
        $response->assertSee('অরুণ কুমার দাস');
        $response->assertDontSee('portal.payments.title');
    }

    // =========================================================================
    // Increment 5 Step 1: Security & Validation Hardening Test Suite (Cases 1-20)
    // =========================================================================

    public function test_case_01_authorized_admin_can_verify_eligible_pledge_payment(): void
    {
        $payment = $this->createPublicSubmission(['amount' => 1500.00, 'transaction_reference' => 'TRXC01VALID']);

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'Valid pledge payment approved by treasurer',
            'pledge_id' => $this->defaultPledge->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => __('portal.payments.verified_successfully'),
        ]);

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->status);
        $this->assertEquals($this->treasurer->id, $payment->verified_by);
        $this->assertNotNull($payment->receipt);
        $this->assertNotNull($payment->receipt->receipt_number);

        // General Ledger balance verification
        $journal = JournalEntry::where('source_type', CollectionPayment::class)
            ->where('source_id', $payment->id)
            ->first();
        $this->assertNotNull($journal);
        $this->assertTrue($journal->isBalanced());
        $this->assertEquals(1500.00, (float) $journal->total_debit);

        // Pledge balance reduction
        $this->defaultPledge->refresh();
        $this->assertEquals(1500.00, (float) $this->defaultPledge->verified_paid_amount);
        $this->assertEquals(3500.00, (float) $this->defaultPledge->outstanding_amount);
    }

    public function test_case_02_unauthorized_admin_cannot_verify(): void
    {
        $payment = $this->createPublicSubmission(['amount' => 1000.00, 'transaction_reference' => 'TRXC02UNAUTH']);

        // Devotee without verify_public_payments permission
        $response = $this->actingAs($this->devotee)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'Unauthorized attempt',
        ]);
        $response->assertStatus(403);

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_PENDING, $payment->status);
        $this->assertNull($payment->receipt);
    }

    public function test_case_03_submitter_cannot_approve_own_submission_unless_super_admin(): void
    {
        // Admin submits payment
        $payment = $this->createPublicSubmission([
            'amount' => 1200.00,
            'transaction_reference' => 'TRXC03SEP',
        ]);
        $payment->update(['submitted_by' => $this->treasurer->id]);

        // Treasurer (submitter) attempts to verify own submission -> Denied
        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'Self verification attempt',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('verification');

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_PENDING, $payment->status);

        // Super Admin verifies the same submission -> Allowed
        $superResponse = $this->actingAs($this->superAdmin)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'Super admin independent override verification',
        ]);
        $superResponse->assertStatus(200);

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->status);
        $this->assertEquals($this->superAdmin->id, $payment->verified_by);
    }

    public function test_case_04_admin_cannot_verify_same_payment_twice(): void
    {
        $payment = $this->createPublicSubmission(['amount' => 1000.00, 'transaction_reference' => 'TRXC04ONCE']);

        // 1st verification: succeeds
        $first = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment));
        $first->assertStatus(200);

        $receiptCountBefore = $payment->receipt()->count();
        $journalCountBefore = JournalEntry::where('source_type', CollectionPayment::class)->where('source_id', $payment->id)->count();

        // 2nd verification: rejected idempotently
        $second = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment));
        $second->assertStatus(422);
        $second->assertJson([
            'success' => false,
            'message' => __('portal.payments.already_processed'),
        ]);

        // Invariant: No duplicate receipts or journal entries created
        $this->assertEquals(1, $receiptCountBefore);
        $this->assertEquals(1, $journalCountBefore);
        $this->assertEquals(1, JournalEntry::where('source_type', CollectionPayment::class)->where('source_id', $payment->id)->count());
    }

    public function test_case_05_admin_cannot_verify_payment_using_another_persons_pledge(): void
    {
        // Person B with separate pledge
        $personB = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Joydeb Roy',
            'phone' => '01899887766',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);
        $pledgeB = Pledge::create([
            'pledge_code' => 'PLG-KP-JOYDEB',
            'person_id' => $personB->id,
            'event_id' => $this->event->id,
            'pledge_year' => 2026,
            'pledged_amount' => 4000.00,
            'pledge_date' => '2026-10-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);

        // Biplob's payment attempting to claim Joydeb's pledge
        $payment = $this->createPublicSubmission([
            'payer_person_id' => $this->registeredDonor->id,
            'amount' => 1500.00,
            'transaction_reference' => 'TRXC05CROSSPERSON',
        ]);

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'pledge_id' => $pledgeB->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('pledge_id');

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_PENDING, $payment->status);

        $pledgeB->refresh();
        $this->assertEquals(0.00, (float) $pledgeB->verified_paid_amount);
        $this->assertEquals(Pledge::STATUS_UNPAID, $pledgeB->status);
    }

    public function test_case_06_admin_cannot_verify_payment_using_another_events_pledge(): void
    {
        // Event 2: Saraswati Puja
        $event2 = Event::create([
            'event_code' => 'EVT-SP-2026',
            'name_en' => 'Saraswati Puja 2026',
            'name_bn' => 'সরস্বতী পূজা ২০২৬',
            'event_category_id' => EventCategory::first()->id,
            'event_year' => 2026,
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-12',
            'status' => Event::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        // Biplob's pledge for Event 2
        $pledgeEvent2 = Pledge::create([
            'pledge_code' => 'PLG-SP-BIPLOB',
            'person_id' => $this->registeredDonor->id,
            'event_id' => $event2->id,
            'pledge_year' => 2026,
            'pledged_amount' => 3000.00,
            'pledge_date' => '2026-01-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);

        // Payment is for Kali Puja (event 1), but pledge_id belongs to Saraswati Puja (event 2)
        $payment = $this->createPublicSubmission([
            'event_id' => $this->event->id,
            'amount' => 1000.00,
            'transaction_reference' => 'TRXC06CROSSEVENT',
        ]);

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'pledge_id' => $pledgeEvent2->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('pledge_id');

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_PENDING, $payment->status);

        $pledgeEvent2->refresh();
        $this->assertEquals(0.00, (float) $pledgeEvent2->verified_paid_amount);
    }

    public function test_case_07_voluntary_event_donation_verification_works_without_pledge(): void
    {
        // Voluntary public donation: no pledge
        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_donation',
            'event_id' => $this->event->id,
            'payer_person_id' => $this->registeredDonor->id,
            'payment_date' => '2026-11-12',
            'amount' => 800.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRXC07VOLUNTARY',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT,
                'pledge_id' => null,
                'event_id' => $this->event->id,
                'amount' => 800.00,
            ],
        ], $this->superAdmin->id);

        $payment->update([
            'is_public_submission' => true,
            'receiver_account_id' => $this->receiverAccount->id,
            'pledge_id' => null,
            'sender_mobile' => '01711223344',
            'public_donor_name' => $this->registeredDonor->name_en,
            'public_donor_phone' => $this->registeredDonor->phone,
        ]);

        $initialPledgePaid = (float) $this->defaultPledge->fresh()->verified_paid_amount;

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'Voluntary event donation verified without pledge',
        ]);

        $response->assertStatus(200);
        $payment->refresh();

        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->status);
        $this->assertEquals(CollectionPayment::TYPE_EVENT_DONATION, $payment->collection_type);
        $this->assertNull($payment->pledge_id);
        $this->assertNotNull($payment->receipt);

        // General Ledger check: credited to Event Income (4020)
        $journal = JournalEntry::where('source_type', CollectionPayment::class)->where('source_id', $payment->id)->first();
        $this->assertNotNull($journal);
        $this->assertTrue($journal->isBalanced());

        // Default pledge balance must remain completely untouched
        $this->assertEquals($initialPledgePaid, (float) $this->defaultPledge->fresh()->verified_paid_amount);
    }

    public function test_case_08_admin_can_reject_pending_payment_with_valid_reason(): void
    {
        $payment = $this->createPublicSubmission(['amount' => 750.00, 'transaction_reference' => 'TRXC08REJECT']);

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $payment), [
            'rejection_reason' => 'Sender bKash account not found in merchant statement.',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_REJECTED, $payment->status);
        $this->assertEquals($this->treasurer->id, $payment->rejected_by);
        $this->assertNotNull($payment->rejected_at);
        $this->assertEquals('Sender bKash account not found in merchant statement.', $payment->rejection_reason);
    }

    public function test_case_09_rejection_without_valid_reason_fails(): void
    {
        $payment = $this->createPublicSubmission(['amount' => 750.00, 'transaction_reference' => 'TRXC09FAIL']);

        // Empty reason
        $responseEmpty = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $payment), [
            'rejection_reason' => '',
        ]);
        $responseEmpty->assertStatus(422);
        $responseEmpty->assertJsonValidationErrors('rejection_reason');

        // Reason < 3 characters
        $responseShort = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $payment), [
            'rejection_reason' => 'No',
        ]);
        $responseShort->assertStatus(422);
        $responseShort->assertJsonValidationErrors('rejection_reason');

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_PENDING, $payment->status);
    }

    public function test_case_10_rejected_payments_create_no_journal_entries_or_receipts(): void
    {
        $payment = $this->createPublicSubmission(['amount' => 2500.00, 'transaction_reference' => 'TRXC10NOLEDGER']);

        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $payment), [
            'rejection_reason' => 'Invalid transaction reference provided by donor.',
        ])->assertStatus(200);

        $payment->refresh();
        $this->assertNull($payment->receipt);
        $this->assertNull($payment->finance_posted_at);

        $journalCount = JournalEntry::where('source_type', CollectionPayment::class)->where('source_id', $payment->id)->count();
        $this->assertEquals(0, $journalCount);

        // Allocations must be deactivated
        $activeAllocations = $payment->allocations()->where('is_active', true)->count();
        $this->assertEquals(0, $activeAllocations);
    }

    public function test_case_11_verified_payments_cannot_be_rejected(): void
    {
        $payment = $this->createPublicSubmission(['amount' => 1500.00, 'transaction_reference' => 'TRXC11VERNOTREJ']);
        $this->paymentService->verifyPayment($payment, $this->treasurer->id);

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->status);

        // Attempt rejection on verified payment
        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $payment), [
            'rejection_reason' => 'Attempting to reject an already verified payment.',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => __('portal.payments.already_processed'),
        ]);

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->status);
        $this->assertNull($payment->rejected_by);
    }

    public function test_case_12_reversed_payments_cannot_be_verified_or_rejected(): void
    {
        $payment = $this->createPublicSubmission(['amount' => 1000.00, 'transaction_reference' => 'TRXC12REVERSED']);
        $this->paymentService->verifyPayment($payment, $this->treasurer->id);
        $payment->refresh();

        // Reverse payment
        $this->paymentService->reversePayment($payment, $this->treasurer->id, 'Reversal for audit testing');
        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_REVERSED, $payment->status);

        // Attempt verify -> Rejected
        $verifyAttempt = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'Attempting to verify reversed payment',
        ]);
        $verifyAttempt->assertStatus(422);
        $verifyAttempt->assertJson([
            'success' => false,
            'message' => __('portal.payments.already_processed'),
        ]);

        // Attempt reject -> Rejected
        $rejectAttempt = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $payment), [
            'rejection_reason' => 'Attempting to reject reversed payment',
        ]);
        $rejectAttempt->assertStatus(422);
        $rejectAttempt->assertJson([
            'success' => false,
            'message' => __('portal.payments.already_processed'),
        ]);

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_REVERSED, $payment->status);
    }

    public function test_case_13_pending_payment_can_be_linked_to_eligible_person(): void
    {
        $newPerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Tapas Majumder',
            'phone' => '01799881122',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $payment = $this->createPublicSubmission(['transaction_reference' => 'TRXC13LINK']);
        $payment->update(['payer_person_id' => null]);

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.link-devotee', $payment), [
            'person_id' => $newPerson->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => __('portal.payments.linked_successfully'),
            'person_name' => $newPerson->name_en,
        ]);

        $payment->refresh();
        $this->assertEquals($newPerson->id, $payment->payer_person_id);
    }

    public function test_case_14_verified_payment_cannot_be_relinked(): void
    {
        $payment = $this->createPublicSubmission(['transaction_reference' => 'TRXC14VERLINK']);
        $this->paymentService->verifyPayment($payment, $this->treasurer->id);

        $newPerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Debabrata Das',
            'phone' => '01755667788',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $originalPayerId = $payment->payer_person_id;

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.link-devotee', $payment), [
            'person_id' => $newPerson->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => __('portal.payments.cannot_relink_processed_payment'),
        ]);

        $payment->refresh();
        $this->assertEquals($originalPayerId, $payment->payer_person_id);
    }

    public function test_case_15_rejected_payment_cannot_be_relinked(): void
    {
        $payment = $this->createPublicSubmission(['transaction_reference' => 'TRXC15REJLINK']);
        $this->paymentService->rejectPayment($payment, $this->treasurer->id, 'Initial rejection');

        $newPerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Pankaj Sen',
            'phone' => '01711335577',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $originalPayerId = $payment->payer_person_id;

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.link-devotee', $payment), [
            'person_id' => $newPerson->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => __('portal.payments.cannot_relink_processed_payment'),
        ]);

        $payment->refresh();
        $this->assertEquals($originalPayerId, $payment->payer_person_id);
    }

    public function test_case_16_reversed_payment_cannot_be_relinked(): void
    {
        $payment = $this->createPublicSubmission(['transaction_reference' => 'TRXC16REVLINK']);
        $this->paymentService->verifyPayment($payment, $this->treasurer->id);
        $payment->refresh();
        $this->paymentService->reversePayment($payment, $this->treasurer->id, 'Reversal reason');

        $newPerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Shyamal Ghosh',
            'phone' => '01722446688',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $originalPayerId = $payment->payer_person_id;

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.link-devotee', $payment), [
            'person_id' => $newPerson->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => __('portal.payments.cannot_relink_processed_payment'),
        ]);

        $payment->refresh();
        $this->assertEquals($originalPayerId, $payment->payer_person_id);
    }

    public function test_case_17_unauthorized_person_linking_is_denied(): void
    {
        $payment = $this->createPublicSubmission(['transaction_reference' => 'TRXC17UNAUTHLINK']);

        $newPerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Anil Bhowmik',
            'phone' => '01733557799',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->devotee)->postJson(route('portal.payments.link-devotee', $payment), [
            'person_id' => $newPerson->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_case_18_invalid_or_inactive_person_linking_is_denied(): void
    {
        $payment = $this->createPublicSubmission(['transaction_reference' => 'TRXC18INACTIVELINK']);

        // Inactive person
        $inactivePerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Inactive Devotee',
            'phone' => '01700998877',
            'status' => 'inactive',
            'created_by' => $this->superAdmin->id,
        ]);

        $originalPayerId = $payment->payer_person_id;

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.link-devotee', $payment), [
            'person_id' => $inactivePerson->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => __('portal.payments.inactive_or_invalid_person'),
        ]);

        // Non-existent ID
        $responseNotFound = $this->actingAs($this->treasurer)->postJson(route('portal.payments.link-devotee', $payment), [
            'person_id' => 9999999,
        ]);
        $responseNotFound->assertStatus(422);

        $payment->refresh();
        $this->assertEquals($originalPayerId, $payment->payer_person_id);
    }

    public function test_case_19_cross_event_person_pledge_manipulation_is_rejected(): void
    {
        // Another active event
        $durgaPuja = Event::create([
            'event_code' => 'EVT-DP-2026',
            'name_en' => 'Durga Puja 2026',
            'name_bn' => 'দুর্গাপূজা ২০২৬',
            'event_category_id' => EventCategory::first()->id,
            'event_year' => 2026,
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-24',
            'status' => Event::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        // Biplob's pledge for Durga Puja
        $durgaPledge = Pledge::create([
            'pledge_code' => 'PLG-DP-BIPLOB',
            'person_id' => $this->registeredDonor->id,
            'event_id' => $durgaPuja->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledge_date' => '2026-09-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);

        // Payment is for Kali Puja (event 1), but client requests Durga Puja pledge
        $payment = $this->createPublicSubmission([
            'event_id' => $this->event->id,
            'amount' => 2000.00,
            'transaction_reference' => 'TRXC19CROSSEVENT',
        ]);

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'pledge_id' => $durgaPledge->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('pledge_id');

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_PENDING, $payment->status);
        $this->assertEquals($this->event->id, $payment->event_id);
    }

    public function test_case_20_existing_financial_balances_and_receipt_integrity_remain_correct_after_failed_requests(): void
    {
        // 1. Initial baseline verification
        $validPayment = $this->createPublicSubmission(['amount' => 1000.00, 'transaction_reference' => 'TRXC20BASELINE']);
        $this->paymentService->verifyPayment($validPayment, $this->treasurer->id);

        $initialJournalCount = JournalEntry::count();
        $initialReceiptCount = Receipt::count();
        $initialMfsBalance = FinancialAccount::where('account_code', FinancialAccount::CODE_MFS)->first()->current_balance;

        // 2. Perform a barrage of invalid requests:
        $pendingPayment = $this->createPublicSubmission(['amount' => 500.00, 'transaction_reference' => 'TRXC20FAILURES']);

        // A. Duplicate verification of already verified payment
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $validPayment))->assertStatus(422);

        // B. Rejection without reason
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $pendingPayment), [
            'rejection_reason' => '',
        ])->assertStatus(422);

        // C. Cross-person pledge verification
        $stranger = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Stranger Devotee',
            'phone' => '01799001122',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);
        $strangerPledge = Pledge::create([
            'pledge_code' => 'PLG-STRANGER',
            'person_id' => $stranger->id,
            'event_id' => $this->event->id,
            'pledge_year' => 2026,
            'pledged_amount' => 3000.00,
            'pledge_date' => '2026-10-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $pendingPayment), [
            'pledge_id' => $strangerPledge->id,
        ])->assertStatus(422);

        // D. Link to inactive person
        $inactive = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Inactive Person',
            'phone' => '01711002233',
            'status' => 'inactive',
            'created_by' => $this->superAdmin->id,
        ]);
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.link-devotee', $pendingPayment), [
            'person_id' => $inactive->id,
        ])->assertStatus(422);

        // 3. Strict financial and audit invariance check:
        $this->assertEquals($initialJournalCount, JournalEntry::count());
        $this->assertEquals($initialReceiptCount, Receipt::count());
        $this->assertEquals($initialMfsBalance, FinancialAccount::where('account_code', FinancialAccount::CODE_MFS)->first()->current_balance);
        $this->assertEquals(CollectionPayment::STATUS_PENDING, $pendingPayment->fresh()->status);
    }

    public function test_reverse_public_payment_request_authorization_and_validation(): void
    {
        $verifiedPayment = $this->createPublicSubmission(['amount' => 1000.00, 'transaction_reference' => 'TRXREVERSETARGET']);
        $this->paymentService->verifyPayment($verifiedPayment, $this->treasurer->id);
        $verifiedPayment->refresh();

        $pendingPayment = $this->createPublicSubmission(['amount' => 500.00, 'transaction_reference' => 'TRXREVFAILPENDING']);

        // Check ReversePublicPaymentRequest directly:
        // 1. Devotee cannot authorize
        $req1 = new ReversePublicPaymentRequest;
        $req1->setUserResolver(fn () => $this->devotee);
        $this->assertFalse($req1->authorize());

        // 2. Treasurer and Super Admin can authorize
        $req2 = new ReversePublicPaymentRequest;
        $req2->setUserResolver(fn () => $this->treasurer);
        $this->assertTrue($req2->authorize());

        $req3 = new ReversePublicPaymentRequest;
        $req3->setUserResolver(fn () => $this->superAdmin);
        $this->assertTrue($req3->authorize());

        // 3. Rules require reversal_reason min 3 max 1000
        $rules = (new ReversePublicPaymentRequest)->rules();
        $this->assertArrayHasKey('reversal_reason', $rules);
        $this->assertContains('required', $rules['reversal_reason']);
        $this->assertContains('min:3', $rules['reversal_reason']);
        $this->assertContains('max:1000', $rules['reversal_reason']);

        // 4. Target payment must be verified
        $reqPending = ReversePublicPaymentRequest::create(
            '/portal/payments/'.$pendingPayment->id.'/reverse',
            'POST',
            ['reversal_reason' => 'Reason for reversing']
        );
        $reqPending->setRouteResolver(function () use ($pendingPayment) {
            $route = new Route('POST', 'portal/payments/{payment}/reverse', []);
            $route->parameters = ['payment' => $pendingPayment];

            return $route;
        });
        $reqPending->setUserResolver(fn () => $this->treasurer);
        $validator = validator($reqPending->all(), $reqPending->rules());
        $reqPending->withValidator($validator);
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('payment'));

        // 5. Verified payment passes validation
        $reqVerified = ReversePublicPaymentRequest::create(
            '/portal/payments/'.$verifiedPayment->id.'/reverse',
            'POST',
            ['reversal_reason' => 'Valid reason for reversing verified payment']
        );
        $reqVerified->setRouteResolver(function () use ($verifiedPayment) {
            $route = new Route('POST', 'portal/payments/{payment}/reverse', []);
            $route->parameters = ['payment' => $verifiedPayment];

            return $route;
        });
        $reqVerified->setUserResolver(fn () => $this->treasurer);
        $validatorVerified = validator($reqVerified->all(), $reqVerified->rules());
        $reqVerified->withValidator($validatorVerified);
        $this->assertFalse($validatorVerified->fails());
    }

    public function test_competing_verification_and_rejection_concurrency_protection(): void
    {
        $payment = $this->createPublicSubmission(['amount' => 1000.00, 'transaction_reference' => 'TRXCOMPETE01']);

        // First action verifies payment
        $responseVerify = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'First concurrent action: verification',
        ]);
        $responseVerify->assertStatus(200);

        // Competing action attempts to reject already processed payment
        $responseReject = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $payment), [
            'rejection_reason' => 'Competing rejection after verification acquired lock',
        ]);
        $responseReject->assertStatus(422);
        $responseReject->assertJson([
            'success' => false,
            'message' => __('portal.payments.already_processed'),
        ]);

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->status);
        $this->assertNotNull($payment->receipt);
        $this->assertNull($payment->rejected_by);
    }

    public function test_linking_devotee_disassociates_pledge_belonging_to_previous_person(): void
    {
        // Devotee A with pledge
        $personA = $this->registeredDonor;
        $pledgeA = $this->defaultPledge;

        // Payment initially linked to Person A with Pledge A
        $payment = $this->createPublicSubmission([
            'payer_person_id' => $personA->id,
            'pledge_id' => $pledgeA->id,
            'transaction_reference' => 'TRXREASSIGN01',
        ], $pledgeA);

        $this->assertEquals($pledgeA->id, $payment->pledge_id);

        // Devotee B
        $personB = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Mihir Sen',
            'phone' => '01788776655',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        // Link payment to Devotee B
        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.link-devotee', $payment), [
            'person_id' => $personB->id,
        ]);

        $response->assertStatus(200);
        $payment->refresh();

        // Must update payer to Person B
        $this->assertEquals($personB->id, $payment->payer_person_id);

        // Must disassociate Person A's pledge from payment
        $this->assertNull($payment->pledge_id);
        $this->assertEquals(CollectionPayment::TYPE_EVENT_DONATION, $payment->collection_type);

        // Active allocation must be voluntary event donation
        $alloc = $payment->allocations()->where('is_active', true)->first();
        $this->assertNotNull($alloc);
        $this->assertEquals(PaymentAllocation::TARGET_VOLUNTARY_EVENT, $alloc->target_type);
        $this->assertNull($alloc->pledge_id);
    }

    public function test_general_voluntary_donation_verification_without_event(): void
    {
        // Public payment for general temple fund (no event)
        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'general_donation',
            'event_id' => null,
            'payer_person_id' => $this->registeredDonor->id,
            'payment_date' => '2026-11-13',
            'amount' => 500.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRXGENERALVOL01',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                'pledge_id' => null,
                'event_id' => null,
                'amount' => 500.00,
            ],
        ], $this->superAdmin->id);

        $payment->update([
            'is_public_submission' => true,
            'receiver_account_id' => $this->receiverAccount->id,
            'pledge_id' => null,
            'event_id' => null,
            'sender_mobile' => '01711223344',
            'public_donor_name' => $this->registeredDonor->name_en,
            'public_donor_phone' => $this->registeredDonor->phone,
        ]);

        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'General temple voluntary donation verified',
        ]);

        $response->assertStatus(200);
        $payment->refresh();

        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->status);
        $this->assertEquals(CollectionPayment::TYPE_GENERAL_DONATION, $payment->collection_type);
        $this->assertNull($payment->event_id);
        $this->assertNull($payment->pledge_id);
        $this->assertNotNull($payment->receipt);

        // Journal Entry checks: balanced double-entry credited to General Temple Income (4010)
        $journal = JournalEntry::where('source_type', CollectionPayment::class)->where('source_id', $payment->id)->first();
        $this->assertNotNull($journal);
        $this->assertTrue($journal->isBalanced());
        $this->assertEquals(500.00, (float) $journal->total_debit);
        $this->assertEquals(500.00, (float) $journal->total_credit);
    }

    public function test_admin_payment_datatable_actions_include_receipt_actions_for_verified_payments(): void
    {
        $payment = $this->createVerifiedPublicPaymentWithReceipt();

        $response = $this->actingAs($this->treasurer)->getJson(route('portal.payments.index'));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $actionsHtml = $data[0]['actions'];
        $this->assertStringContainsString(route('receipts.show', $payment->receipt), $actionsHtml);
        $this->assertStringContainsString(route('receipts.print', $payment->receipt), $actionsHtml);
        $this->assertStringNotContainsString('btn-verify-payment', $actionsHtml);
        $this->assertStringNotContainsString('btn-reject-payment', $actionsHtml);
    }

    public function test_admin_payment_datatable_actions_never_display_receipt_actions_for_pending_or_rejected_payments(): void
    {
        // 1. Pending payment
        $pendingPayment = $this->createPendingPublicPayment();

        $response = $this->actingAs($this->treasurer)->getJson(route('portal.payments.index'));
        $response->assertStatus(200);
        $data = $response->json('data');
        $actionsHtml = $data[0]['actions'];

        $this->assertStringNotContainsString('/receipts/', $actionsHtml);
        $this->assertStringContainsString('btn-verify-payment', $actionsHtml);
        $this->assertStringContainsString('btn-reject-payment', $actionsHtml);

        // 2. Reject payment
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $pendingPayment), [
            'rejection_reason' => 'Invalid transaction details.',
        ])->assertStatus(200);

        $response = $this->actingAs($this->treasurer)->getJson(route('portal.payments.index'));
        $response->assertStatus(200);
        $data = $response->json('data');
        $actionsHtml = $data[0]['actions'];

        $this->assertStringNotContainsString('/receipts/', $actionsHtml);
        $this->assertStringNotContainsString('btn-verify-payment', $actionsHtml);
        $this->assertStringNotContainsString('btn-reject-payment', $actionsHtml);
    }

    public function test_admin_payment_datatable_actions_include_rich_verification_data_attributes(): void
    {
        $pendingPayment = $this->createPendingPublicPayment('TRXPENDING01');
        $pendingPayment->update([
            'payment_proof_path' => 'payment_proofs/test_sample.jpg',
        ]);

        $response = $this->actingAs($this->treasurer)->getJson(route('portal.payments.index'));
        $response->assertStatus(200);
        $data = $response->json('data');
        $actionsHtml = $data[0]['actions'];

        // Verify button data attributes
        $this->assertStringContainsString('data-donor="'.e($pendingPayment->payer_display_name).'"', $actionsHtml);
        $this->assertStringContainsString('data-event="'.e($pendingPayment->event->name).'"', $actionsHtml);
        $this->assertStringContainsString('data-method="Bkash"', $actionsHtml);
        $this->assertStringContainsString('data-trx="TRXPENDING01"', $actionsHtml);
        $this->assertStringContainsString('data-proof="'.e(route('portal.payments.proof', $pendingPayment)).'"', $actionsHtml);
        $this->assertStringContainsString('data-pledge-code="'.e($this->defaultPledge->pledge_code).'"', $actionsHtml);

        // Reject button data attributes
        $this->assertStringContainsString('data-code="'.e($pendingPayment->payment_code).'"', $actionsHtml);
        $this->assertStringContainsString('data-amount="1,000.00"', $actionsHtml);
    }

    public function test_admin_payment_verification_modal_does_not_contain_obsolete_guest_checkbox(): void
    {
        $response = $this->actingAs($this->treasurer)->get(route('portal.payments.index'));

        $response->assertStatus(200);
        $response->assertDontSee('create_guest_person');
        $response->assertDontSee('Create guest devotee profile automatically');
        $response->assertSee('modalVerifyPledgeBox');
        $response->assertSee('modalVerifyVoluntaryBox');
        $response->assertSee('modalVerifyProofBox');
        $response->assertSee('btnSubmitVerifySpinner');
        $response->assertSee('btnSubmitRejectSpinner');
    }

    public function test_payment_details_view_shows_receipt_actions_only_when_receipt_exists(): void
    {
        $verifiedPayment = $this->createVerifiedPublicPaymentWithReceipt();

        $response = $this->actingAs($this->treasurer)->get(route('portal.payments.show', $verifiedPayment));
        $response->assertStatus(200);
        $response->assertSee(route('receipts.show', $verifiedPayment->receipt));
        $response->assertSee(route('receipts.print', $verifiedPayment->receipt));
        $response->assertSee(route('receipts.download', $verifiedPayment->receipt));

        // Pending payment should not show receipt actions
        $pendingPayment = $this->createPendingPublicPayment();
        $response = $this->actingAs($this->treasurer)->get(route('portal.payments.show', $pendingPayment));
        $response->assertStatus(200);
        $response->assertDontSee('print_receipts');
        $response->assertDontSee(route('receipts.show', 1));
    }

    public function test_payment_details_view_displays_voluntary_event_vs_voluntary_general_donation_cards(): void
    {
        // 1. Voluntary event donation
        $eventPayment = $this->paymentService->submitPayment([
            'collection_type' => 'voluntary_event',
            'event_id' => $this->event->id,
            'payer_person_id' => $this->registeredDonor->id,
            'payment_date' => '2026-11-13',
            'amount' => 500.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRXVOLEVT01',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT,
                'pledge_id' => null,
                'event_id' => $this->event->id,
                'amount' => 500.00,
            ],
        ], $this->superAdmin->id);
        $eventPayment->update(['is_public_submission' => true, 'receiver_account_id' => $this->receiverAccount->id]);

        $response = $this->actingAs($this->treasurer)->get(route('portal.payments.show', $eventPayment));
        $response->assertStatus(200);
        $response->assertSee(__('portal.payments.voluntary_event_donation'));
        $response->assertSee(__('portal.payments.voluntary_donation_help'));

        // 2. Voluntary general donation
        $generalPayment = $this->paymentService->submitPayment([
            'collection_type' => 'general_donation',
            'event_id' => null,
            'payer_person_id' => $this->registeredDonor->id,
            'payment_date' => '2026-11-13',
            'amount' => 500.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRXVOLGEN01',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                'pledge_id' => null,
                'event_id' => null,
                'amount' => 500.00,
            ],
        ], $this->superAdmin->id);
        $generalPayment->update(['is_public_submission' => true, 'receiver_account_id' => $this->receiverAccount->id]);

        $response = $this->actingAs($this->treasurer)->get(route('portal.payments.show', $generalPayment));
        $response->assertStatus(200);
        $response->assertSee(__('portal.payments.voluntary_general_donation'));
        $response->assertSee(__('portal.payments.voluntary_general_donation_help'));
    }

    public function test_payment_details_view_devotee_linking_ui_state(): void
    {
        // 1. Pending payment displays devotee linking form
        $pendingPayment = $this->createPendingPublicPayment();
        $response = $this->actingAs($this->treasurer)->get(route('portal.payments.show', $pendingPayment));
        $response->assertStatus(200);
        $response->assertSee('id="linkDevoteeForm"', false);
        $response->assertSee('id="linkPersonSelect"', false);

        // 2. Verified payment hides devotee linking form
        $verifiedPayment = $this->createVerifiedPublicPaymentWithReceipt();
        $response = $this->actingAs($this->treasurer)->get(route('portal.payments.show', $verifiedPayment));
        $response->assertStatus(200);
        $response->assertDontSee('id="linkDevoteeForm"', false);

        // 3. Rejected payment hides devotee linking form
        $rejectedPayment = $this->createPendingPublicPayment();
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $rejectedPayment), [
            'rejection_reason' => 'Invalid transaction reference.',
        ])->assertStatus(200);

        $response = $this->actingAs($this->treasurer)->get(route('portal.payments.show', $rejectedPayment));
        $response->assertStatus(200);
        $response->assertDontSee('id="linkDevoteeForm"', false);
    }

    public function test_localization_parity_for_step_3_new_ui_keys(): void
    {
        $en = include resource_path('../lang/en/portal.php');
        $bn = include resource_path('../lang/bn/portal.php');

        $keys = [
            'voluntary_event_donation',
            'voluntary_general_donation',
            'voluntary_donation_help',
            'voluntary_general_donation_help',
            'voluntary_verification_info',
            'download_proof',
            'download_receipt',
            'change_linked_devotee',
            'current_linked_devotee',
        ];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $en['payments'], "Missing EN key: payments.{$key}");
            $this->assertArrayHasKey($key, $bn['payments'], "Missing BN key: payments.{$key}");
            $this->assertNotEmpty($en['payments'][$key], "Empty EN key: payments.{$key}");
            $this->assertNotEmpty($bn['payments'][$key], "Empty BN key: payments.{$key}");
        }
    }

    public function test_authorized_user_can_reverse_verified_payment_via_route(): void
    {
        $payment = $this->createVerifiedPublicPaymentWithReceipt('TRXREVROUTE01');
        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->status);

        // 1. JSON / AJAX request returns success
        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $payment), [
            'reversal_reason' => 'Administrative reversal due to donor request.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'payment' => [
                'id' => $payment->id,
                'status' => CollectionPayment::STATUS_REVERSED,
            ],
        ]);

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_REVERSED, $payment->status);
        $this->assertEquals($this->treasurer->id, $payment->reversed_by);
        $this->assertNotNull($payment->reversed_at);
        $this->assertEquals('Administrative reversal due to donor request.', $payment->reversal_reason);

        // 2. Standard redirect request works as super_admin on another verified payment
        $payment2 = $this->createVerifiedPublicPaymentWithReceipt('TRXREVROUTE02');
        $response2 = $this->actingAs($this->superAdmin)->post(route('portal.payments.reverse', $payment2), [
            'reversal_reason' => 'Super admin reversal verification test.',
        ]);

        $response2->assertRedirect(route('portal.payments.show', $payment2));
        $response2->assertSessionHas('success');
        $this->assertEquals(CollectionPayment::STATUS_REVERSED, $payment2->fresh()->status);
    }

    public function test_unauthorized_user_cannot_reverse_payment(): void
    {
        $payment = $this->createVerifiedPublicPaymentWithReceipt('TRXREVUNAUTH01');

        // Devotee gets 403 Forbidden
        $response = $this->actingAs($this->devotee)->postJson(route('portal.payments.reverse', $payment), [
            'reversal_reason' => 'Unauthorized attempt by devotee.',
        ]);
        $response->assertStatus(403);
        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->fresh()->status);

        // Temple Admin (lacks reverse permission) gets 403 Forbidden
        $response2 = $this->actingAs($this->templeAdmin)->postJson(route('portal.payments.reverse', $payment), [
            'reversal_reason' => 'Unauthorized attempt by temple admin.',
        ]);
        $response2->assertStatus(403);
        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->fresh()->status);

        // Guest redirects to login
        $this->app['auth']->forgetGuards();
        $responseGuest = $this->post(route('portal.payments.reverse', $payment), [
            'reversal_reason' => 'Guest attempt.',
        ]);
        $responseGuest->assertRedirect(route('login'));
    }

    public function test_pending_or_rejected_or_reversed_payment_cannot_be_reversed(): void
    {
        // 1. Pending payment cannot be reversed
        $pendingPayment = $this->createPendingPublicPayment('TRXREVFAILPENDING01');
        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $pendingPayment), [
            'reversal_reason' => 'Attempting to reverse pending payment.',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['payment']);
        $this->assertEquals(CollectionPayment::STATUS_PENDING, $pendingPayment->fresh()->status);

        // 2. Rejected payment cannot be reversed
        $rejectedPayment = $this->createPendingPublicPayment('TRXREVFAILREJ01');
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $rejectedPayment), [
            'rejection_reason' => 'Payment invalid.',
        ])->assertStatus(200);

        $response2 = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $rejectedPayment), [
            'reversal_reason' => 'Attempting to reverse rejected payment.',
        ]);
        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['payment']);
        $this->assertEquals(CollectionPayment::STATUS_REJECTED, $rejectedPayment->fresh()->status);

        // 3. Already reversed payment cannot be reversed again
        $verifiedPayment = $this->createVerifiedPublicPaymentWithReceipt('TRXREVFAILREV01');
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $verifiedPayment), [
            'reversal_reason' => 'First reversal.',
        ])->assertStatus(200);

        $response3 = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $verifiedPayment), [
            'reversal_reason' => 'Second reversal attempt.',
        ]);
        $response3->assertStatus(422);
        $response3->assertJsonValidationErrors(['payment']);
    }

    public function test_reversal_requires_valid_reason_between_3_and_1000_chars(): void
    {
        $payment = $this->createVerifiedPublicPaymentWithReceipt('TRXREVVALIDATION01');

        // Missing reason
        $response1 = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $payment), []);
        $response1->assertStatus(422);
        $response1->assertJsonValidationErrors(['reversal_reason']);

        // Reason too short (< 3 chars)
        $response2 = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $payment), [
            'reversal_reason' => 'ab',
        ]);
        $response2->assertStatus(422);
        $response2->assertJsonValidationErrors(['reversal_reason']);

        // Whitespace only
        $response3 = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $payment), [
            'reversal_reason' => '   ',
        ]);
        $response3->assertStatus(422);
        $response3->assertJsonValidationErrors(['reversal_reason']);

        // Payment remains verified
        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->fresh()->status);
    }

    public function test_reversal_creates_balanced_compensating_journal_entry_and_preserves_original_journal(): void
    {
        $payment = $this->createVerifiedPublicPaymentWithReceipt('TRXREVJOURNAL01');
        $payment->refresh();

        $originalJournal = $payment->journalEntry;
        $this->assertNotNull($originalJournal);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $originalJournal->status);
        $originalLines = $originalJournal->lines;
        $this->assertNotEmpty($originalLines);

        // Perform reversal
        $response = $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $payment), [
            'reversal_reason' => 'Accounting correction required.',
        ]);
        $response->assertStatus(200);

        // Original journal still exists, marked as reversed
        $originalJournal->refresh();
        $this->assertEquals(JournalEntry::STATUS_REVERSED, $originalJournal->status);

        // Compensating reversal journal entry exists
        $reversalJournal = JournalEntry::where('reversed_journal_id', $originalJournal->id)->first();
        $this->assertNotNull($reversalJournal);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $reversalJournal->status);
        $this->assertTrue($reversalJournal->isBalanced());
        $this->assertEquals((float) $originalJournal->total_debit, (float) $reversalJournal->total_credit);
        $this->assertEquals((float) $originalJournal->total_credit, (float) $reversalJournal->total_debit);

        // Lines are inverted
        $this->assertEquals($originalLines->count(), $reversalJournal->lines->count());
        foreach ($originalLines as $origLine) {
            $matchingInverse = $reversalJournal->lines
                ->where('financial_account_id', $origLine->financial_account_id)
                ->first();
            $this->assertNotNull($matchingInverse);
            $this->assertEquals((float) $origLine->debit, (float) $matchingInverse->credit);
            $this->assertEquals((float) $origLine->credit, (float) $matchingInverse->debit);
        }
    }

    public function test_reversal_restores_pledge_outstanding_balance(): void
    {
        // Default pledge has 5000 pledged amount, 0 paid initially
        $pledge = $this->defaultPledge->fresh();
        $this->assertEquals(5000.00, (float) $pledge->pledged_amount);
        $this->assertEquals(0.00, (float) $pledge->verified_paid_amount);
        $this->assertEquals(5000.00, (float) $pledge->outstanding_amount);
        $this->assertEquals(Pledge::STATUS_UNPAID, $pledge->status);

        // Verify a 2000 payment against the pledge
        $payment = $this->createPublicSubmission(['amount' => 2000.00, 'transaction_reference' => 'TRXREVPLEDGE01'], $pledge);
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'Verified pledge payment',
        ])->assertStatus(200);

        $pledge->refresh();
        $this->assertEquals(2000.00, (float) $pledge->verified_paid_amount);
        $this->assertEquals(3000.00, (float) $pledge->outstanding_amount);
        $this->assertEquals(Pledge::STATUS_PARTIAL, $pledge->status);

        // Reverse the payment
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $payment), [
            'reversal_reason' => 'Donor cancelled pledge contribution.',
        ])->assertStatus(200);

        // Assert pledge outstanding amount is restored
        $pledge->refresh();
        $this->assertEquals(0.00, (float) $pledge->verified_paid_amount);
        $this->assertEquals(5000.00, (float) $pledge->outstanding_amount);
        $this->assertEquals(Pledge::STATUS_UNPAID, $pledge->status);

        // Payment allocation is deactivated
        $allocation = $payment->allocations()->first();
        $this->assertFalse((bool) $allocation->fresh()->is_active);
    }

    public function test_voluntary_event_and_general_donation_reversal_leaves_pledges_untouched(): void
    {
        $pledge = $this->defaultPledge->fresh();
        $this->assertEquals(0.00, (float) $pledge->verified_paid_amount);
        $this->assertEquals(5000.00, (float) $pledge->outstanding_amount);

        // 1. Voluntary event payment
        $eventPayment = $this->paymentService->submitPayment([
            'collection_type' => 'voluntary_event',
            'event_id' => $this->event->id,
            'payer_person_id' => $this->registeredDonor->id,
            'payment_date' => '2026-11-13',
            'amount' => 500.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRXVOLEVTREV01',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT,
                'pledge_id' => null,
                'event_id' => $this->event->id,
                'amount' => 500.00,
            ],
        ], $this->superAdmin->id);
        $eventPayment->update(['is_public_submission' => true, 'receiver_account_id' => $this->receiverAccount->id]);

        $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $eventPayment), [
            'verification_note' => 'Verified voluntary event',
        ])->assertStatus(200);

        // 2. Voluntary general payment
        $genPayment = $this->paymentService->submitPayment([
            'collection_type' => 'general_donation',
            'event_id' => null,
            'payer_person_id' => $this->registeredDonor->id,
            'payment_date' => '2026-11-13',
            'amount' => 300.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRXVOLGENREV01',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                'pledge_id' => null,
                'event_id' => null,
                'amount' => 300.00,
            ],
        ], $this->superAdmin->id);
        $genPayment->update(['is_public_submission' => true, 'receiver_account_id' => $this->receiverAccount->id]);

        $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $genPayment), [
            'verification_note' => 'Verified voluntary general',
        ])->assertStatus(200);

        // Reverse voluntary event payment
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $eventPayment), [
            'reversal_reason' => 'Voluntary event reversal test.',
        ])->assertStatus(200);

        // Reverse voluntary general payment
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $genPayment), [
            'reversal_reason' => 'Voluntary general reversal test.',
        ])->assertStatus(200);

        // Pledge remains completely untouched
        $pledge->refresh();
        $this->assertEquals(0.00, (float) $pledge->verified_paid_amount);
        $this->assertEquals(5000.00, (float) $pledge->outstanding_amount);
        $this->assertEquals(Pledge::STATUS_UNPAID, $pledge->status);
    }

    public function test_reversal_preserves_original_receipt_and_marks_it_voided(): void
    {
        $payment = $this->createVerifiedPublicPaymentWithReceipt('TRXREVRECEIPT01');
        $receipt = $payment->receipt;
        $this->assertNotNull($receipt);
        $this->assertEquals(Receipt::STATUS_VALID, $receipt->status);

        // Perform reversal
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $payment), [
            'reversal_reason' => 'MFS transaction was fraudulent.',
        ])->assertStatus(200);

        // Receipt is preserved in database, marked VOIDED
        $receipt->refresh();
        $this->assertEquals(Receipt::STATUS_VOIDED, $receipt->status);
        $this->assertEquals($this->treasurer->id, $receipt->voided_by);
        $this->assertEquals('MFS transaction was fraudulent.', $receipt->void_reason);
        $this->assertNotNull($receipt->voided_at);
    }

    public function test_reversed_payment_details_view_renders_reversal_audit_and_compensating_journal(): void
    {
        $payment = $this->createVerifiedPublicPaymentWithReceipt('TRXREVSHOW01');

        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $payment), [
            'reversal_reason' => 'Detailed view reversal inspection reason.',
        ])->assertStatus(200);

        $response = $this->actingAs($this->treasurer)->get(route('portal.payments.show', $payment));
        $response->assertStatus(200);

        // Reversal information alert displayed
        $response->assertSee(__('donations.status_reversed'));
        $response->assertSee('Detailed view reversal inspection reason.');
        $response->assertSee($this->treasurer->name);

        // Receipt shown as voided
        $response->assertSee(__('donations.receipt_status_voided'));

        // Compensating reversal ledger voucher displayed
        $response->assertSee('Compensating Reversal Ledger Voucher');

        // Action buttons: Reverse button hidden since payment is already reversed
        $response->assertDontSee('id="reversePaymentModal"', false);
    }

    public function test_datatable_actions_column_includes_reverse_button_only_for_verified_posted_payments(): void
    {
        // 1. Pending payment
        $pendingPayment = $this->createPendingPublicPayment('TRXDTREV01');

        // 2. Verified payment with posted journal
        $verifiedPayment = $this->createVerifiedPublicPaymentWithReceipt('TRXDTREV02');

        // 3. Rejected payment
        $rejectedPayment = $this->createPendingPublicPayment('TRXDTREV03');
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reject', $rejectedPayment), [
            'rejection_reason' => 'Rejected for test.',
        ])->assertStatus(200);

        // 4. Reversed payment
        $reversedPayment = $this->createVerifiedPublicPaymentWithReceipt('TRXDTREV04');
        $this->actingAs($this->treasurer)->postJson(route('portal.payments.reverse', $reversedPayment), [
            'reversal_reason' => 'Reversed for test.',
        ])->assertStatus(200);

        // Fetch DataTables JSON as Treasurer
        $response = $this->actingAs($this->treasurer)->getJson(route('portal.payments.index'));
        $response->assertStatus(200);
        $data = collect($response->json('data'));

        // Verified payment row contains btn-reverse-payment and reverse URL
        $verifiedRow = $data->firstWhere(fn ($row) => str_contains($row['transaction_details'], 'TRXDTREV02'));
        $this->assertNotNull($verifiedRow);
        $this->assertStringContainsString('btn-reverse-payment', $verifiedRow['actions']);
        $this->assertStringContainsString(route('portal.payments.reverse', $verifiedPayment), $verifiedRow['actions']);
        $this->assertStringContainsString('data-receipt="'.e($verifiedPayment->receipt->receipt_number).'"', $verifiedRow['actions']);

        // Pending payment row does NOT contain btn-reverse-payment
        $pendingRow = $data->firstWhere(fn ($row) => str_contains($row['transaction_details'], 'TRXDTREV01'));
        $this->assertNotNull($pendingRow);
        $this->assertStringNotContainsString('btn-reverse-payment', $pendingRow['actions']);

        // Rejected payment row does NOT contain btn-reverse-payment
        $rejectedRow = $data->firstWhere(fn ($row) => str_contains($row['transaction_details'], 'TRXDTREV03'));
        $this->assertNotNull($rejectedRow);
        $this->assertStringNotContainsString('btn-reverse-payment', $rejectedRow['actions']);

        // Reversed payment row does NOT contain btn-reverse-payment
        $reversedRow = $data->firstWhere(fn ($row) => str_contains($row['transaction_details'], 'TRXDTREV04'));
        $this->assertNotNull($reversedRow);
        $this->assertStringNotContainsString('btn-reverse-payment', $reversedRow['actions']);

        // User without reversal permission (e.g. templeAdmin) never sees btn-reverse-payment even on verified payments
        $unauthResponse = $this->actingAs($this->templeAdmin)->getJson(route('portal.payments.index'));
        $unauthResponse->assertStatus(200);
        $unauthData = collect($unauthResponse->json('data'));
        $unauthVerifiedRow = $unauthData->firstWhere(fn ($row) => str_contains($row['transaction_details'], 'TRXDTREV02'));
        $this->assertNotNull($unauthVerifiedRow);
        $this->assertStringNotContainsString('btn-reverse-payment', $unauthVerifiedRow['actions']);
    }

    public function test_concurrency_lock_for_update_prevents_double_reversal(): void
    {
        $payment = $this->createVerifiedPublicPaymentWithReceipt('TRXCONCURREV01');

        // First reversal succeeds
        $reversed = $this->paymentService->reversePayment($payment, $this->treasurer->id, 'First legitimate reversal.');
        $this->assertEquals(CollectionPayment::STATUS_REVERSED, $reversed->status);

        // Immediate subsequent attempt on the same payment fails with ValidationException
        $this->expectException(ValidationException::class);
        $this->paymentService->reversePayment($payment, $this->treasurer->id, 'Concurrent second reversal.');
    }

    public function test_localization_parity_for_step_4_reversal_ui_keys(): void
    {
        $en = include resource_path('../lang/en/portal.php');
        $bn = include resource_path('../lang/bn/portal.php');

        $keys = [
            'reversal_warning_title',
            'reversal_warning_body',
            'reversal_pledge_restoration_help',
            'reversal_voluntary_help',
            'reversal_receipt_voided_help',
        ];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $en['payments'], "Missing EN key: payments.{$key}");
            $this->assertArrayHasKey($key, $bn['payments'], "Missing BN key: payments.{$key}");
            $this->assertNotEmpty($en['payments'][$key], "Empty EN key: payments.{$key}");
            $this->assertNotEmpty($bn['payments'][$key], "Empty BN key: payments.{$key}");
        }
    }

    private function createPendingPublicPayment(?string $trx = null): CollectionPayment
    {
        static $pendingCounter = 100;
        $pendingCounter++;
        $trxRef = $trx ?: ('TRXPENDING'.$pendingCounter);

        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'event_id' => $this->event->id,
            'payer_person_id' => $this->registeredDonor->id,
            'pledge_id' => $this->defaultPledge->id,
            'payment_date' => '2026-11-13',
            'amount' => 1000.00,
            'payment_method' => 'bkash',
            'transaction_reference' => $trxRef,
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_PLEDGE,
                'pledge_id' => $this->defaultPledge->id,
                'event_id' => $this->event->id,
                'amount' => 1000.00,
            ],
        ], $this->superAdmin->id);

        $payment->update([
            'is_public_submission' => true,
            'pledge_id' => $this->defaultPledge->id,
            'receiver_account_id' => $this->receiverAccount->id,
            'sender_mobile' => '01711000000',
            'public_donor_name' => $this->registeredDonor->name_en,
            'public_donor_phone' => $this->registeredDonor->phone,
        ]);

        return $payment;
    }

    private function createVerifiedPublicPaymentWithReceipt(?string $trx = null): CollectionPayment
    {
        $payment = $this->createPendingPublicPayment($trx);

        $this->actingAs($this->treasurer)->postJson(route('portal.payments.verify', $payment), [
            'verification_note' => 'Approved',
        ])->assertStatus(200);

        return $payment->fresh(['receipt', 'event', 'payerPerson']);
    }
}
