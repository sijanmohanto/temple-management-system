<?php

namespace Tests\Feature\PublicPortal;

use App\Models\CollectionPayment;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
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

class ComplaintFinancialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $donorUser;

    protected Person $donorPerson;

    protected Event $event;

    protected ReceiverAccount $receiverAccount;

    protected Pledge $pledge;

    protected CollectionPayment $payment;

    protected PaymentAllocation $allocation;

    protected Receipt $receipt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->assignRole('super_admin');

        $this->donorPerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Shyamal Chakraborty',
            'phone' => '01711776655',
            'email' => 'shyamal@example.com',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->donorUser = User::factory()->create([
            'name' => 'Shyamal Chakraborty',
            'phone' => '01711776655',
            'email' => 'shyamal@example.com',
            'status' => 'active',
        ]);
        $this->donorUser->assignRole('devotee');

        $category = EventCategory::firstOrCreate(
            ['slug' => 'puja'],
            ['name_en' => 'Puja', 'name_bn' => 'পূজা', 'status' => 'active']
        );

        $this->event = Event::create([
            'event_category_id' => $category->id,
            'name_en' => 'Saraswati Puja 2026',
            'name_bn' => 'সরস্বতী পূজা ২০২৬',
            'slug' => 'saraswati-puja-2026',
            'event_code' => 'EVT-SP-2026',
            'event_year' => 2026,
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-05',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->superAdmin->id,
        ]);

        $assetAccount = FinancialAccount::where('account_code', '1010')->first()
            ?? FinancialAccount::firstOrCreate(
                ['account_code' => '1010'],
                ['name_en' => 'bKash Merchant Account', 'account_type' => 'asset', 'is_active' => true]
            );

        $this->receiverAccount = ReceiverAccount::create([
            'account_name' => 'Temple bKash Main',
            'payment_method' => 'bkash',
            'account_number' => '01700112233',
            'account_type' => 'merchant',
            'financial_account_id' => $assetAccount->id,
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-SP01',
            'person_id' => $this->donorPerson->id,
            'event_id' => $this->event->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-02-01',
            'status' => Pledge::STATUS_PAID,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->payment = CollectionPayment::create([
            'payment_code' => 'PAY-2026-SP001',
            'payer_person_id' => $this->donorPerson->id,
            'collection_type' => 'event_pledge',
            'event_id' => $this->event->id,
            'receiver_account_id' => $this->receiverAccount->id,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRXSP001',
            'amount' => 5000.00,
            'status' => CollectionPayment::STATUS_VERIFIED,
            'is_public_submission' => true,
            'payment_date' => '2026-02-02',
            'payment_year' => 2026,
            'submitted_by' => $this->donorUser->id,
            'verified_by' => $this->superAdmin->id,
            'verified_at' => now(),
            'created_by' => $this->superAdmin->id,
        ]);

        $this->allocation = PaymentAllocation::create([
            'collection_payment_id' => $this->payment->id,
            'target_type' => PaymentAllocation::TARGET_PLEDGE,
            'pledge_id' => $this->pledge->id,
            'amount' => 5000.00,
            'is_active' => true,
        ]);

        $this->receipt = Receipt::create([
            'receipt_number' => 'RCT-2026-SP0001',
            'collection_payment_id' => $this->payment->id,
            'receipt_year' => 2026,
            'issued_at' => now(),
            'issued_by' => $this->superAdmin->id,
            'status' => 'valid',
        ]);
    }

    public function test_complaint_lifecycle_never_modifies_financial_records(): void
    {
        // Snapshot initial financial state
        $initialJournalEntriesCount = JournalEntry::count();
        $initialJournalLinesCount = JournalLine::count();
        $initialPaymentStatus = $this->payment->status;
        $initialPaymentAmount = $this->payment->amount;
        $initialAllocationStatus = $this->allocation->status;
        $initialAllocationAmount = $this->allocation->allocated_amount;
        $initialPledgeStatus = $this->pledge->status;
        $initialPledgeAmount = $this->pledge->pledged_amount;
        $initialReceiptVoidStatus = $this->receipt->is_void;

        // Step 1: Donor submits complaint regarding payment
        $responseSubmit = $this->actingAs($this->donorUser)->post(route('public.complaints.store'), [
            'complaint_type' => 'wrong_due_amount',
            'payment_code' => $this->payment->payment_code,
            'event_id' => $this->event->id,
            'description' => 'I paid BDT 5000 but statement says due remaining. Please investigate.',
        ]);
        $responseSubmit->assertSessionHasNoErrors();

        $complaint = Complaint::where('person_id', $this->donorPerson->id)->firstOrFail();

        // Step 2: Admin marks under review
        $this->actingAs($this->superAdmin)->post(route('portal.complaints.under-review', $complaint));

        // Step 3: Admin replies to donor
        $this->actingAs($this->superAdmin)->post(route('portal.complaints.respond', $complaint), [
            'admin_response' => 'We are reviewing ledger entries with our treasury officer.',
        ]);

        // Step 4: Admin adds internal note
        $this->actingAs($this->superAdmin)->post(route('portal.complaints.add-note', $complaint), [
            'internal_notes' => 'Auditor note: ledger balances confirmed matched.',
        ]);

        // Step 5: Admin resolves complaint
        $this->actingAs($this->superAdmin)->post(route('portal.complaints.resolve', $complaint), [
            'admin_response' => 'Account reconciled; your pledge is fully settled.',
        ]);

        // Step 6: Admin closes complaint
        $this->actingAs($this->superAdmin)->post(route('portal.complaints.close', $complaint));

        // Refetch models
        $this->payment->refresh();
        $this->allocation->refresh();
        $this->pledge->refresh();
        $this->receipt->refresh();

        // Strict Financial Invariants Assertions
        $this->assertEquals($initialPaymentStatus, $this->payment->status, 'Payment status must not be modified by complaints.');
        $this->assertEquals($initialPaymentAmount, $this->payment->amount, 'Payment amount must not be modified by complaints.');
        $this->assertEquals($initialAllocationStatus, $this->allocation->status, 'Allocation status must not be modified by complaints.');
        $this->assertEquals($initialAllocationAmount, $this->allocation->allocated_amount, 'Allocation amount must not be modified by complaints.');
        $this->assertEquals($initialPledgeStatus, $this->pledge->status, 'Pledge status must not be modified by complaints.');
        $this->assertEquals($initialPledgeAmount, $this->pledge->pledged_amount, 'Pledge amount must not be modified by complaints.');
        $this->assertEquals($initialReceiptVoidStatus, $this->receipt->is_void, 'Receipt void state must not be modified by complaints.');
        $this->assertEquals($initialJournalEntriesCount, JournalEntry::count(), 'Zero journal entries must be created or modified by complaints.');
        $this->assertEquals($initialJournalLinesCount, JournalLine::count(), 'Zero journal lines must be created or modified by complaints.');
    }
}
