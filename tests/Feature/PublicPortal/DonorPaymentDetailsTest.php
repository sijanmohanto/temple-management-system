<?php

namespace Tests\Feature\PublicPortal;

use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\FinancialAccount;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\Receipt;
use App\Models\ReceiverAccount;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonorPaymentDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $donorUserA;

    protected Person $donorPersonA;

    protected User $donorUserB;

    protected Person $donorPersonB;

    protected Event $event;

    protected ReceiverAccount $receiverAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->assignRole('super_admin');

        // Donor A
        $this->donorPersonA = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Animesh Roy',
            'name_bn' => 'অনিমেষ রায়',
            'phone' => '01711000001',
            'email' => 'animesh@example.com',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->donorUserA = User::factory()->create([
            'name' => 'Animesh Roy',
            'phone' => '01711000001',
            'email' => 'animesh@example.com',
            'status' => 'active',
        ]);
        $this->donorUserA->assignRole('devotee');

        // Donor B
        $this->donorPersonB = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Biplob Das',
            'name_bn' => 'বিপ্লব দাস',
            'phone' => '01711000002',
            'email' => 'biplob@example.com',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->donorUserB = User::factory()->create([
            'name' => 'Biplob Das',
            'phone' => '01711000002',
            'email' => 'biplob@example.com',
            'status' => 'active',
        ]);
        $this->donorUserB->assignRole('devotee');

        $category = EventCategory::firstOrCreate(
            ['slug' => 'durga-puja'],
            ['name_en' => 'Durga Puja', 'name_bn' => 'দুর্গাপূজা', 'status' => 'active']
        );

        $this->event = Event::create([
            'event_category_id' => $category->id,
            'name_en' => 'Sharadotsav 2026',
            'name_bn' => 'শারদোৎসব ২০২৬',
            'slug' => 'sharadotsav-2026',
            'event_code' => 'EVT-SH-2026',
            'event_year' => 2026,
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-25',
            'status' => Event::STATUS_ACTIVE,
            'is_published' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $assetAccount = FinancialAccount::where('account_code', '1010')->first()
            ?? FinancialAccount::firstOrCreate(
                ['account_code' => '1010'],
                ['name_en' => 'bKash Merchant Account', 'account_type' => 'asset', 'is_active' => true]
            );

        $this->receiverAccount = ReceiverAccount::create([
            'account_name' => 'Temple bKash Official',
            'payment_method' => 'bkash',
            'account_number' => '01799887766',
            'account_type' => 'merchant',
            'financial_account_id' => $assetAccount->id,
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    protected function createPayment(array $overrides = []): CollectionPayment
    {
        return CollectionPayment::create(array_merge([
            'payment_code' => 'PAY-'.uniqid(),
            'payer_person_id' => $this->donorPersonA->id,
            'collection_type' => 'event_pledge',
            'event_id' => $this->event->id,
            'receiver_account_id' => $this->receiverAccount->id,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRX'.uniqid(),
            'amount' => 1000.00,
            'status' => CollectionPayment::STATUS_PENDING,
            'is_public_submission' => true,
            'payment_date' => '2026-10-16',
            'payment_year' => 2026,
            'submitted_by' => $this->donorUserA->id,
            'created_by' => $this->superAdmin->id,
        ], $overrides));
    }

    public function test_guest_is_redirected_to_login_from_my_contributions(): void
    {
        $response = $this->get(route('public.payments.history'));
        $response->assertRedirect(route('login'));

        $responseShow = $this->get(route('public.payments.show', 'PAY-TEST-0001'));
        $responseShow->assertRedirect(route('login'));
    }

    public function test_donor_can_view_own_payment_history_and_filter_by_reversed_status(): void
    {
        $verifiedPayment = $this->createPayment([
            'payment_code' => 'PAY-2026-0001',
            'payer_person_id' => $this->donorPersonA->id,
            'amount' => 1500.00,
            'status' => CollectionPayment::STATUS_VERIFIED,
        ]);

        $reversedPayment = $this->createPayment([
            'payment_code' => 'PAY-2026-0002',
            'payer_person_id' => $this->donorPersonA->id,
            'amount' => 2000.00,
            'status' => CollectionPayment::STATUS_REVERSED,
            'reversal_reason' => 'Customer requested correction',
            'reversed_at' => now(),
            'reversed_by' => $this->superAdmin->id,
        ]);

        $responseAll = $this->actingAs($this->donorUserA)->get(route('public.payments.history'));
        $responseAll->assertOk();
        $responseAll->assertSee('PAY-2026-0001');
        $responseAll->assertSee('PAY-2026-0002');

        // Filter by reversed
        $responseReversed = $this->actingAs($this->donorUserA)->get(route('public.payments.history', ['status' => 'reversed']));
        $responseReversed->assertOk();
        $responseReversed->assertSee('PAY-2026-0002');
        $responseReversed->assertDontSee('PAY-2026-0001');
    }

    public function test_donor_can_view_own_payment_details_page(): void
    {
        $payment = $this->createPayment([
            'payment_code' => 'PAY-2026-0003',
            'payer_person_id' => $this->donorPersonA->id,
            'transaction_reference' => 'TRX003',
            'sender_mobile' => '01711000001',
            'amount' => 3500.00,
            'status' => CollectionPayment::STATUS_VERIFIED,
        ]);

        $response = $this->actingAs($this->donorUserA)->get(route('public.payments.show', $payment->payment_code));
        $response->assertOk();
        $response->assertSee('PAY-2026-0003');
        $response->assertSee('3,500.00');
        $response->assertSee('Temple bKash Official');
        $response->assertSee('TRX003');
    }

    public function test_cross_donor_cannot_view_another_donor_payment_details(): void
    {
        $paymentA = $this->createPayment([
            'payment_code' => 'PAY-2026-0004',
            'payer_person_id' => $this->donorPersonA->id,
            'amount' => 1000.00,
            'status' => CollectionPayment::STATUS_PENDING,
        ]);

        // Donor B attempts to access Donor A's payment details
        $response = $this->actingAs($this->donorUserB)->get(route('public.payments.show', $paymentA->payment_code));
        $response->assertStatus(403);
    }

    protected function createReceipt(CollectionPayment $payment, array $overrides = []): Receipt
    {
        return Receipt::create(array_merge([
            'receipt_number' => 'RCT-'.uniqid(),
            'collection_payment_id' => $payment->id,
            'receipt_year' => 2026,
            'issued_at' => now(),
            'issued_by' => $this->superAdmin->id,
            'status' => 'valid',
        ], $overrides));
    }

    public function test_verified_payment_with_receipt_exposes_view_and_download_actions(): void
    {
        $payment = $this->createPayment([
            'payment_code' => 'PAY-2026-0005',
            'payer_person_id' => $this->donorPersonA->id,
            'amount' => 5000.00,
            'status' => CollectionPayment::STATUS_VERIFIED,
        ]);

        $receipt = $this->createReceipt($payment, [
            'receipt_number' => 'RCT-2026-000005',
        ]);

        $response = $this->actingAs($this->donorUserA)->get(route('public.payments.show', $payment->payment_code));
        $response->assertOk();
        $response->assertSee(route('public.payments.receipt', $receipt->receipt_number));
        $response->assertSee(route('public.payments.receipt.download', $receipt->receipt_number));

        // Test receipt download endpoint
        $downloadResponse = $this->actingAs($this->donorUserA)->get(route('public.payments.receipt.download', $receipt->receipt_number));
        $downloadResponse->assertOk();
    }

    public function test_reversed_or_pending_payment_does_not_expose_active_receipt(): void
    {
        $payment = $this->createPayment([
            'payment_code' => 'PAY-2026-0006',
            'payer_person_id' => $this->donorPersonA->id,
            'amount' => 2500.00,
            'status' => CollectionPayment::STATUS_REVERSED,
            'reversal_reason' => 'Bank chargeback',
            'reversed_at' => now(),
            'reversed_by' => $this->superAdmin->id,
        ]);

        $voidedReceipt = $this->createReceipt($payment, [
            'receipt_number' => 'RCT-2026-000006',
            'status' => 'voided',
            'void_reason' => 'Payment reversed',
            'voided_at' => now(),
            'voided_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->donorUserA)->get(route('public.payments.show', $payment->payment_code));
        $response->assertOk();
        $response->assertDontSee(route('public.payments.receipt.download', $voidedReceipt->receipt_number));

        // Direct download attempt on voided receipt is denied / 403
        $downloadResponse = $this->actingAs($this->donorUserA)->get(route('public.payments.receipt.download', $voidedReceipt->receipt_number));
        $downloadResponse->assertStatus(403);
    }

    public function test_cross_donor_cannot_download_receipt_of_another_donor(): void
    {
        $paymentA = $this->createPayment([
            'payment_code' => 'PAY-2026-0007',
            'payer_person_id' => $this->donorPersonA->id,
            'amount' => 4000.00,
            'status' => CollectionPayment::STATUS_VERIFIED,
        ]);

        $receiptA = $this->createReceipt($paymentA, [
            'receipt_number' => 'RCT-2026-000007',
        ]);

        // Donor B tries to download Donor A's receipt
        $response = $this->actingAs($this->donorUserB)->get(route('public.payments.receipt.download', $receiptA->receipt_number));
        $response->assertStatus(403);
    }

    public function test_only_verified_allocations_count_towards_paid_amount(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-001',
            'person_id' => $this->donorPersonA->id,
            'event_id' => $this->event->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-10-15',
            'status' => Pledge::STATUS_PARTIAL,
            'created_by' => $this->superAdmin->id,
        ]);

        // Verified Payment & Allocation: 3000
        $paymentVerified = $this->createPayment([
            'payment_code' => 'PAY-2026-0008',
            'payer_person_id' => $this->donorPersonA->id,
            'amount' => 3000.00,
            'status' => CollectionPayment::STATUS_VERIFIED,
        ]);

        PaymentAllocation::create([
            'collection_payment_id' => $paymentVerified->id,
            'target_type' => PaymentAllocation::TARGET_PLEDGE,
            'pledge_id' => $pledge->id,
            'amount' => 3000.00,
            'is_active' => true,
        ]);

        // Pending Payment & Allocation: 2000 (must NOT be counted as paid)
        $paymentPending = $this->createPayment([
            'payment_code' => 'PAY-2026-0009',
            'payer_person_id' => $this->donorPersonA->id,
            'amount' => 2000.00,
            'status' => CollectionPayment::STATUS_PENDING,
        ]);

        PaymentAllocation::create([
            'collection_payment_id' => $paymentPending->id,
            'target_type' => PaymentAllocation::TARGET_PLEDGE,
            'pledge_id' => $pledge->id,
            'amount' => 2000.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->donorUserA)->get(route('public.payments.show', $paymentVerified->payment_code));
        $response->assertOk();
        // Paid amount should reflect 3,000.00, and remaining due should reflect 7,000.00 (10,000 - 3,000)
        $response->assertSee('3,000.00');
        $response->assertSee('7,000.00');
        $response->assertDontSee('5,000.00'); // It must not count the 2000 pending
    }

    public function test_voluntary_contributions_displayed_cleanly_without_pledge(): void
    {
        $voluntaryPayment = $this->createPayment([
            'payment_code' => 'PAY-2026-0010',
            'payer_person_id' => $this->donorPersonA->id,
            'collection_type' => 'event_donation',
            'amount' => 1200.00,
            'status' => CollectionPayment::STATUS_VERIFIED,
        ]);

        $response = $this->actingAs($this->donorUserA)->get(route('public.payments.show', $voluntaryPayment->payment_code));
        $response->assertOk();
        $response->assertSee('PAY-2026-0010');
        $response->assertSee('1,200.00');
        $response->assertSee(__('portal.public.voluntary_contribution_details'));
    }
}
