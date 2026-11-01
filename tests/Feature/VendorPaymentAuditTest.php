<?php

namespace Tests\Feature;

use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Models\VendorPayment;
use App\Models\VendorPaymentAllocation;
use App\Services\FinancePostingService;
use App\Services\VendorPaymentService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VendorPaymentAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $templeAdmin;

    protected User $devotee;

    protected Vendor $vendor;

    protected FinancialAccount $cashAccount;

    protected FinancialAccount $bankAccount;

    protected FinancialAccount $inactiveAccount;

    protected FinancialAccount $liabilityAccount;

    protected FinancialAccount $apAccount;

    protected VendorBill $bill;

    protected VendorPaymentService $paymentService;

    protected FinancePostingService $postingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $treasurerRole = Role::where('slug', 'treasurer')->first();
        $templeAdminRole = Role::where('slug', 'temple_admin')->first();
        $devoteeRole = Role::where('slug', 'devotee')->first();

        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->roles()->attach($superAdminRole);

        $this->treasurer = User::factory()->create(['status' => 'active']);
        $this->treasurer->roles()->attach($treasurerRole);

        $this->templeAdmin = User::factory()->create(['status' => 'active']);
        $this->templeAdmin->roles()->attach($templeAdminRole);

        $this->devotee = User::factory()->create(['status' => 'active']);
        $this->devotee->roles()->attach($devoteeRole);

        $this->cashAccount = FinancialAccount::create([
            'account_code' => '1010',
            'account_name_en' => 'Cash in Hand',
            'account_name_bn' => 'হাতে নগদ ক্যাশ',
            'account_type' => FinancialAccount::TYPE_ASSET,
            'normal_balance' => 'debit',
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->bankAccount = FinancialAccount::create([
            'account_code' => '1020',
            'account_name_en' => 'Main Bank Account',
            'account_name_bn' => 'প্রধান ব্যাংক হিসাব',
            'account_type' => FinancialAccount::TYPE_ASSET,
            'normal_balance' => 'debit',
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->inactiveAccount = FinancialAccount::create([
            'account_code' => '1099',
            'account_name_en' => 'Old Inactive Account',
            'account_name_bn' => 'পুরাতন নিষ্ক্রিয় হিসাব',
            'account_type' => FinancialAccount::TYPE_ASSET,
            'normal_balance' => 'debit',
            'is_system' => false,
            'is_active' => false, // Inactive
        ]);

        $this->liabilityAccount = FinancialAccount::create([
            'account_code' => '2020',
            'account_name_en' => 'Loan Payable',
            'account_name_bn' => 'ঋণ হিসাব',
            'account_type' => FinancialAccount::TYPE_LIABILITY,
            'normal_balance' => 'credit',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->apAccount = FinancialAccount::create([
            'account_code' => FinancialAccount::CODE_AP,
            'account_name_en' => 'Accounts Payable',
            'account_name_bn' => 'প্রদেয় হিসাব',
            'account_type' => FinancialAccount::TYPE_LIABILITY,
            'normal_balance' => 'credit',
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->vendor = Vendor::create([
            'vendor_code' => 'VND-0001',
            'name' => 'Radha Krishna Supplies',
            'contact_person' => 'Gopal Roy',
            'phone' => '01711223344',
            'vendor_type' => 'supplier',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->bill = VendorBill::create([
            'bill_number' => 'BIL-2026-0001',
            'vendor_id' => $this->vendor->id,
            'vendor_invoice_number' => 'INV-9001',
            'bill_date' => '2026-11-01',
            'due_date' => '2026-11-15',
            'subtotal' => 10000.00,
            'total_amount' => 10000.00,
            'paid_amount' => 0.00,
            'status' => VendorBill::STATUS_APPROVED,
            'payment_status' => 'unpaid',
            'created_by' => $this->treasurer->id,
        ]);

        $this->paymentService = app(VendorPaymentService::class);
        $this->postingService = app(FinancePostingService::class);
    }

    /**
     * Area 1: Reversal and Ledger Integrity
     * - Confirm original journal entries remain accessible after reversal.
     * - Confirm reversal entries are linked to their original entries.
     * - Confirm ledger reports and audit history remain accurate.
     */
    public function test_area_1_reversal_and_ledger_integrity(): void
    {
        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0001',
            'vendor_id' => $this->vendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 5000.00,
            'status' => VendorPayment::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill->id,
            'allocated_amount' => 5000.00,
        ]);

        // Post the payment
        $this->paymentService->postPayment($payment, $this->superAdmin);
        $payment->refresh();
        $originalJournalId = $payment->journal_entry_id;
        $this->assertNotNull($originalJournalId);

        // Reverse the payment
        $reason = 'Check bounced by clearing house';
        $this->paymentService->reversePayment($payment, $reason, $this->superAdmin);
        $payment->refresh();

        // 1. Confirm original journal entry remains accessible in DB
        $originalJournal = JournalEntry::find($originalJournalId);
        $this->assertNotNull($originalJournal);
        $this->assertEquals(JournalEntry::STATUS_REVERSED, $originalJournal->status);
        $this->assertEquals($reason, $originalJournal->reversal_reason);
        $this->assertCount(2, $originalJournal->lines);

        // 2. Confirm reversal entry exists and links to original journal
        $this->assertNotNull($payment->reversal_journal_entry_id);
        $reversalJournal = JournalEntry::find($payment->reversal_journal_entry_id);
        $this->assertNotNull($reversalJournal);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $reversalJournal->status);
        $this->assertEquals($originalJournal->id, $reversalJournal->reversed_journal_id);
        $this->assertEquals($originalJournal->id, $reversalJournal->source_id);
        $this->assertEquals(JournalEntry::SOURCE_REVERSAL, $reversalJournal->source_type);
        $this->assertEquals($reason, $reversalJournal->reversal_reason);

        // 3. Confirm balanced double entry on reversal entry
        $this->assertEquals((float) $originalJournal->total_credit, (float) $reversalJournal->total_debit);
        $this->assertEquals((float) $originalJournal->total_debit, (float) $reversalJournal->total_credit);

        // 4. Confirm payment record preserves both original and reversal journal IDs
        $this->assertEquals($originalJournal->id, $payment->journal_entry_id);
        $this->assertEquals($reversalJournal->id, $payment->reversal_journal_entry_id);
        $this->assertEquals(VendorPayment::STATUS_REVERSED, $payment->status);
    }

    /**
     * Area 2: Concurrent Payment Posting
     * - Test two different payment vouchers attempting to settle the same bill.
     * - Confirm bill outstanding cannot become negative.
     * - Confirm no duplicate or excessive settlement occurs.
     */
    public function test_area_2_concurrent_payment_posting_prevents_oversettlement(): void
    {
        // Bill total is 10,000.
        // Payment A allocates 7,000.
        // Payment B allocates 5,000 (valid at creation because 5,000 <= 10,000).
        // Total requested = 12,000 (> 10,000).

        $paymentA = VendorPayment::create([
            'payment_number' => 'VPY-2026-0002',
            'vendor_id' => $this->vendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 7000.00,
            'status' => VendorPayment::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);
        VendorPaymentAllocation::create([
            'vendor_payment_id' => $paymentA->id,
            'vendor_bill_id' => $this->bill->id,
            'allocated_amount' => 7000.00,
        ]);

        $paymentB = VendorPayment::create([
            'payment_number' => 'VPY-2026-0003',
            'vendor_id' => $this->vendor->id,
            'financial_account_id' => $this->cashAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 5000.00,
            'status' => VendorPayment::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);
        VendorPaymentAllocation::create([
            'vendor_payment_id' => $paymentB->id,
            'vendor_bill_id' => $this->bill->id,
            'allocated_amount' => 5000.00,
        ]);

        // Post Payment A: succeeds
        $this->paymentService->postPayment($paymentA, $this->superAdmin);
        $paymentA->refresh();
        $this->bill->refresh();
        $this->assertEquals(VendorPayment::STATUS_POSTED, $paymentA->status);
        $this->assertEquals(7000.00, (float) $this->bill->paid_amount);
        $this->assertEquals(3000.00, (float) $this->bill->outstanding_amount);

        // Post Payment B: must fail because available outstanding is now only 3,000 (< 5,000)
        try {
            $this->paymentService->postPayment($paymentB, $this->superAdmin);
            $this->fail('Expected ValidationException was not thrown for concurrent over-settlement.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('allocations', $e->errors());
        }

        // Bill state remains unchanged and protected
        $this->bill->refresh();
        $paymentB->refresh();

        $this->assertEquals(7000.00, (float) $this->bill->paid_amount);
        $this->assertEquals(3000.00, (float) $this->bill->outstanding_amount);
        $this->assertGreaterThanOrEqual(0.00, (float) $this->bill->outstanding_amount);
        $this->assertEquals(VendorPayment::STATUS_APPROVED, $paymentB->status);
        $this->assertNull($paymentB->journal_entry_id);
    }

    /**
     * Area 3: Multiple Payment Reversal
     * - Create a bill with multiple posted partial payments.
     * - Reverse one payment.
     * - Confirm the remaining payment allocation, paid amount, outstanding amount, and bill status remain correct.
     */
    public function test_area_3_multiple_partial_payments_and_selective_reversal(): void
    {
        // Bill total: 10,000
        // Payment 1: 4,000 (Bank)
        // Payment 2: 6,000 (Cash) -> Bill becomes fully paid

        $payment1 = VendorPayment::create([
            'payment_number' => 'VPY-2026-0004',
            'vendor_id' => $this->vendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 4000.00,
            'status' => VendorPayment::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);
        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment1->id,
            'vendor_bill_id' => $this->bill->id,
            'allocated_amount' => 4000.00,
        ]);

        $payment2 = VendorPayment::create([
            'payment_number' => 'VPY-2026-0005',
            'vendor_id' => $this->vendor->id,
            'financial_account_id' => $this->cashAccount->id,
            'payment_date' => '2026-11-03',
            'payment_method' => 'cash',
            'amount' => 6000.00,
            'status' => VendorPayment::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);
        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment2->id,
            'vendor_bill_id' => $this->bill->id,
            'allocated_amount' => 6000.00,
        ]);

        // Post both payments
        $this->paymentService->postPayment($payment1, $this->superAdmin);
        $this->paymentService->postPayment($payment2, $this->superAdmin);

        $this->bill->refresh();
        $this->assertEquals(10000.00, (float) $this->bill->paid_amount);
        $this->assertEquals('paid', $this->bill->payment_status);
        $this->assertEquals(0.00, (float) $this->bill->outstanding_amount);

        // Reverse Payment 2 (6,000)
        $this->paymentService->reversePayment($payment2, 'Cash recount error', $this->superAdmin);

        $this->bill->refresh();
        $payment2->refresh();
        $payment1->refresh();

        // Payment 2 is reversed
        $this->assertEquals(VendorPayment::STATUS_REVERSED, $payment2->status);

        // Payment 1 remains posted and active
        $this->assertEquals(VendorPayment::STATUS_POSTED, $payment1->status);

        // Bill state correctly reflects only Payment 1 remaining
        $this->assertEquals(4000.00, (float) $this->bill->paid_amount);
        $this->assertEquals('partially_paid', $this->bill->payment_status);
        $this->assertEquals(6000.00, (float) $this->bill->outstanding_amount);

        // Reverse Payment 1 (4,000)
        $this->paymentService->reversePayment($payment1, 'Bank transfer rejected', $this->superAdmin);

        $this->bill->refresh();
        $payment1->refresh();

        $this->assertEquals(VendorPayment::STATUS_REVERSED, $payment1->status);
        $this->assertEquals(0.00, (float) $this->bill->paid_amount);
        $this->assertEquals('unpaid', $this->bill->payment_status);
        $this->assertEquals(10000.00, (float) $this->bill->outstanding_amount);
    }

    /**
     * Area 4: Financial Account Validation
     * - Verify inactive accounts cannot be used.
     * - Verify only valid liquid asset accounts are accepted.
     * - Check both request validation and service-level enforcement.
     */
    public function test_area_4_financial_account_validation_request_and_service(): void
    {
        // 1. Request validation rejects inactive account
        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.store'), [
            'vendor_id' => $this->vendor->id,
            'financial_account_id' => $this->inactiveAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 1000.00,
            'allocations' => [
                ['vendor_bill_id' => $this->bill->id, 'allocated_amount' => 1000.00],
            ],
        ]);
        $response->assertSessionHasErrors('financial_account_id');

        // 2. Request validation rejects non-asset (liability) account
        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.store'), [
            'vendor_id' => $this->vendor->id,
            'financial_account_id' => $this->liabilityAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 1000.00,
            'allocations' => [
                ['vendor_bill_id' => $this->bill->id, 'allocated_amount' => 1000.00],
            ],
        ]);
        $response->assertSessionHasErrors('financial_account_id');

        // 3. Service layer directly rejects inactive account
        try {
            $this->paymentService->createPayment([
                'vendor_id' => $this->vendor->id,
                'financial_account_id' => $this->inactiveAccount->id,
                'payment_date' => '2026-11-02',
                'payment_method' => 'cash',
                'amount' => 1000.00,
                'allocations' => [
                    ['vendor_bill_id' => $this->bill->id, 'allocated_amount' => 1000.00],
                ],
            ], $this->treasurer);
            $this->fail('Service failed to reject inactive financial account.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('financial_account_id', $e->errors());
        }

        // 4. Service layer directly rejects non-asset liability account
        try {
            $this->paymentService->createPayment([
                'vendor_id' => $this->vendor->id,
                'financial_account_id' => $this->liabilityAccount->id,
                'payment_date' => '2026-11-02',
                'payment_method' => 'cash',
                'amount' => 1000.00,
                'allocations' => [
                    ['vendor_bill_id' => $this->bill->id, 'allocated_amount' => 1000.00],
                ],
            ], $this->treasurer);
            $this->fail('Service failed to reject non-asset financial account.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('financial_account_id', $e->errors());
        }
    }

    /**
     * Area 5: Approval and Posting Authorization
     * - Verify approveAndPostPayment() enforces permissions and conditions.
     * - Confirm verification and separation-of-duties rules cannot be bypassed.
     * - Confirm posting remains idempotent.
     */
    public function test_area_5_approve_and_post_authorization_and_idempotency(): void
    {
        // 1. User without both permissions cannot invoke approve-and-post HTTP endpoint
        // Devotee has neither permission
        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0006',
            'vendor_id' => $this->vendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 2000.00,
            'status' => VendorPayment::STATUS_VERIFIED,
            'created_by' => $this->templeAdmin->id,
        ]);
        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill->id,
            'allocated_amount' => 2000.00,
        ]);

        $response = $this->actingAs($this->devotee)->post(route('procurement.payments.approve-and-post', $payment));
        $response->assertStatus(403);

        // 2. Draft or Submitted payment cannot be approve-and-posted directly (cannot bypass verification)
        $draftPayment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0007',
            'vendor_id' => $this->vendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 1500.00,
            'status' => VendorPayment::STATUS_DRAFT,
            'created_by' => $this->templeAdmin->id,
        ]);
        VendorPaymentAllocation::create([
            'vendor_payment_id' => $draftPayment->id,
            'vendor_bill_id' => $this->bill->id,
            'allocated_amount' => 1500.00,
        ]);

        try {
            $this->paymentService->approveAndPostPayment($draftPayment, $this->superAdmin);
            $this->fail('Expected ValidationException when approving an unverified draft payment.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        // 3. Creator cannot approve-and-post own payment (separation of duties)
        $paymentCreatedByTreasurer = VendorPayment::create([
            'payment_number' => 'VPY-2026-0008',
            'vendor_id' => $this->vendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 2500.00,
            'status' => VendorPayment::STATUS_VERIFIED,
            'created_by' => $this->treasurer->id,
        ]);
        VendorPaymentAllocation::create([
            'vendor_payment_id' => $paymentCreatedByTreasurer->id,
            'vendor_bill_id' => $this->bill->id,
            'allocated_amount' => 2500.00,
        ]);

        try {
            $this->paymentService->approveAndPostPayment($paymentCreatedByTreasurer, $this->treasurer);
            $this->fail('Expected separation of duties ValidationException for creator self-approval.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('approver', $e->errors());
        }

        // 4. Authorized SuperAdmin can approveAndPostPayment() successfully
        $posted = $this->paymentService->approveAndPostPayment($paymentCreatedByTreasurer, $this->superAdmin);
        $this->assertEquals(VendorPayment::STATUS_POSTED, $posted->status);
        $this->assertNotNull($posted->journal_entry_id);

        // 5. Idempotency test: calling postPayment() again on an already posted payment
        $initialJournalsCount = JournalEntry::where('source_type', VendorPayment::class)->count();
        $this->assertEquals(1, $initialJournalsCount);

        // Call posting service again directly
        $reposted = $this->postingService->postVendorPayment($posted, $this->superAdmin->id);
        $this->assertEquals($posted->journal_entry_id, $reposted->id);

        $finalJournalsCount = JournalEntry::where('source_type', VendorPayment::class)->count();
        $this->assertEquals(1, $finalJournalsCount);
    }
}
