<?php

namespace Tests\Feature\Devotee;

use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\JournalEntry;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\Receipt;
use App\Models\ReceiverAccount;
use App\Models\User;
use App\Services\DevoteeCredentialService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevoteePaymentAndHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Person $devoteePerson1;

    protected User $devoteeUser1;

    protected Person $devoteePerson2;

    protected User $devoteeUser2;

    protected EventCategory $pujaCategory;

    protected Event $eventA;

    protected Event $eventB;

    protected ReceiverAccount $receiverAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@temple.local',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('super_admin');

        $this->devoteePerson1 = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Tapan Sen',
            'phone' => '01711001122',
            'status' => 'active',
        ]);
        $service = app(DevoteeCredentialService::class);
        $this->devoteeUser1 = $service->provisionDevoteeAccount($this->devoteePerson1, 'Pass@123456', $this->adminUser);

        $this->devoteePerson2 = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Shyamal Das',
            'phone' => '01811001122',
            'status' => 'active',
        ]);
        $this->devoteeUser2 = $service->provisionDevoteeAccount($this->devoteePerson2, 'Pass@654321', $this->adminUser);

        $this->pujaCategory = EventCategory::create([
            'name_en' => 'Puja Festival',
            'name_bn' => 'পূজা উৎসব',
            'slug' => 'puja-fest-payment-test',
            'is_active' => true,
        ]);

        $this->eventA = Event::create([
            'event_code' => 'EVT-2026-0001',
            'event_category_id' => $this->pujaCategory->id,
            'name_en' => 'Durga Puja 2026',
            'slug' => 'durga-puja-2026',
            'event_year' => 2026,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        $this->eventB = Event::create([
            'event_code' => 'EVT-2026-0002',
            'event_category_id' => $this->pujaCategory->id,
            'name_en' => 'Kali Puja 2026',
            'slug' => 'kali-puja-2026',
            'event_year' => 2026,
            'start_date' => now()->subDays(3),
            'end_date' => now()->addDays(7),
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        $this->receiverAccount = ReceiverAccount::create([
            'account_name' => 'Temple bKash Merchant',
            'account_number' => '01711223344',
            'payment_method' => 'bkash',
            'account_type' => 'merchant',
            'is_active' => true,
            'is_verified' => true,
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_payment_for_event_a_does_not_reduce_event_b_due(): void
    {
        // Pledges for Devotee 1
        $pledgeA = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->eventA->id,
            'person_id' => $this->devoteePerson1->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => now(),
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $pledgeB = Pledge::create([
            'pledge_code' => 'PLG-2026-0002',
            'event_id' => $this->eventB->id,
            'person_id' => $this->devoteePerson1->id,
            'pledge_year' => 2026,
            'pledged_amount' => 2000.00,
            'pledged_on' => now(),
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        // Submit and verify payment of 3,000 for Event A
        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0001',
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'payer_person_id' => $this->devoteePerson1->id,
            'event_id' => $this->eventA->id,
            'pledge_id' => $pledgeA->id,
            'amount' => 3000.00,
            'payment_method' => 'bkash',
            'status' => CollectionPayment::STATUS_VERIFIED,
            'payment_date' => now(),
            'payment_year' => 2026,
            'submitted_by' => $this->devoteeUser1->id,
            'created_by' => $this->adminUser->id,
            'verified_by' => $this->adminUser->id,
            'verified_at' => now(),
        ]);
        PaymentAllocation::create([
            'collection_payment_id' => $payment->id,
            'event_id' => $this->eventA->id,
            'pledge_id' => $pledgeA->id,
            'target_type' => PaymentAllocation::TARGET_PLEDGE,
            'amount' => 3000.00,
            'is_active' => true,
        ]);

        $pledgeA->refresh();
        $pledgeB->refresh();

        // Event A due reduces from 5,000 to 2,000
        $this->assertEquals(3000.00, $pledgeA->verified_paid_amount);
        $this->assertEquals(2000.00, $pledgeA->outstanding_amount);

        // Event B due MUST remain completely intact at 2,000
        $this->assertEquals(0.00, $pledgeB->verified_paid_amount);
        $this->assertEquals(2000.00, $pledgeB->outstanding_amount);
    }

    public function test_devotee_can_view_own_payments_and_details(): void
    {
        $payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0010',
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'payer_person_id' => $this->devoteePerson1->id,
            'event_id' => $this->eventA->id,
            'amount' => 1500.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRXOWN01',
            'status' => CollectionPayment::STATUS_VERIFIED,
            'payment_date' => now(),
            'payment_year' => 2026,
            'submitted_by' => $this->devoteeUser1->id,
            'created_by' => $this->adminUser->id,
            'verified_by' => $this->adminUser->id,
            'verified_at' => now(),
        ]);

        // 1. Appears in DataTable JSON
        $tableResponse = $this->actingAs($this->devoteeUser1)->getJson(route('devotee.payments.index'));
        $tableResponse->assertStatus(200);
        $tableResponse->assertSee('PAY-2026-0010');

        // 2. Offcanvas details endpoint
        $detailsResponse = $this->actingAs($this->devoteeUser1)->getJson(route('devotee.payments.details', 'PAY-2026-0010'));
        $detailsResponse->assertStatus(200);
        $detailsResponse->assertJson([
            'success' => true,
            'payment' => [
                'payment_code' => 'PAY-2026-0010',
                'amount' => 1500.00,
                'status' => 'verified',
            ],
        ]);
    }

    public function test_cross_devotee_payment_access_is_forbidden(): void
    {
        // Payment by Devotee 2
        CollectionPayment::create([
            'payment_code' => 'PAY-DEVOTEE-2',
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'payer_person_id' => $this->devoteePerson2->id,
            'event_id' => $this->eventA->id,
            'amount' => 5000.00,
            'payment_method' => 'bkash',
            'status' => CollectionPayment::STATUS_VERIFIED,
            'payment_date' => now(),
            'payment_year' => 2026,
            'submitted_by' => $this->devoteeUser2->id,
            'created_by' => $this->devoteeUser2->id,
        ]);

        // Devotee 1 attempts to view Devotee 2's payment details
        $response = $this->actingAs($this->devoteeUser1)->getJson(route('devotee.payments.details', 'PAY-DEVOTEE-2'));
        $response->assertStatus(403);
    }

    public function test_duplicate_transaction_reference_is_rejected(): void
    {
        CollectionPayment::create([
            'payment_code' => 'PAY-EXISTING-TRX',
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'payer_person_id' => $this->devoteePerson2->id,
            'event_id' => $this->eventA->id,
            'amount' => 1000.00,
            'payment_method' => 'bkash',
            'transaction_reference' => 'DUPLICATE_TRX_999',
            'status' => CollectionPayment::STATUS_VERIFIED,
            'payment_date' => now(),
            'payment_year' => 2026,
            'submitted_by' => $this->devoteeUser2->id,
            'created_by' => $this->adminUser->id,
        ]);

        // Devotee 1 submits identical transaction reference
        $response = $this->actingAs($this->devoteeUser1)->post(route('public.events.pay.submit', $this->eventA->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->receiverAccount->id,
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711001122',
            'transaction_reference' => 'DUPLICATE_TRX_999',
        ]);

        $response->assertSessionHasErrors(['transaction_reference']);
    }

    public function test_pending_payment_submission_does_not_create_journal_or_receipt(): void
    {
        $initialJournals = JournalEntry::count();
        $initialReceipts = Receipt::count();

        $response = $this->actingAs($this->devoteeUser1)->post(route('public.events.pay.submit', $this->eventA->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->receiverAccount->id,
            'amount' => 2500.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711001122',
            'transaction_reference' => 'TRXPENDINGCHECK',
        ]);

        $response->assertRedirect();

        $payment = CollectionPayment::where('transaction_reference', 'TRXPENDINGCHECK')->first();
        $this->assertNotNull($payment);
        $this->assertEquals(CollectionPayment::STATUS_PENDING, $payment->status);

        // Invariant: zero journal entries and zero receipts created on submission
        $this->assertEquals($initialJournals, JournalEntry::count());
        $this->assertEquals($initialReceipts, Receipt::count());
    }
}
