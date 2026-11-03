<?php

namespace Tests\Feature\PublicPortal;

use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\ReceiverAccount;
use App\Models\User;
use App\Services\CollectionPaymentService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
}
