<?php

namespace Tests\Feature\PublicPortal;

use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\Receipt;
use App\Models\ReceiverAccount;
use App\Models\User;
use App\Services\EventCollectionReportService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicPaymentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $donorUser;

    protected Person $donorPerson;

    protected User $unlinkedUser;

    protected Event $event;

    protected ReceiverAccount $bkashReceiver;

    protected ReceiverAccount $bankReceiver;

    protected Pledge $pledge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);
        Storage::fake('public');
        Storage::fake('local');

        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->assignRole('super_admin');

        // Donor Person in directory
        $this->donorPerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Subhashish Roy',
            'name_bn' => 'সুভাশীষ রায়',
            'phone' => '01711998877',
            'email' => 'subhashish@example.com',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        // Authenticated Devotee User with matching phone
        $this->donorUser = User::factory()->create([
            'name' => 'Subhashish Roy',
            'phone' => '01711998877',
            'email' => 'subhashish@example.com',
            'status' => 'active',
        ]);
        $this->donorUser->assignRole('devotee');

        // Unlinked User with no matching person record
        $this->unlinkedUser = User::factory()->create([
            'name' => 'Unlinked Visitor',
            'phone' => '01999000111',
            'email' => 'unlinked@example.com',
            'status' => 'active',
        ]);

        $category = EventCategory::first();
        $this->event = Event::create([
            'event_code' => 'EVT-DURGA-2026',
            'slug' => 'durga-puja-2026',
            'name_en' => 'Durga Puja 2026',
            'name_bn' => 'শারদীয় দুর্গাপূজা ২০২৬',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-20',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by' => $this->superAdmin->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $mfsAccount = FinancialAccount::where('account_code', FinancialAccount::CODE_MFS)->first();
        $bankAccount = FinancialAccount::where('account_code', FinancialAccount::CODE_BANK)->first();

        $this->bkashReceiver = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BKASH,
            'account_name' => 'Sri Sri Mandir bKash Merchant',
            'account_number' => '01700112233',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'financial_account_id' => $mfsAccount?->id,
            'event_id' => $this->event->id,
            'is_active' => true,
            'is_verified' => true,
            'display_order' => 1,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->bankReceiver = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BANK_TRANSFER,
            'account_name' => 'Sri Sri Mandir General Fund',
            'account_number' => '1029384756',
            'account_type' => ReceiverAccount::TYPE_CURRENT,
            'bank_name' => 'Sonali Bank PLC',
            'branch_name' => 'Dhaka Main Branch',
            'routing_number' => '200270111',
            'financial_account_id' => $bankAccount?->id,
            'is_active' => true,
            'is_verified' => true,
            'display_order' => 2,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->pledge = Pledge::create([
            'pledge_code' => 'PLG-DP-2026-001',
            'person_id' => $this->donorPerson->id,
            'event_id' => $this->event->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledge_date' => '2026-10-01',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_payment_submission_form(): void
    {
        $response = $this->get(route('public.events.pay', $this->event->slug));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_linked_devotee_profile_is_denied(): void
    {
        $response = $this->actingAs($this->unlinkedUser)->get(route('public.events.pay', $this->event->slug));
        $response->assertRedirect(route('public.events.show', $this->event->slug));
        $response->assertSessionHas('error', __('portal.public.account_not_linked_error'));

        $postResponse = $this->actingAs($this->unlinkedUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX12345678',
        ]);
        $postResponse->assertStatus(403);
    }

    public function test_registered_donor_with_linked_profile_can_view_payment_form(): void
    {
        $response = $this->actingAs($this->donorUser)->get(route('public.events.pay', $this->event->slug));
        $response->assertStatus(200);
        $response->assertSee($this->event->name);
        $response->assertSee($this->donorPerson->person_code);
        $response->assertSee($this->donorPerson->name_en);
        $response->assertSee($this->bkashReceiver->account_number);
        $response->assertSee($this->pledge->pledge_code);
    }

    public function test_payment_submission_denied_for_closed_event(): void
    {
        $this->event->update(['status' => Event::STATUS_CLOSED]);

        $response = $this->actingAs($this->donorUser)->get(route('public.events.pay', $this->event->slug));
        $response->assertRedirect(route('public.events.show', $this->event->slug));
        $response->assertSessionHas('error', __('portal.public.event_not_open_for_payment'));

        $postResponse = $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX12345678',
        ]);
        $postResponse->assertStatus(404);
    }

    public function test_registered_donor_can_submit_mfs_payment_successfully(): void
    {
        $postData = [
            'pledge_id' => $this->pledge->id,
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => 4500.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX99887766',
            'payment_notes' => 'Advance Puja payment via bKash app',
        ];

        $response = $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), $postData);

        $response->assertRedirect(route('public.payments.history'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('collection_payments', [
            'payer_person_id' => $this->donorPerson->id,
            'event_id' => $this->event->id,
            'pledge_id' => $this->pledge->id,
            'receiver_account_id' => $this->bkashReceiver->id,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRX99887766',
            'sender_mobile' => '01711998877',
            'amount' => 4500.00,
            'status' => CollectionPayment::STATUS_PENDING,
            'is_public_submission' => true,
        ]);

        $payment = CollectionPayment::where('transaction_reference', 'TRX99887766')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(CollectionPayment::STATUS_PENDING, $payment->status);

        $this->assertDatabaseHas('payment_allocations', [
            'collection_payment_id' => $payment->id,
            'pledge_id' => $this->pledge->id,
            'amount' => 4500.00,
            'is_active' => true,
        ]);
    }

    public function test_financial_ledger_invariant_on_public_submission(): void
    {
        $initialJournalEntriesCount = JournalEntry::count();
        $initialReceiptsCount = Receipt::count();
        $initialPledgePaid = (float) $this->pledge->fresh()->verified_paid_amount;

        $reportService = app(EventCollectionReportService::class);
        $initialSummary = $reportService->getPublicSummary($this->event);
        $initialVerifiedCollection = $initialSummary['total_verified_collections'];

        // Submit public payment
        $response = $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'pledge_id' => $this->pledge->id,
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => 5000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX_LEDGER_TEST_1',
        ]);
        $response->assertRedirect(route('public.payments.history'));

        // Strict Financial Ledger Invariant Assertions:
        // 1. Journal entries MUST NOT increase
        $this->assertEquals($initialJournalEntriesCount, JournalEntry::count(), 'Journal entries must NOT be created on public submission.');

        // 2. Receipts MUST NOT be created
        $this->assertEquals($initialReceiptsCount, Receipt::count(), 'Receipts must NOT be issued on public submission.');

        // 3. Pledge verified paid amount MUST remain unchanged
        $this->assertEquals($initialPledgePaid, (float) $this->pledge->fresh()->verified_paid_amount, 'Pledge verified paid amount must NOT change on unverified submission.');

        // 4. Event verified collections summary MUST remain unchanged
        $newSummary = $reportService->getPublicSummary($this->event->fresh());
        $this->assertEquals($initialVerifiedCollection, $newSummary['total_verified_collections'], 'Public verified collections must NOT include pending submissions.');
    }

    public function test_method_aware_duplicate_transaction_reference_is_rejected(): void
    {
        // First submission with bKash and TRX_DUP_123
        $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX_DUP_123',
        ]);

        // Attempt second submission with same bKash method and same TRX
        $secondResponse = $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => 2000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX_DUP_123',
        ]);

        $secondResponse->assertSessionHasErrors(['transaction_reference']);
    }

    public function test_validation_rules_for_amount_and_future_date(): void
    {
        // Negative amount
        $responseNegative = $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => -100.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX_VAL_1',
        ]);
        $responseNegative->assertSessionHasErrors(['amount']);

        // Future date
        $responseFuture = $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => 500.00,
            'payment_date' => now()->addDays(5)->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX_VAL_2',
        ]);
        $responseFuture->assertSessionHasErrors(['payment_date']);
    }

    public function test_payment_proof_file_upload_is_stored(): void
    {
        $fakeFile = UploadedFile::fake()->image('payment_receipt.jpg', 600, 600);

        $response = $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => 3000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX_PROOF_TEST',
            'payment_proof' => $fakeFile,
        ]);

        $response->assertRedirect(route('public.payments.history'));

        $payment = CollectionPayment::where('transaction_reference', 'TRX_PROOF_TEST')->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->payment_proof_path);
        // Ensure proof is stored on private 'local' disk, NOT public disk
        Storage::disk('local')->assertExists($payment->payment_proof_path);
        Storage::disk('public')->assertMissing($payment->payment_proof_path);
    }

    public function test_donor_payment_history_shows_only_own_submissions(): void
    {
        // Donor A payment
        $paymentA = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0001',
            'payer_person_id' => $this->donorPerson->id,
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->event->id,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 1500.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRX_DONOR_A',
            'status' => CollectionPayment::STATUS_PENDING,
            'is_public_submission' => true,
            'submitted_by' => $this->donorUser->id,
            'created_by' => $this->donorUser->id,
        ]);

        // Donor B Person & User
        $personB = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Other Devotee',
            'phone' => '01888000999',
            'status' => 'active',
        ]);
        $userB = User::factory()->create([
            'phone' => '01888000999',
            'status' => 'active',
        ]);

        $paymentB = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0002',
            'payer_person_id' => $personB->id,
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->event->id,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 7000.00,
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'TRX_DONOR_B',
            'status' => CollectionPayment::STATUS_PENDING,
            'is_public_submission' => true,
            'submitted_by' => $userB->id,
            'created_by' => $userB->id,
        ]);

        // Donor A visits history
        $responseA = $this->actingAs($this->donorUser)->get(route('public.payments.history'));
        $responseA->assertStatus(200);
        $responseA->assertSee($paymentA->payment_code);
        $responseA->assertSee('TRX_DONOR_A');
        $responseA->assertDontSee($paymentB->payment_code);
        $responseA->assertDontSee('TRX_DONOR_B');

        // Donor B visits history
        $responseB = $this->actingAs($userB)->get(route('public.payments.history'));
        $responseB->assertStatus(200);
        $responseB->assertSee($paymentB->payment_code);
        $responseB->assertSee('TRX_DONOR_B');
        $responseB->assertDontSee($paymentA->payment_code);
        $responseB->assertDontSee('TRX_DONOR_A');
    }

    public function test_idor_protection_for_donor_receipt_access(): void
    {
        // Create verified payment with receipt for Donor A
        $paymentA = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0003',
            'payer_person_id' => $this->donorPerson->id,
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->event->id,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 2500.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRX_RECEIPT_TEST',
            'status' => CollectionPayment::STATUS_VERIFIED,
            'is_public_submission' => true,
            'submitted_by' => $this->donorUser->id,
            'created_by' => $this->donorUser->id,
        ]);

        $receipt = Receipt::create([
            'receipt_number' => 'REC-2026-0001',
            'collection_payment_id' => $paymentA->id,
            'receipt_year' => 2026,
            'issued_at' => now(),
            'issued_by' => $this->superAdmin->id,
            'status' => Receipt::STATUS_VALID,
        ]);

        // Another donor User
        $personB = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Unauthorized Donor',
            'phone' => '01666777888',
            'status' => 'active',
        ]);
        $userB = User::factory()->create([
            'phone' => '01666777888',
            'status' => 'active',
        ]);

        // Donor B attempts to access Donor A's receipt -> 403 Forbidden
        $responseUnauthorized = $this->actingAs($userB)->get(route('public.payments.receipt', $receipt->receipt_number));
        $responseUnauthorized->assertStatus(403);

        // Donor A accesses own receipt -> 200 OK
        $responseAuthorized = $this->actingAs($this->donorUser)->get(route('public.payments.receipt', $receipt->receipt_number));
        $responseAuthorized->assertStatus(200);
        $responseAuthorized->assertSee($receipt->receipt_number);
    }

    public function test_admin_can_verify_and_post_submitted_public_payment(): void
    {
        // 1. Donor submits public payment
        $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'pledge_id' => $this->pledge->id,
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => 3000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX_VERIFY_WORKFLOW',
        ]);

        $payment = CollectionPayment::where('transaction_reference', 'TRX_VERIFY_WORKFLOW')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(CollectionPayment::STATUS_PENDING, $payment->status);

        // 2. Admin verifies payment via existing PublicPaymentController::verify endpoint
        $verifyResponse = $this->actingAs($this->superAdmin)->post(route('portal.payments.verify', $payment->id), [
            'verification_note' => 'Confirmed in bKash statement',
            'person_id' => $this->donorPerson->id,
            'pledge_id' => $this->pledge->id,
        ]);

        $verifyResponse->assertRedirect();

        // 3. Payment is now verified and financial transactions are posted
        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->status);
        $this->assertNotNull($payment->verified_at);
        $this->assertEquals($this->superAdmin->id, $payment->verified_by);

        // 4. Receipt is issued
        $this->assertNotNull($payment->receipt);

        // 5. Journal entry is posted to finance ledger
        $this->assertNotNull($payment->finance_post_reference);
        $this->assertDatabaseHas('journal_entries', [
            'journal_number' => $payment->finance_post_reference,
            'status' => JournalEntry::STATUS_POSTED,
        ]);

        // 6. Pledge paid amount is now updated
        $this->assertEquals(3000.00, (float) $this->pledge->fresh()->verified_paid_amount);
    }

    public function test_unauthenticated_user_cannot_access_proof_endpoint(): void
    {
        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0010',
            'payer_person_id' => $this->donorPerson->id,
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->event->id,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 1000.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRX_PROOF_AUTH',
            'payment_proof_path' => 'payment_proofs/test_proof.jpg',
            'status' => CollectionPayment::STATUS_PENDING,
            'is_public_submission' => true,
            'submitted_by' => $this->donorUser->id,
            'created_by' => $this->donorUser->id,
        ]);

        Storage::disk('local')->put($payment->payment_proof_path, 'sample proof content');

        // Public donor proof route
        $responsePublic = $this->get(route('public.payments.proof', $payment->payment_code));
        $responsePublic->assertStatus(401);

        // Admin proof route
        $responseAdmin = $this->get(route('portal.payments.proof', $payment->id));
        $responseAdmin->assertRedirect(route('login'));
    }

    public function test_cross_donor_idor_protection_for_payment_proof(): void
    {
        $fakeContent = 'donor A secret proof content';
        $proofPath = 'payment_proofs/donor_a_proof.jpg';
        Storage::disk('local')->put($proofPath, $fakeContent);

        $paymentA = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0011',
            'payer_person_id' => $this->donorPerson->id,
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->event->id,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 2000.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRX_IDOR_A',
            'payment_proof_path' => $proofPath,
            'status' => CollectionPayment::STATUS_PENDING,
            'is_public_submission' => true,
            'submitted_by' => $this->donorUser->id,
            'created_by' => $this->donorUser->id,
        ]);

        $personB = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Donor B',
            'phone' => '01555444333',
            'status' => 'active',
        ]);
        $userB = User::factory()->create([
            'phone' => '01555444333',
            'status' => 'active',
        ]);

        // Donor B tries to access Donor A's proof -> 403 Forbidden
        $responseB = $this->actingAs($userB)->get(route('public.payments.proof', $paymentA->payment_code));
        $responseB->assertStatus(403);

        // Donor A accesses own proof -> 200 OK
        $responseA = $this->actingAs($this->donorUser)->get(route('public.payments.proof', $paymentA->payment_code));
        $responseA->assertStatus(200);
        $this->assertEquals($fakeContent, $responseA->streamedContent());
    }

    public function test_admin_with_permission_can_access_proof_via_admin_and_public_route(): void
    {
        $fakeContent = 'admin proof stream content';
        $proofPath = 'payment_proofs/admin_test_proof.jpg';
        Storage::disk('local')->put($proofPath, $fakeContent);

        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0012',
            'payer_person_id' => $this->donorPerson->id,
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->event->id,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 1500.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRX_ADMIN_ACCESS',
            'payment_proof_path' => $proofPath,
            'status' => CollectionPayment::STATUS_PENDING,
            'is_public_submission' => true,
            'submitted_by' => $this->donorUser->id,
            'created_by' => $this->donorUser->id,
        ]);

        // Super admin accesses via admin route
        $adminResp = $this->actingAs($this->superAdmin)->get(route('portal.payments.proof', $payment->id));
        $adminResp->assertStatus(200);
        $this->assertEquals($fakeContent, $adminResp->streamedContent());

        // Super admin accesses via public route
        $publicResp = $this->actingAs($this->superAdmin)->get(route('public.payments.proof', $payment->payment_code));
        $publicResp->assertStatus(200);
        $this->assertEquals($fakeContent, $publicResp->streamedContent());
    }

    public function test_ambiguous_person_matching_returns_null(): void
    {
        // 1. Two active people with identical phone
        Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Duplicate Phone Person 1',
            'phone' => '01811223344',
            'status' => 'active',
        ]);
        Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Duplicate Phone Person 2',
            'phone' => '01811223344',
            'status' => 'active',
        ]);

        $ambiguousUser = User::factory()->create([
            'phone' => '01811223344',
            'status' => 'active',
        ]);

        $this->assertNull($ambiguousUser->person(), 'Ambiguous phone match must resolve to null');

        // 2. Conflicting matches: phone matches Person X, email matches Person Y
        $personX = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Person X',
            'phone' => '01700998811',
            'email' => 'x@example.com',
            'status' => 'active',
        ]);
        $personY = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Person Y',
            'phone' => '01700998822',
            'email' => 'y@example.com',
            'status' => 'active',
        ]);

        $conflictingUser = User::factory()->create([
            'phone' => '01700998811', // Matches Person X
            'email' => 'y@example.com', // Matches Person Y
            'status' => 'active',
        ]);

        $this->assertNull($conflictingUser->person(), 'Conflicting phone and email matches must resolve to null');
    }

    public function test_bd_phone_normalization_variants_match_correct_person(): void
    {
        $person = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'BD Format Devotee',
            'phone' => '+8801755123456',
            'status' => 'active',
        ]);

        // User registered with standard 11-digit local format
        $userLocal = User::factory()->create([
            'phone' => '01755123456',
            'status' => 'active',
        ]);

        $resolved = $userLocal->person();
        $this->assertNotNull($resolved);
        $this->assertEquals($person->id, $resolved->id);

        // User registered with 880 prefix without plus
        $userPrefix = User::factory()->create([
            'phone' => '8801755123456',
            'status' => 'active',
        ]);
        $this->assertEquals($person->id, $userPrefix->person()?->id);
    }

    public function test_voluntary_public_donation_admin_verification(): void
    {
        // 1. Devotee submits a voluntary donation with NO pledge
        $postData = [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => 2500.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX_VOLUNTARY_01',
            'payment_notes' => 'Direct voluntary gift for puja celebration',
        ];

        $response = $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), $postData);
        $response->assertRedirect(route('public.payments.history'));

        $payment = CollectionPayment::where('transaction_reference', 'TRX_VOLUNTARY_01')->first();
        $this->assertNotNull($payment);
        $this->assertNull($payment->pledge_id);
        $this->assertEquals(CollectionPayment::TYPE_EVENT_DONATION, $payment->collection_type);

        $initialPledgePaid = (float) $this->pledge->fresh()->verified_paid_amount;

        // 2. Admin verifies the voluntary donation without a pledge
        $verifyResponse = $this->actingAs($this->superAdmin)->post(route('portal.payments.verify', $payment->id), [
            'verification_note' => 'Verified voluntary donation against MFS statement',
        ]);
        $verifyResponse->assertRedirect();

        $payment->refresh();
        $this->assertEquals(CollectionPayment::STATUS_VERIFIED, $payment->status);
        $this->assertNotNull($payment->receipt);
        $this->assertNotNull($payment->finance_post_reference);

        // 3. Journal entry is posted to CODE_INCOME_EVENT (4020)
        $journal = JournalEntry::where('journal_number', $payment->finance_post_reference)->first();
        $this->assertNotNull($journal);
        $this->assertTrue($journal->isBalanced());

        $eventIncomeAccount = FinancialAccount::where('account_code', FinancialAccount::CODE_INCOME_EVENT)->first();
        $creditLine = $journal->lines->where('financial_account_id', $eventIncomeAccount->id)->first();
        $this->assertNotNull($creditLine);
        $this->assertEquals('credit', $creditLine->entry_type);
        $this->assertEquals(2500.00, (float) $creditLine->amount);

        // 4. Ensure existing pledge balance is NOT reduced
        $this->assertEquals($initialPledgePaid, (float) $this->pledge->fresh()->verified_paid_amount);
    }

    public function test_receiver_account_event_scope_enforcement(): void
    {
        // Another event
        $category = EventCategory::first();
        $otherEvent = Event::create([
            'event_code' => 'EVT-KALI-2026',
            'slug' => 'kali-puja-2026',
            'name_en' => 'Kali Puja 2026',
            'name_bn' => 'শ্যামাপূজা ২০২৬',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'start_date' => '2026-11-05',
            'end_date' => '2026-11-08',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by' => $this->superAdmin->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $mfsAccount = FinancialAccount::where('account_code', FinancialAccount::CODE_MFS)->first();

        // Specific receiver account for Kali Puja
        $otherEventReceiver = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BKASH,
            'account_name' => 'Kali Puja Exclusive bKash',
            'account_number' => '01799887766',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'financial_account_id' => $mfsAccount?->id,
            'event_id' => $otherEvent->id,
            'is_active' => true,
            'is_verified' => true,
            'display_order' => 5,
            'created_by' => $this->superAdmin->id,
        ]);

        // Attempt to submit payment for Durga Puja using Kali Puja exclusive account -> Rejected
        $response = $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $otherEventReceiver->id,
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX_SCOPE_MISMATCH',
        ]);
        $response->assertSessionHasErrors(['receiver_account_id']);

        // General receiver account (event_id is null) succeeds
        $generalReceiver = ReceiverAccount::create([
            'payment_method' => ReceiverAccount::METHOD_BKASH,
            'account_name' => 'Temple General bKash',
            'account_number' => '01711224466',
            'account_type' => ReceiverAccount::TYPE_MERCHANT,
            'financial_account_id' => $mfsAccount?->id,
            'event_id' => null, // General
            'is_active' => true,
            'is_verified' => true,
            'display_order' => 6,
            'created_by' => $this->superAdmin->id,
        ]);

        $generalResponse = $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $generalReceiver->id,
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX_GENERAL_SUCCESS',
        ]);
        $generalResponse->assertRedirect(route('public.payments.history'));
    }

    public function test_event_collection_date_boundaries_enforced(): void
    {
        // 1. Collection has not started yet
        $this->event->update([
            'collection_start_date' => now()->addDays(5)->toDateString(),
            'collection_end_date' => now()->addDays(20)->toDateString(),
        ]);

        $responseBefore = $this->actingAs($this->donorUser)->get(route('public.events.pay', $this->event->slug));
        $responseBefore->assertRedirect(route('public.events.show', $this->event->slug));
        $responseBefore->assertSessionHas('error');

        $postBefore = $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX_DATE_BEFORE',
        ]);
        $postBefore->assertSessionHasErrors(['event']);

        // 2. Collection already ended
        $this->event->update([
            'collection_start_date' => now()->subDays(20)->toDateString(),
            'collection_end_date' => now()->subDays(2)->toDateString(),
        ]);

        $responseAfter = $this->actingAs($this->donorUser)->get(route('public.events.pay', $this->event->slug));
        $responseAfter->assertRedirect(route('public.events.show', $this->event->slug));
        $responseAfter->assertSessionHas('error');

        $postAfter = $this->actingAs($this->donorUser)->post(route('public.events.pay.submit', $this->event->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->bkashReceiver->id,
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711998877',
            'transaction_reference' => 'TRX_DATE_AFTER',
        ]);
        $postAfter->assertSessionHasErrors(['event']);
    }

    public function test_artisan_migrate_proofs_command(): void
    {
        $fakeContent = 'legacy public proof file content';
        $proofPath = 'payment_proofs/legacy_proof.jpg';

        // Place file on public disk
        Storage::disk('public')->put($proofPath, $fakeContent);

        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0099',
            'payer_person_id' => $this->donorPerson->id,
            'collection_type' => CollectionPayment::TYPE_EVENT_DONATION,
            'event_id' => $this->event->id,
            'payment_date' => now()->toDateString(),
            'payment_year' => 2026,
            'amount' => 1200.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRX_MIGRATE_CMD',
            'payment_proof_path' => $proofPath,
            'status' => CollectionPayment::STATUS_PENDING,
            'is_public_submission' => true,
            'submitted_by' => $this->donorUser->id,
            'created_by' => $this->donorUser->id,
        ]);

        Storage::disk('local')->assertMissing($proofPath);

        // Run artisan migration command
        $this->artisan('portal:migrate-proofs')
            ->assertSuccessful();

        // Verify copied to local disk with exact content
        Storage::disk('local')->assertExists($proofPath);
        $this->assertEquals($fakeContent, Storage::disk('local')->get($proofPath));

        // Original remains on public disk by default
        Storage::disk('public')->assertExists($proofPath);
    }
}
