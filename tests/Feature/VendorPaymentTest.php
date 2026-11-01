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
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $templeAdmin;

    protected User $devotee;

    protected Vendor $activeVendor;

    protected Vendor $inactiveVendor;

    protected FinancialAccount $cashAccount;

    protected FinancialAccount $bankAccount;

    protected FinancialAccount $apAccount;

    protected FinancialAccount $expenseAccount;

    protected VendorBill $bill1;

    protected VendorBill $bill2;

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

        $this->apAccount = FinancialAccount::create([
            'account_code' => FinancialAccount::CODE_AP,
            'account_name_en' => 'Accounts Payable',
            'account_name_bn' => 'প্রদেয় হিসাব',
            'account_type' => FinancialAccount::TYPE_LIABILITY,
            'normal_balance' => 'credit',
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->expenseAccount = FinancialAccount::create([
            'account_code' => '5101',
            'account_name_en' => 'Puja Materials',
            'account_name_bn' => 'পূজা সামগ্রী',
            'account_type' => FinancialAccount::TYPE_EXPENSE,
            'normal_balance' => 'debit',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->activeVendor = Vendor::create([
            'vendor_code' => 'VND-0001',
            'name' => 'Roy Sweet & Store',
            'contact_person' => 'Subhash Roy',
            'phone' => '01711000001',
            'vendor_type' => 'supplier',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->inactiveVendor = Vendor::create([
            'vendor_code' => 'VND-0002',
            'name' => 'Inactive Vendor Co',
            'contact_person' => 'N/A',
            'phone' => '01711000002',
            'vendor_type' => 'supplier',
            'is_active' => false,
            'created_by' => $this->superAdmin->id,
        ]);

        // Create approved bills with outstanding balances
        $this->bill1 = VendorBill::create([
            'bill_number' => 'BIL-2026-0001',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-001',
            'bill_date' => '2026-11-01',
            'due_date' => '2026-11-15',
            'subtotal' => 10000.00,
            'total_amount' => 10000.00,
            'paid_amount' => 0.00,
            'status' => VendorBill::STATUS_APPROVED,
            'payment_status' => 'unpaid',
            'created_by' => $this->treasurer->id,
        ]);

        $this->bill2 = VendorBill::create([
            'bill_number' => 'BIL-2026-0002',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-002',
            'bill_date' => '2026-11-02',
            'due_date' => '2026-11-20',
            'subtotal' => 5000.00,
            'total_amount' => 5000.00,
            'paid_amount' => 0.00,
            'status' => VendorBill::STATUS_APPROVED,
            'payment_status' => 'unpaid',
            'created_by' => $this->treasurer->id,
        ]);
    }

    public function test_guest_cannot_access_vendor_payments(): void
    {
        $response = $this->get(route('procurement.payments.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('procurement.payments.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_cannot_access_vendor_payments(): void
    {
        $response = $this->actingAs($this->devotee)->get(route('procurement.payments.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->devotee)->get(route('procurement.payments.create'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_payments_index_and_create_page(): void
    {
        $response = $this->actingAs($this->treasurer)->get(route('procurement.payments.index'));
        $response->assertStatus(200);
        $response->assertSee(__('procurement.vendor_payments'));

        $response = $this->actingAs($this->treasurer)->get(route('procurement.payments.create'));
        $response->assertStatus(200);
        $response->assertSee(__('procurement.payment_information'));
    }

    public function test_user_can_create_vendor_payment_with_unique_payment_number(): void
    {
        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 6000.00,
            'reference_number' => 'TRX-998877',
            'notes' => 'Settlement for bill 1 and partial bill 2',
            'allocations' => [
                [
                    'vendor_bill_id' => $this->bill1->id,
                    'allocated_amount' => 4000.00,
                ],
                [
                    'vendor_bill_id' => $this->bill2->id,
                    'allocated_amount' => 2000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.store'), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('vendor_payments', [
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_method' => 'bank_transfer',
            'amount' => 6000.00,
            'status' => 'draft',
            'reference_number' => 'TRX-998877',
        ]);

        $payment = VendorPayment::first();
        $this->assertNotNull($payment);
        $this->assertMatchesRegularExpression('/^VPY-\d{4}-\d{4}$/', $payment->payment_number);
        $this->assertCount(2, $payment->allocations);
    }

    public function test_payment_validation_rejects_inactive_vendor(): void
    {
        $payload = [
            'vendor_id' => $this->inactiveVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 1000.00,
            'allocations' => [
                [
                    'vendor_bill_id' => $this->bill1->id,
                    'allocated_amount' => 1000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.store'), $payload);
        $response->assertSessionHasErrors('vendor_id');
    }

    public function test_payment_validation_rejects_invalid_non_asset_account(): void
    {
        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->apAccount->id, // AP is liability, not asset
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 1000.00,
            'allocations' => [
                [
                    'vendor_bill_id' => $this->bill1->id,
                    'allocated_amount' => 1000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.store'), $payload);
        $response->assertSessionHasErrors('financial_account_id');
    }

    public function test_payment_validation_requires_positive_amount(): void
    {
        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 0.00,
            'allocations' => [
                [
                    'vendor_bill_id' => $this->bill1->id,
                    'allocated_amount' => 0.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.store'), $payload);
        $response->assertSessionHasErrors('amount');
    }

    public function test_payment_validation_requires_at_least_one_bill_allocation(): void
    {
        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 1000.00,
            'allocations' => [],
        ];

        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.store'), $payload);
        $response->assertSessionHasErrors('allocations');
    }

    public function test_sum_of_allocations_must_equal_payment_amount(): void
    {
        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 5000.00, // Allocation sum is 4000.00 (mismatch)
            'allocations' => [
                [
                    'vendor_bill_id' => $this->bill1->id,
                    'allocated_amount' => 4000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.store'), $payload);
        $response->assertSessionHasErrors('allocations');
    }

    public function test_allocation_cannot_exceed_bill_outstanding_balance(): void
    {
        // Bill 2 total is 5000. Trying to allocate 6000
        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 6000.00,
            'allocations' => [
                [
                    'vendor_bill_id' => $this->bill2->id,
                    'allocated_amount' => 6000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.store'), $payload);
        $response->assertSessionHasErrors('allocations');
    }

    public function test_cannot_allocate_to_unapproved_bill(): void
    {
        $draftBill = VendorBill::create([
            'bill_number' => 'BIL-2026-0003',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-003',
            'bill_date' => '2026-11-02',
            'subtotal' => 2000.00,
            'total_amount' => 2000.00,
            'status' => VendorBill::STATUS_DRAFT,
            'created_by' => $this->treasurer->id,
        ]);

        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 2000.00,
            'allocations' => [
                [
                    'vendor_bill_id' => $draftBill->id,
                    'allocated_amount' => 2000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.store'), $payload);
        $response->assertSessionHasErrors('allocations');
    }

    public function test_cannot_allocate_bill_belonging_to_different_vendor(): void
    {
        $otherVendor = Vendor::create([
            'vendor_code' => 'VND-0003',
            'name' => 'Other Vendor',
            'contact_person' => 'Other',
            'phone' => '01711000003',
            'vendor_type' => 'supplier',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $otherBill = VendorBill::create([
            'bill_number' => 'BIL-2026-0004',
            'vendor_id' => $otherVendor->id,
            'vendor_invoice_number' => 'INV-OTH-1',
            'bill_date' => '2026-11-02',
            'subtotal' => 3000.00,
            'total_amount' => 3000.00,
            'status' => VendorBill::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);

        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 3000.00,
            'allocations' => [
                [
                    'vendor_bill_id' => $otherBill->id,
                    'allocated_amount' => 3000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.store'), $payload);
        $response->assertSessionHasErrors('allocations');
    }

    public function test_vendor_payment_workflow_draft_to_submitted_to_verified_to_approved(): void
    {
        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0001',
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 5000.00,
            'status' => VendorPayment::STATUS_DRAFT,
            'created_by' => $this->treasurer->id,
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill1->id,
            'allocated_amount' => 5000.00,
        ]);

        // Submit
        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.submit', $payment));
        $response->assertRedirect();
        $payment->refresh();
        $this->assertEquals(VendorPayment::STATUS_SUBMITTED, $payment->status);
        $this->assertEquals($this->treasurer->id, $payment->submitted_by);

        // Verify
        $response = $this->actingAs($this->templeAdmin)->post(route('procurement.payments.verify', $payment), [
            'verification_notes' => 'Bank account and invoice details verified.',
        ]);
        $response->assertRedirect();
        $payment->refresh();
        $this->assertEquals(VendorPayment::STATUS_VERIFIED, $payment->status);
        $this->assertEquals($this->templeAdmin->id, $payment->verified_by);

        // Approve (by superAdmin)
        $response = $this->actingAs($this->superAdmin)->post(route('procurement.payments.approve', $payment));
        $response->assertRedirect();
        $payment->refresh();
        $this->assertEquals(VendorPayment::STATUS_APPROVED, $payment->status);
        $this->assertEquals($this->superAdmin->id, $payment->approved_by);
    }

    public function test_strict_separation_of_duties_prevents_creator_from_self_approving_payment(): void
    {
        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0002',
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 3000.00,
            'status' => VendorPayment::STATUS_VERIFIED,
            'created_by' => $this->treasurer->id,
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill1->id,
            'allocated_amount' => 3000.00,
        ]);

        // Treasurer created the payment, so Treasurer cannot approve it
        $response = $this->actingAs($this->treasurer)->post(route('procurement.payments.approve', $payment));
        $response->assertSessionHasErrors('approver');

        $payment->refresh();
        $this->assertEquals(VendorPayment::STATUS_VERIFIED, $payment->status);
    }

    public function test_super_admin_can_approve_own_payment(): void
    {
        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0003',
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 2000.00,
            'status' => VendorPayment::STATUS_VERIFIED,
            'created_by' => $this->superAdmin->id,
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill1->id,
            'allocated_amount' => 2000.00,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('procurement.payments.approve', $payment));
        $response->assertRedirect();

        $payment->refresh();
        $this->assertEquals(VendorPayment::STATUS_APPROVED, $payment->status);
    }

    public function test_rejection_requires_mandatory_reason(): void
    {
        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0004',
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 2000.00,
            'status' => VendorPayment::STATUS_SUBMITTED,
            'created_by' => $this->treasurer->id,
        ]);

        // Attempt without reason
        $response = $this->actingAs($this->superAdmin)->post(route('procurement.payments.reject', $payment), [
            'rejection_reason' => '',
        ]);
        $response->assertSessionHasErrors('rejection_reason');

        // With reason
        $response = $this->actingAs($this->superAdmin)->post(route('procurement.payments.reject', $payment), [
            'rejection_reason' => 'Incorrect beneficiary bank account number.',
        ]);
        $response->assertRedirect();

        $payment->refresh();
        $this->assertEquals(VendorPayment::STATUS_REJECTED, $payment->status);
        $this->assertEquals('Incorrect beneficiary bank account number.', $payment->rejection_reason);
    }

    public function test_unposted_payment_cancellation_requires_mandatory_reason(): void
    {
        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0005',
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 1500.00,
            'status' => VendorPayment::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);

        // Attempt without reason
        $response = $this->actingAs($this->superAdmin)->post(route('procurement.payments.cancel', $payment), [
            'cancellation_reason' => '',
        ]);
        $response->assertSessionHasErrors('cancellation_reason');

        // With reason
        $response = $this->actingAs($this->superAdmin)->post(route('procurement.payments.cancel', $payment), [
            'cancellation_reason' => 'Cancelled due to vendor bank account dispute.',
        ]);
        $response->assertRedirect();

        $payment->refresh();
        $this->assertEquals(VendorPayment::STATUS_CANCELLED, $payment->status);
    }

    public function test_payment_posting_creates_double_entry_debit_ap_and_credit_liquid_asset(): void
    {
        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0006',
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 5000.00,
            'status' => VendorPayment::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill1->id,
            'allocated_amount' => 5000.00,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('procurement.payments.post', $payment));
        $response->assertRedirect();

        $payment->refresh();
        $this->assertEquals(VendorPayment::STATUS_POSTED, $payment->status);
        $this->assertNotNull($payment->journal_entry_id);

        $journal = JournalEntry::find($payment->journal_entry_id);
        $this->assertNotNull($journal);
        $this->assertEquals(5000.00, (float) $journal->total_debit);
        $this->assertEquals(5000.00, (float) $journal->total_credit);

        // Verify balanced double-entry
        $lines = $journal->lines;
        $this->assertCount(2, $lines);

        $debitLine = $lines->firstWhere('debit_amount', '>', 0);
        $creditLine = $lines->firstWhere('credit_amount', '>', 0);

        $this->assertNotNull($debitLine);
        $this->assertNotNull($creditLine);

        // Debit: Accounts Payable (2010)
        $this->assertEquals($this->apAccount->id, $debitLine->financial_account_id);
        $this->assertEquals(5000.00, (float) $debitLine->debit_amount);

        // Credit: Bank Account (1020)
        $this->assertEquals($this->bankAccount->id, $creditLine->financial_account_id);
        $this->assertEquals(5000.00, (float) $creditLine->credit_amount);
    }

    public function test_payment_posting_atomically_updates_bill_paid_amount_and_status(): void
    {
        // Bill 1: 10000 total. Pay 4000 (partially paid)
        // Bill 2: 5000 total. Pay 5000 (fully paid)
        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0007',
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->cashAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 9000.00,
            'status' => VendorPayment::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill1->id,
            'allocated_amount' => 4000.00,
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill2->id,
            'allocated_amount' => 5000.00,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('procurement.payments.post', $payment));
        $response->assertRedirect();

        $this->bill1->refresh();
        $this->bill2->refresh();

        $this->assertEquals(4000.00, (float) $this->bill1->paid_amount);
        $this->assertEquals('partially_paid', $this->bill1->payment_status);
        $this->assertTrue($this->bill1->isPartiallyPaid());
        $this->assertEquals(6000.00, (float) $this->bill1->outstanding_amount);

        $this->assertEquals(5000.00, (float) $this->bill2->paid_amount);
        $this->assertEquals('paid', $this->bill2->payment_status);
        $this->assertTrue($this->bill2->isFullyPaid());
        $this->assertEquals(0.00, (float) $this->bill2->outstanding_amount);
    }

    public function test_repeated_posting_does_not_duplicate_journal_entry(): void
    {
        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0008',
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->cashAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 2000.00,
            'status' => VendorPayment::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill1->id,
            'allocated_amount' => 2000.00,
        ]);

        // First post
        $this->actingAs($this->superAdmin)->post(route('procurement.payments.post', $payment));
        $initialJournalsCount = JournalEntry::where('source_type', VendorPayment::class)->count();
        $this->assertEquals(1, $initialJournalsCount);

        // Second post attempt (idempotent / should not double post)
        $this->actingAs($this->superAdmin)->post(route('procurement.payments.post', $payment));
        $finalJournalsCount = JournalEntry::where('source_type', VendorPayment::class)->count();
        $this->assertEquals(1, $finalJournalsCount);
    }

    public function test_posted_payment_reversal_reverses_journal_and_restores_bill_balances(): void
    {
        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0009',
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->cashAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 5000.00,
            'status' => VendorPayment::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill2->id,
            'allocated_amount' => 5000.00,
        ]);

        // Post payment
        $this->actingAs($this->superAdmin)->post(route('procurement.payments.post', $payment));
        $payment->refresh();
        $this->bill2->refresh();
        $this->assertEquals('paid', $this->bill2->payment_status);
        $this->assertEquals(5000.00, (float) $this->bill2->paid_amount);

        // Reverse payment
        $response = $this->actingAs($this->superAdmin)->post(route('procurement.payments.reverse', $payment), [
            'reversal_reason' => 'Cheque returned by bank due to signature mismatch.',
        ]);
        $response->assertRedirect();

        $payment->refresh();
        $this->bill2->refresh();

        $this->assertEquals(VendorPayment::STATUS_REVERSED, $payment->status);
        $this->assertNotNull($payment->reversal_journal_entry_id);
        $this->assertEquals('Cheque returned by bank due to signature mismatch.', $payment->reversal_reason);

        // Bill balances restored
        $this->assertEquals(0.00, (float) $this->bill2->paid_amount);
        $this->assertEquals('unpaid', $this->bill2->payment_status);
        $this->assertEquals(5000.00, (float) $this->bill2->outstanding_amount);

        // Reversal journal verified
        $reversal = JournalEntry::find($payment->reversal_journal_entry_id);
        $this->assertNotNull($reversal);
        $this->assertEquals(5000.00, (float) $reversal->total_debit);
        $this->assertEquals(5000.00, (float) $reversal->total_credit);
    }

    public function test_vendor_outstanding_calculation_correctly_reflects_posted_payments(): void
    {
        // Initially: bill1=10000, bill2=5000. Total billed = 15000, paid = 0, outstanding = 15000
        $metricsInitial = $this->activeVendor->getBillMetrics();
        $this->assertEquals(15000.00, $metricsInitial['total_billed']);
        $this->assertEquals(0.00, $metricsInitial['total_paid']);
        $this->assertEquals(15000.00, $metricsInitial['total_outstanding']);

        // Post payment of 6000 (4000 to bill 1, 2000 to bill 2)
        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0010',
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 6000.00,
            'status' => VendorPayment::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill1->id,
            'allocated_amount' => 4000.00,
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill2->id,
            'allocated_amount' => 2000.00,
        ]);

        $this->actingAs($this->superAdmin)->post(route('procurement.payments.post', $payment));

        $metricsAfter = $this->activeVendor->getBillMetrics();
        $this->assertEquals(15000.00, $metricsAfter['total_billed']);
        $this->assertEquals(6000.00, $metricsAfter['total_paid']);
        $this->assertEquals(9000.00, $metricsAfter['total_outstanding']);
    }

    public function test_vendor_payments_datatable_server_side_json_strictly_has_no_row_number_column(): void
    {
        VendorPayment::create([
            'payment_number' => 'VPY-2026-0011',
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->bankAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'bank_transfer',
            'amount' => 1000.00,
            'status' => VendorPayment::STATUS_DRAFT,
            'created_by' => $this->treasurer->id,
        ]);

        $response = $this->actingAs($this->treasurer)
            ->getJson(route('procurement.payments.index'), ['HTTP_X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayHasKey('data', $json);
        $this->assertNotEmpty($json['data']);

        $firstRow = $json['data'][0];

        // STRICT INVARIANT: Must NOT contain #, sl, or row_number column
        $this->assertArrayNotHasKey('#', $firstRow);
        $this->assertArrayNotHasKey('sl', $firstRow);
        $this->assertArrayNotHasKey('sl_no', $firstRow);
        $this->assertArrayNotHasKey('row_number', $firstRow);
        $this->assertArrayNotHasKey('index', $firstRow);

        // Required semantic columns
        $this->assertArrayHasKey('payment_number', $firstRow);
        $this->assertArrayHasKey('vendor', $firstRow);
        $this->assertArrayHasKey('payment_date', $firstRow);
        $this->assertArrayHasKey('payment_method', $firstRow);
        $this->assertArrayHasKey('financial_account', $firstRow);
        $this->assertArrayHasKey('amount', $firstRow);
        $this->assertArrayHasKey('status', $firstRow);
        $this->assertArrayHasKey('actions', $firstRow);
    }

    public function test_bengali_locale_renders_payment_views_without_errors(): void
    {
        app()->setLocale('bn');

        $payment = VendorPayment::create([
            'payment_number' => 'VPY-2026-0012',
            'vendor_id' => $this->activeVendor->id,
            'financial_account_id' => $this->cashAccount->id,
            'payment_date' => '2026-11-02',
            'payment_method' => 'cash',
            'amount' => 5000.00,
            'status' => VendorPayment::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $payment->id,
            'vendor_bill_id' => $this->bill1->id,
            'allocated_amount' => 5000.00,
        ]);

        $response = $this->actingAs($this->treasurer)->get(route('procurement.payments.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->treasurer)->get(route('procurement.payments.create'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->treasurer)->get(route('procurement.payments.show', $payment));
        $response->assertStatus(200);

        $response = $this->actingAs($this->treasurer)->get(route('procurement.payments.print', $payment));
        $response->assertStatus(200);
        $response->assertSee('শ্রী শ্রী রাধাকৃষ্ণ মন্দির');
    }

    public function test_load_payable_bills_endpoint_returns_eligible_approved_bills(): void
    {
        $response = $this->actingAs($this->treasurer)
            ->getJson(route('procurement.payments.payable-bills', $this->activeVendor));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $bills = $response->json('bills');
        $this->assertCount(2, $bills);
        $this->assertEquals('BIL-2026-0001', $bills[0]['bill_number']);
        $this->assertEquals(10000.00, $bills[0]['outstanding_amount']);
    }
}
