<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Models\VendorBillItem;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorBillTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $templeAdmin;

    protected User $devotee;

    protected Event $event;

    protected FinancialAccount $expenseAccount1;

    protected FinancialAccount $expenseAccount2;

    protected FinancialAccount $apAccount;

    protected FinancialAccount $cashAccount;

    protected Budget $approvedBudget;

    protected Vendor $activeVendor;

    protected Vendor $inactiveVendor;

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

        $category = EventCategory::create([
            'name_en' => 'Religious Puja',
            'name_bn' => 'ধর্মীয় পূজা',
            'slug' => 'religious-puja-'.uniqid(),
            'is_active' => true,
        ]);

        $this->event = Event::create([
            'event_code' => 'EVT-2026-0001',
            'name_en' => 'Kali Puja 2026',
            'name_bn' => 'শ্যামাপূজা ২০২৬',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->expenseAccount1 = FinancialAccount::create([
            'account_code' => '5101',
            'account_name_en' => 'Puja Materials & Flowers',
            'account_name_bn' => 'পূজা সামগ্রী ও ফুল',
            'account_type' => 'expense',
            'normal_balance' => 'debit',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->expenseAccount2 = FinancialAccount::create([
            'account_code' => '5102',
            'account_name_en' => 'Fruits & Bhog Prasad',
            'account_name_bn' => 'ফল ও ভোগ প্রসাদ',
            'account_type' => 'expense',
            'normal_balance' => 'debit',
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

        $this->cashAccount = FinancialAccount::create([
            'account_code' => '1010',
            'account_name_en' => 'Cash in Hand',
            'account_name_bn' => 'হাতে নগদ',
            'account_type' => FinancialAccount::TYPE_ASSET,
            'normal_balance' => 'debit',
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->approvedBudget = Budget::create([
            'budget_code' => 'BDG-2026-0001',
            'budget_type' => 'event',
            'event_id' => $this->event->id,
            'fiscal_year' => 2026,
            'title_en' => 'Kali Puja Approved Budget',
            'title_bn' => 'শ্যামাপূজা অনুমোদিত বাজেট',
            'total_amount' => 150000.00,
            'status' => Budget::STATUS_APPROVED,
            'created_by' => $this->superAdmin->id,
            'approved_by' => $this->treasurer->id,
            'approved_at' => now(),
        ]);

        BudgetItem::create([
            'budget_id' => $this->approvedBudget->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'allocated_amount' => 80000.00,
        ]);

        BudgetItem::create([
            'budget_id' => $this->approvedBudget->id,
            'financial_account_id' => $this->expenseAccount2->id,
            'allocated_amount' => 70000.00,
        ]);

        $this->activeVendor = Vendor::create([
            'vendor_code' => 'VND-0001',
            'name' => 'Puja Bhandar Enterprise',
            'contact_person' => 'Subhashish Roy',
            'phone' => '01711223344',
            'email' => 'subhashish@example.com',
            'address' => 'Shankhari Bazar, Dhaka',
            'vendor_type' => 'Goods Supplier',
            'tax_id' => 'TRD-DH-987654',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->inactiveVendor = Vendor::create([
            'vendor_code' => 'VND-0002',
            'name' => 'Inactive Flower Mart',
            'contact_person' => 'Gouranga Das',
            'phone' => '01811223344',
            'is_active' => false,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    /**
     * Helper to create an approved PO.
     */
    protected function createApprovedPo(Vendor $vendor, float $qty1 = 100, float $price1 = 50): PurchaseOrder
    {
        $po = PurchaseOrder::create([
            'order_number' => 'PO-2026-0001',
            'vendor_id' => $vendor->id,
            'order_date' => '2026-11-01',
            'event_id' => $this->event->id,
            'budget_id' => $this->approvedBudget->id,
            'total_amount' => $qty1 * $price1,
            'subtotal' => $qty1 * $price1,
            'status' => PurchaseOrder::STATUS_APPROVED,
            'created_by' => $this->templeAdmin->id,
            'approved_by' => $this->treasurer->id,
            'approved_at' => now(),
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Pure Ghee for Kali Puja',
            'quantity' => $qty1,
            'unit' => 'kg',
            'unit_price' => $price1,
            'line_total' => $qty1 * $price1,
        ]);

        return $po;
    }

    public function test_guest_cannot_access_vendor_bills(): void
    {
        $response = $this->get(route('procurement.bills.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_cannot_access_vendor_bills(): void
    {
        $response = $this->actingAs($this->devotee)->get(route('procurement.bills.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_bills_index_and_create_page(): void
    {
        $response = $this->actingAs($this->templeAdmin)->get(route('procurement.bills.index'));
        $response->assertStatus(200);
        $response->assertSee(__('procurement.bill_list'));

        $createResponse = $this->actingAs($this->templeAdmin)->get(route('procurement.bills.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee(__('procurement.create_bill'));
    }

    public function test_user_can_create_vendor_bill_with_unique_bill_number(): void
    {
        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-2026-101',
            'bill_date' => '2026-11-01',
            'due_date' => '2026-11-15',
            'event_id' => $this->event->id,
            'budget_id' => $this->approvedBudget->id,
            'items' => [
                [
                    'item_description' => 'Incense Sticks & Camphor',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 10,
                    'unit' => 'packs',
                    'unit_price' => 150.00,
                ],
                [
                    'item_description' => 'Fresh Apples for Bhog',
                    'financial_account_id' => $this->expenseAccount2->id,
                    'quantity' => 20,
                    'unit' => 'kg',
                    'unit_price' => 200.00,
                ],
            ],
            'tax_amount' => 100.00,
            'discount_amount' => 50.00,
            'notes' => 'Urgent procurement for Diwali Kali Puja',
        ];

        $response = $this->actingAs($this->templeAdmin)->post(route('procurement.bills.store'), $payload);
        $response->assertSessionHasNoErrors();

        $bill = VendorBill::where('vendor_invoice_number', 'INV-2026-101')->first();
        $this->assertNotNull($bill);
        $this->assertStringStartsWith('BIL-2026-', $bill->bill_number);
        $this->assertEquals(VendorBill::STATUS_DRAFT, $bill->status);
        $this->assertEquals(5500.00, (float) $bill->subtotal); // 10*150 + 20*200 = 1500 + 4000 = 5500
        $this->assertEquals(5550.00, (float) $bill->total_amount); // 5500 + 100 - 50 = 5550
        $this->assertCount(2, $bill->items);
    }

    public function test_bill_validation_rejects_inactive_vendor(): void
    {
        $payload = [
            'vendor_id' => $this->inactiveVendor->id,
            'vendor_invoice_number' => 'INV-INACT-001',
            'bill_date' => '2026-11-01',
            'items' => [
                [
                    'item_description' => 'Test item',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 5,
                    'unit_price' => 100,
                ],
            ],
        ];

        $response = $this->actingAs($this->templeAdmin)->post(route('procurement.bills.store'), $payload);
        $response->assertSessionHasErrors('vendor_id');
    }

    public function test_duplicate_vendor_invoice_number_for_same_vendor_is_rejected(): void
    {
        // First bill
        VendorBill::create([
            'bill_number' => 'BIL-2026-0001',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'DUPLICATE-INV-999',
            'bill_date' => '2026-11-01',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'status' => VendorBill::STATUS_DRAFT,
            'created_by' => $this->templeAdmin->id,
        ]);

        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'DUPLICATE-INV-999',
            'bill_date' => '2026-11-01',
            'items' => [
                [
                    'item_description' => 'Another bill item',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ];

        $response = $this->actingAs($this->templeAdmin)->post(route('procurement.bills.store'), $payload);
        $response->assertSessionHasErrors('vendor_invoice_number');
    }

    public function test_po_vendor_mismatch_is_rejected(): void
    {
        $po = $this->createApprovedPo($this->activeVendor);

        $otherVendor = Vendor::create([
            'vendor_code' => 'VND-0003',
            'name' => 'Different Vendor Ltd',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $payload = [
            'vendor_id' => $otherVendor->id,
            'purchase_order_id' => $po->id,
            'vendor_invoice_number' => 'INV-MISMATCH-1',
            'bill_date' => '2026-11-01',
            'items' => [
                [
                    'item_description' => 'Item',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 5,
                    'unit_price' => 50,
                ],
            ],
        ];

        $response = $this->actingAs($this->templeAdmin)->post(route('procurement.bills.store'), $payload);
        $response->assertSessionHasErrors('purchase_order_id');
    }

    public function test_po_status_ineligible_for_billing_is_rejected(): void
    {
        $draftPo = PurchaseOrder::create([
            'order_number' => 'PO-2026-0099',
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-11-01',
            'total_amount' => 1000.00,
            'status' => PurchaseOrder::STATUS_DRAFT, // Draft PO cannot be billed
            'created_by' => $this->templeAdmin->id,
        ]);

        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'purchase_order_id' => $draftPo->id,
            'vendor_invoice_number' => 'INV-DRAFT-PO',
            'bill_date' => '2026-11-01',
            'items' => [
                [
                    'item_description' => 'Item',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 5,
                    'unit_price' => 50,
                ],
            ],
        ];

        $response = $this->actingAs($this->templeAdmin)->post(route('procurement.bills.store'), $payload);
        $response->assertSessionHasErrors('purchase_order_id');
    }

    public function test_partial_billing_from_po_succeeds(): void
    {
        $po = $this->createApprovedPo($this->activeVendor, 100, 50); // 100 kg @ 50 = 5000
        $poItem = $po->items->first();

        // Bill first 40 kg
        $payload1 = [
            'vendor_id' => $this->activeVendor->id,
            'purchase_order_id' => $po->id,
            'vendor_invoice_number' => 'INV-PO-PART-1',
            'bill_date' => '2026-11-01',
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'item_description' => 'Pure Ghee (Part 1)',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 40,
                    'unit' => 'kg',
                    'unit_price' => 50,
                ],
            ],
        ];

        $response1 = $this->actingAs($this->templeAdmin)->post(route('procurement.bills.store'), $payload1);
        $response1->assertSessionHasNoErrors();

        $this->assertEquals(40.0, (float) $poItem->fresh()->billed_quantity);
        $this->assertEquals(60.0, (float) $poItem->fresh()->remaining_quantity);
        $this->assertEquals(2000.0, (float) $po->fresh()->billed_amount);
        $this->assertEquals(3000.0, (float) $po->fresh()->remaining_billable_amount);
        $this->assertTrue($po->fresh()->canBeBilled());
    }

    public function test_cumulative_over_billing_beyond_po_quantity_is_prevented(): void
    {
        $po = $this->createApprovedPo($this->activeVendor, 50, 100); // 50 kg total
        $poItem = $po->items->first();

        // Bill 30 kg first
        $payload1 = [
            'vendor_id' => $this->activeVendor->id,
            'purchase_order_id' => $po->id,
            'vendor_invoice_number' => 'INV-OVER-1',
            'bill_date' => '2026-11-01',
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'item_description' => 'Item Part 1',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 30,
                    'unit_price' => 100,
                ],
            ],
        ];
        $this->actingAs($this->templeAdmin)->post(route('procurement.bills.store'), $payload1);

        // Attempt to bill 25 kg more (30 + 25 = 55 > 50)
        $payload2 = [
            'vendor_id' => $this->activeVendor->id,
            'purchase_order_id' => $po->id,
            'vendor_invoice_number' => 'INV-OVER-2',
            'bill_date' => '2026-11-02',
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'item_description' => 'Item Part 2 Exceeding',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 25,
                    'unit_price' => 100,
                ],
            ],
        ];

        $response2 = $this->actingAs($this->templeAdmin)->post(route('procurement.bills.store'), $payload2);
        $response2->assertSessionHasErrors('items');
    }

    public function test_server_side_calculation_ignores_tampered_client_totals(): void
    {
        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-TAMPER-001',
            'bill_date' => '2026-11-01',
            'subtotal' => 1.00, // Tampered client value
            'total_amount' => 1.00, // Tampered client value
            'items' => [
                [
                    'item_description' => 'Gold Leaf for Kali Idol',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 5,
                    'unit_price' => 1200.00, // 5 * 1200 = 6000
                ],
            ],
            'tax_amount' => 300.00,
            'discount_amount' => 100.00,
        ];

        $this->actingAs($this->templeAdmin)->post(route('procurement.bills.store'), $payload);

        $bill = VendorBill::where('vendor_invoice_number', 'INV-TAMPER-001')->first();
        $this->assertNotNull($bill);
        $this->assertEquals(6000.00, (float) $bill->subtotal);
        $this->assertEquals(6200.00, (float) $bill->total_amount); // 6000 + 300 - 100 = 6200
    }

    public function test_bill_validation_rejects_invalid_expense_account(): void
    {
        // Try to use Accounts Payable (liability) as an expense line account
        $payload = [
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-BAD-ACCOUNT',
            'bill_date' => '2026-11-01',
            'items' => [
                [
                    'item_description' => 'Wrong account test',
                    'financial_account_id' => $this->apAccount->id, // Not an expense account
                    'quantity' => 1,
                    'unit_price' => 500,
                ],
            ],
        ];

        $response = $this->actingAs($this->templeAdmin)->post(route('procurement.bills.store'), $payload);
        $response->assertSessionHasErrors('items');
    }

    public function test_vendor_bill_workflow_draft_to_submitted_to_verified_to_approved(): void
    {
        // 1. Create Draft
        $bill = VendorBill::create([
            'bill_number' => 'BIL-2026-0010',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-FLOW-01',
            'bill_date' => '2026-11-01',
            'subtotal' => 2000.00,
            'total_amount' => 2000.00,
            'status' => VendorBill::STATUS_DRAFT,
            'created_by' => $this->templeAdmin->id,
        ]);

        VendorBillItem::create([
            'vendor_bill_id' => $bill->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Workflow Item',
            'quantity' => 10,
            'unit_price' => 200.00,
            'line_total' => 2000.00,
        ]);

        // 2. Submit
        $submitRes = $this->actingAs($this->templeAdmin)->post(route('procurement.bills.submit', $bill));
        $submitRes->assertRedirect(route('procurement.bills.show', $bill));
        $this->assertEquals(VendorBill::STATUS_SUBMITTED, $bill->fresh()->status);
        $this->assertNotNull($bill->fresh()->submitted_at);

        // 3. Verify
        $verifyRes = $this->actingAs($this->treasurer)->post(route('procurement.bills.verify', $bill), [
            'verification_notes' => 'Checked against physical delivery challan.',
        ]);
        $verifyRes->assertRedirect(route('procurement.bills.show', $bill));
        $this->assertEquals(VendorBill::STATUS_VERIFIED, $bill->fresh()->status);
        $this->assertEquals('Checked against physical delivery challan.', $bill->fresh()->verification_notes);

        // 4. Approve
        $approveRes = $this->actingAs($this->treasurer)->post(route('procurement.bills.approve', $bill));
        $approveRes->assertRedirect(route('procurement.bills.show', $bill));
        $this->assertEquals(VendorBill::STATUS_APPROVED, $bill->fresh()->status);
        $this->assertNotNull($bill->fresh()->approved_at);
        $this->assertNotNull($bill->fresh()->journal_entry_id);
    }

    public function test_strict_separation_of_duties_prevents_creator_from_self_approving_bill(): void
    {
        $bill = VendorBill::create([
            'bill_number' => 'BIL-2026-0011',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-DUTIES-01',
            'bill_date' => '2026-11-01',
            'subtotal' => 1500.00,
            'total_amount' => 1500.00,
            'status' => VendorBill::STATUS_VERIFIED,
            'created_by' => $this->treasurer->id, // Treasurer created this bill
        ]);

        VendorBillItem::create([
            'vendor_bill_id' => $bill->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Separation of duties test',
            'quantity' => 3,
            'unit_price' => 500.00,
            'line_total' => 1500.00,
        ]);

        // Treasurer attempts to self-approve
        $response = $this->actingAs($this->treasurer)->post(route('procurement.bills.approve', $bill));
        $response->assertSessionHasErrors('approver');
        $this->assertEquals(VendorBill::STATUS_VERIFIED, $bill->fresh()->status);
    }

    public function test_super_admin_can_approve_own_bill(): void
    {
        $bill = VendorBill::create([
            'bill_number' => 'BIL-2026-0012',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-ADMIN-01',
            'bill_date' => '2026-11-01',
            'subtotal' => 800.00,
            'total_amount' => 800.00,
            'status' => VendorBill::STATUS_VERIFIED,
            'created_by' => $this->superAdmin->id, // Super Admin created this bill
        ]);

        VendorBillItem::create([
            'vendor_bill_id' => $bill->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Super admin item',
            'quantity' => 2,
            'unit_price' => 400.00,
            'line_total' => 800.00,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('procurement.bills.approve', $bill));
        $response->assertRedirect(route('procurement.bills.show', $bill));
        $this->assertEquals(VendorBill::STATUS_APPROVED, $bill->fresh()->status);
    }

    public function test_rejection_requires_mandatory_reason(): void
    {
        $bill = VendorBill::create([
            'bill_number' => 'BIL-2026-0013',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-REJECT-01',
            'bill_date' => '2026-11-01',
            'subtotal' => 1000.00,
            'total_amount' => 1000.00,
            'status' => VendorBill::STATUS_SUBMITTED,
            'created_by' => $this->templeAdmin->id,
        ]);

        // Empty reason fails
        $response = $this->actingAs($this->treasurer)->post(route('procurement.bills.reject', $bill), [
            'rejection_reason' => '',
        ]);
        $response->assertSessionHasErrors('rejection_reason');

        // Valid reason succeeds
        $validRes = $this->actingAs($this->treasurer)->post(route('procurement.bills.reject', $bill), [
            'rejection_reason' => 'Vendor invoice rate does not match agreed quotation.',
        ]);
        $validRes->assertRedirect(route('procurement.bills.show', $bill));
        $this->assertEquals(VendorBill::STATUS_REJECTED, $bill->fresh()->status);
        $this->assertEquals('Vendor invoice rate does not match agreed quotation.', $bill->fresh()->rejection_reason);
    }

    public function test_bill_approval_posts_to_accounts_payable_without_decreasing_cash_or_bank(): void
    {
        $bill = VendorBill::create([
            'bill_number' => 'BIL-2026-0014',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-POST-01',
            'bill_date' => '2026-11-01',
            'subtotal' => 10000.00,
            'tax_amount' => 500.00,
            'discount_amount' => 200.00,
            'total_amount' => 10300.00, // 10000 + 500 - 200 = 10300
            'status' => VendorBill::STATUS_VERIFIED,
            'created_by' => $this->templeAdmin->id,
        ]);

        VendorBillItem::create([
            'vendor_bill_id' => $bill->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Item 1',
            'quantity' => 10,
            'unit_price' => 600.00,
            'line_total' => 6000.00,
        ]);

        VendorBillItem::create([
            'vendor_bill_id' => $bill->id,
            'financial_account_id' => $this->expenseAccount2->id,
            'description' => 'Item 2',
            'quantity' => 10,
            'unit_price' => 400.00,
            'line_total' => 4000.00,
        ]);

        // Record initial cash account entries count
        $initialCashEntries = JournalEntry::whereHas('lines', function ($q) {
            $q->where('financial_account_id', $this->cashAccount->id);
        })->count();

        // Treasurer approves
        $this->actingAs($this->treasurer)->post(route('procurement.bills.approve', $bill));

        $bill->refresh();
        $this->assertEquals(VendorBill::STATUS_APPROVED, $bill->status);
        $this->assertNotNull($bill->journal_entry_id);

        $journalEntry = JournalEntry::with('lines.account')->find($bill->journal_entry_id);
        $this->assertNotNull($journalEntry);
        $this->assertEquals('posted', $journalEntry->status);

        // Verify Accounts Payable is credited with exact bill total
        $apLine = $journalEntry->lines->firstWhere('financial_account_id', $this->apAccount->id);
        $this->assertNotNull($apLine, 'Accounts Payable line must be present');
        $this->assertEquals(10300.00, (float) $apLine->credit_amount);
        $this->assertEquals(0.00, (float) $apLine->debit_amount);

        // Verify total debits equal total credits
        $totalDebits = $journalEntry->lines->sum('debit_amount');
        $totalCredits = $journalEntry->lines->sum('credit_amount');
        $this->assertEquals(10300.00, (float) $totalDebits);
        $this->assertEquals(10300.00, (float) $totalCredits);

        // Verify Cash or Bank accounts were NOT affected
        $afterCashEntries = JournalEntry::whereHas('lines', function ($q) {
            $q->where('financial_account_id', $this->cashAccount->id);
        })->count();
        $this->assertEquals($initialCashEntries, $afterCashEntries, 'Cash accounts must not be disbursed upon bill approval');
    }

    public function test_repeated_approval_does_not_create_duplicate_journal_entry(): void
    {
        $bill = VendorBill::create([
            'bill_number' => 'BIL-2026-0015',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-IDEMPOTENT-01',
            'bill_date' => '2026-11-01',
            'subtotal' => 2000.00,
            'total_amount' => 2000.00,
            'status' => VendorBill::STATUS_VERIFIED,
            'created_by' => $this->templeAdmin->id,
        ]);

        VendorBillItem::create([
            'vendor_bill_id' => $bill->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Test Idempotency',
            'quantity' => 10,
            'unit_price' => 200.00,
            'line_total' => 2000.00,
        ]);

        // First approval
        $this->actingAs($this->treasurer)->post(route('procurement.bills.approve', $bill));
        $firstEntryId = $bill->fresh()->journal_entry_id;

        // Second approval attempt
        $this->actingAs($this->treasurer)->post(route('procurement.bills.approve', $bill));
        $this->assertEquals($firstEntryId, $bill->fresh()->journal_entry_id);
        $this->assertEquals(1, JournalEntry::where('source_type', VendorBill::class)->where('source_id', $bill->id)->count());
    }

    public function test_approved_bill_cancellation_reverses_journal_entry_without_deleting_history(): void
    {
        $bill = VendorBill::create([
            'bill_number' => 'BIL-2026-0016',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-REVERSE-01',
            'bill_date' => '2026-11-01',
            'subtotal' => 3000.00,
            'total_amount' => 3000.00,
            'status' => VendorBill::STATUS_VERIFIED,
            'created_by' => $this->templeAdmin->id,
        ]);

        VendorBillItem::create([
            'vendor_bill_id' => $bill->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Item for Reversal',
            'quantity' => 10,
            'unit_price' => 300.00,
            'line_total' => 3000.00,
        ]);

        // Approve and post
        $this->actingAs($this->treasurer)->post(route('procurement.bills.approve', $bill));
        $originalEntryId = $bill->fresh()->journal_entry_id;
        $this->assertNotNull($originalEntryId);

        // Cancel approved bill
        $cancelRes = $this->actingAs($this->superAdmin)->post(route('procurement.bills.cancel', $bill), [
            'cancellation_reason' => 'Vendor delivered spoiled goods; goods returned.',
        ]);
        $cancelRes->assertRedirect(route('procurement.bills.show', $bill));

        $bill->refresh();
        $this->assertEquals(VendorBill::STATUS_CANCELLED, $bill->status);
        $this->assertNotNull($bill->reversal_journal_entry_id);

        // Original entry still exists (immutable history preserved)
        $this->assertNotNull(JournalEntry::find($originalEntryId));

        // Reversal entry exists and reverses debits and credits
        $reversalEntry = JournalEntry::with('lines')->find($bill->reversal_journal_entry_id);
        $this->assertNotNull($reversalEntry);

        $reversalApLine = $reversalEntry->lines->firstWhere('financial_account_id', $this->apAccount->id);
        $this->assertNotNull($reversalApLine);
        $this->assertEquals(3000.00, (float) $reversalApLine->debit_amount, 'AP must be debited upon cancellation');
    }

    public function test_vendor_outstanding_calculation_accuracy(): void
    {
        // 1. Approved bill of 5,000 (Outstanding = 5,000)
        VendorBill::create([
            'bill_number' => 'BIL-2026-0021',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-OUT-1',
            'bill_date' => '2026-11-01',
            'subtotal' => 5000.00,
            'total_amount' => 5000.00,
            'status' => VendorBill::STATUS_APPROVED,
            'payment_status' => 'unpaid',
            'created_by' => $this->templeAdmin->id,
        ]);

        // 2. Approved bill of 3,000 (Outstanding = 5,000 + 3,000 = 8,000)
        VendorBill::create([
            'bill_number' => 'BIL-2026-0022',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-OUT-2',
            'bill_date' => '2026-11-01',
            'subtotal' => 3000.00,
            'total_amount' => 3000.00,
            'status' => VendorBill::STATUS_APPROVED,
            'payment_status' => 'unpaid',
            'created_by' => $this->templeAdmin->id,
        ]);

        // 3. Rejected bill of 10,000 (Should NOT count in outstanding)
        VendorBill::create([
            'bill_number' => 'BIL-2026-0023',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-OUT-3',
            'bill_date' => '2026-11-01',
            'subtotal' => 10000.00,
            'total_amount' => 10000.00,
            'status' => VendorBill::STATUS_REJECTED,
            'created_by' => $this->templeAdmin->id,
        ]);

        $metrics = $this->activeVendor->getBillMetrics();
        $this->assertEquals(8000.00, $metrics['total_billed']);
        $this->assertEquals(8000.00, $metrics['total_outstanding']);
        $this->assertEquals(0.00, $metrics['total_paid']);
        $this->assertEquals(2, $metrics['unpaid_count']);
    }

    public function test_vendor_bills_datatable_server_side_json_response_strictly_has_no_row_number_column(): void
    {
        VendorBill::create([
            'bill_number' => 'BIL-2026-0030',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-DT-01',
            'bill_date' => '2026-11-01',
            'subtotal' => 1200.00,
            'total_amount' => 1200.00,
            'status' => VendorBill::STATUS_DRAFT,
            'created_by' => $this->templeAdmin->id,
        ]);

        $response = $this->actingAs($this->templeAdmin)->getJson(route('procurement.bills.index'));
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertArrayHasKey('data', $json);
        $this->assertNotEmpty($json['data']);

        $firstRow = $json['data'][0];
        // Strictly assert no row-number keys exist
        $this->assertArrayNotHasKey('#', $firstRow);
        $this->assertArrayNotHasKey('sl', $firstRow);
        $this->assertArrayNotHasKey('row_number', $firstRow);
        $this->assertArrayNotHasKey('serial', $firstRow);
        $this->assertArrayNotHasKey('dt_row_index', $firstRow);

        // Assert valid keys
        $this->assertArrayHasKey('bill_number', $firstRow);
        $this->assertArrayHasKey('vendor', $firstRow);
        $this->assertArrayHasKey('invoice_reference', $firstRow);
        $this->assertArrayHasKey('bill_date', $firstRow);
        $this->assertArrayHasKey('due_date', $firstRow);
        $this->assertArrayHasKey('total_amount', $firstRow);
        $this->assertArrayHasKey('status', $firstRow);
        $this->assertArrayHasKey('actions', $firstRow);
    }

    public function test_bengali_locale_renders_vendor_bill_views_without_errors(): void
    {
        app()->setLocale('bn');

        $bill = VendorBill::create([
            'bill_number' => 'BIL-2026-0040',
            'vendor_id' => $this->activeVendor->id,
            'vendor_invoice_number' => 'INV-BN-01',
            'bill_date' => '2026-11-01',
            'subtotal' => 2500.00,
            'total_amount' => 2500.00,
            'status' => VendorBill::STATUS_DRAFT,
            'created_by' => $this->templeAdmin->id,
        ]);

        VendorBillItem::create([
            'vendor_bill_id' => $bill->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'বাংলা টেস্ট আইটেম',
            'quantity' => 5,
            'unit_price' => 500.00,
            'line_total' => 2500.00,
        ]);

        // 1. Index
        $indexRes = $this->actingAs($this->templeAdmin)->get(route('procurement.bills.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('বিল তালিকা');
        $indexRes->assertDontSee('procurement.bill_list');

        // 2. Create
        $createRes = $this->actingAs($this->templeAdmin)->get(route('procurement.bills.create'));
        $createRes->assertStatus(200);
        $createRes->assertSee('নতুন বিল তৈরি');
        $createRes->assertDontSee('procurement.create_bill');

        // 3. Show
        $showRes = $this->actingAs($this->templeAdmin)->get(route('procurement.bills.show', $bill));
        $showRes->assertStatus(200);
        $showRes->assertSee('বিলের বিবরণী');
        $showRes->assertDontSee('procurement.bill_details');

        // 4. Print
        $printRes = $this->actingAs($this->templeAdmin)->get(route('procurement.bills.print', $bill));
        $printRes->assertStatus(200);
        $printRes->assertSee('সরবরাহকারী বিল ভাউচার');
        $printRes->assertDontSee('procurement.bill_voucher');
    }

    public function test_load_po_items_endpoint_returns_unbilled_quantities(): void
    {
        $po = $this->createApprovedPo($this->activeVendor, 100, 45); // 100 kg
        $poItem = $po->items->first();

        // Bill 30 kg first
        VendorBill::create([
            'bill_number' => 'BIL-2026-0050',
            'vendor_id' => $this->activeVendor->id,
            'purchase_order_id' => $po->id,
            'vendor_invoice_number' => 'INV-API-PART-1',
            'bill_date' => '2026-11-01',
            'subtotal' => 1350.00,
            'total_amount' => 1350.00,
            'status' => VendorBill::STATUS_SUBMITTED,
            'created_by' => $this->templeAdmin->id,
        ]);

        VendorBillItem::create([
            'vendor_bill_id' => VendorBill::where('bill_number', 'BIL-2026-0050')->first()->id,
            'purchase_order_item_id' => $poItem->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Pure Ghee Part 1',
            'quantity' => 30,
            'unit_price' => 45.00,
            'line_total' => 1350.00,
        ]);

        $response = $this->actingAs($this->templeAdmin)->getJson(route('procurement.bills.po-items', $po));
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertArrayHasKey('items', $json);
        $this->assertCount(1, $json['items']);
        $item = $json['items'][0];
        $this->assertEquals(100.0, (float) $item['ordered_quantity']);
        $this->assertEquals(30.0, (float) $item['billed_quantity']);
        $this->assertEquals(70.0, (float) $item['remaining_quantity']);
    }
}
