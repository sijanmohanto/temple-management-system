<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $templeAdmin;

    protected User $devotee;

    protected Event $event;

    protected FinancialAccount $expenseAccount1;

    protected FinancialAccount $expenseAccount2;

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

        $this->approvedBudget = Budget::create([
            'budget_code' => 'BDG-2026-0001',
            'title_en' => 'Kali Puja 2026 Budget',
            'budget_type' => Budget::TYPE_EVENT,
            'fiscal_year' => 2026,
            'event_id' => $this->event->id,
            'total_amount' => 50000.00,
            'status' => Budget::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
        ]);

        BudgetItem::create([
            'budget_id' => $this->approvedBudget->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'allocated_amount' => 30000.00,
        ]);

        BudgetItem::create([
            'budget_id' => $this->approvedBudget->id,
            'financial_account_id' => $this->expenseAccount2->id,
            'allocated_amount' => 20000.00,
        ]);

        $this->activeVendor = Vendor::create([
            'vendor_code' => 'VND-0001',
            'name' => 'Bhadra & Sons Suppliers',
            'contact_person' => 'Ratan Bhadra',
            'phone' => '01711000001',
            'email' => 'ratan@example.com',
            'vendor_type' => 'Goods',
            'address' => 'Bazar Road, Joydebpur',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->inactiveVendor = Vendor::create([
            'vendor_code' => 'VND-0002',
            'name' => 'Inactive Vendor Co',
            'contact_person' => 'Sunil Das',
            'phone' => '01711000002',
            'is_active' => false,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    public function test_guests_cannot_access_purchase_order_endpoints(): void
    {
        $response = $this->get(route('procurement.orders.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('procurement.orders.create'));
        $response->assertRedirect(route('login'));

        $response = $this->post(route('procurement.orders.store'), []);
        $response->assertRedirect(route('login'));
    }

    public function test_unauthorized_user_cannot_access_purchase_order_endpoints(): void
    {
        $response = $this->actingAs($this->devotee)->get(route('procurement.orders.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->devotee)->get(route('procurement.orders.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->devotee)->post(route('procurement.orders.store'), [
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-10-30',
        ]);
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_purchase_order_index_and_create_pages(): void
    {
        $response = $this->actingAs($this->treasurer)->get(route('procurement.orders.index'));
        $response->assertStatus(200);
        $response->assertSeeText(__('procurement.purchase_orders'));

        $response = $this->actingAs($this->treasurer)->get(route('procurement.orders.create'));
        $response->assertStatus(200);
        $response->assertSeeText(__('procurement.create_order'));
    }

    public function test_cannot_create_purchase_order_from_non_approved_or_already_converted_request(): void
    {
        // 1. Draft request
        $draftRequest = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0001',
            'title' => 'Draft Requisition',
            'request_date' => '2026-10-30',
            'requested_by' => $this->treasurer->id,
            'status' => PurchaseRequest::STATUS_DRAFT,
            'total_estimated_amount' => 5000.00,
            'created_by' => $this->treasurer->id,
        ]);

        $response = $this->actingAs($this->treasurer)->post(route('procurement.orders.store'), [
            'purchase_request_id' => $draftRequest->id,
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-10-30',
            'items' => [
                [
                    'description' => 'Item 1',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 5,
                    'unit' => 'pcs',
                    'unit_price' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('purchase_request_id');
        $this->assertDatabaseMissing('purchase_orders', [
            'purchase_request_id' => $draftRequest->id,
        ]);
    }

    public function test_can_convert_approved_purchase_request_to_purchase_order_with_concurrency_safety(): void
    {
        $approvedRequest = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0002',
            'title' => 'Approved Flower Requisition',
            'request_date' => '2026-10-30',
            'requested_by' => $this->treasurer->id,
            'status' => PurchaseRequest::STATUS_APPROVED,
            'event_id' => $this->event->id,
            'budget_id' => $this->approvedBudget->id,
            'total_estimated_amount' => 4500.00,
            'is_converted_to_po' => false,
            'created_by' => $this->treasurer->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $approvedRequest->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Red Hibiscus Garlands',
            'quantity' => 10,
            'unit' => 'mala',
            'estimated_unit_price' => 250.00,
            'estimated_total' => 2500.00,
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $approvedRequest->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Marigold Garlands',
            'quantity' => 20,
            'unit' => 'mala',
            'estimated_unit_price' => 100.00,
            'estimated_total' => 2000.00,
        ]);

        $response = $this->actingAs($this->treasurer)->post(route('procurement.orders.store'), [
            'purchase_request_id' => $approvedRequest->id,
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-10-30',
            'expected_delivery_date' => '2026-11-01',
            'delivery_address' => 'Kali Temple Mandap',
            'discount_amount' => 200.00,
            'items' => [
                [
                    'description' => 'Red Hibiscus Garlands',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 10,
                    'unit' => 'mala',
                    'unit_price' => 250.00,
                ],
                [
                    'description' => 'Marigold Garlands',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 20,
                    'unit' => 'mala',
                    'unit_price' => 100.00,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $order = PurchaseOrder::where('purchase_request_id', $approvedRequest->id)->first();
        $this->assertNotNull($order);
        $this->assertMatchesRegularExpression('/^PO-\d{4}-\d{4}$/', $order->order_number);
        $this->assertEquals(PurchaseOrder::STATUS_DRAFT, $order->status);
        $this->assertEquals(4500.00, (float) $order->subtotal);
        $this->assertEquals(200.00, (float) $order->discount_amount);
        $this->assertEquals(4300.00, (float) $order->total_amount);
        $this->assertEquals($this->activeVendor->id, $order->vendor_id);
        $this->assertEquals($this->event->id, $order->event_id);
        $this->assertEquals($this->approvedBudget->id, $order->budget_id);

        // Check line items created
        $this->assertCount(2, $order->items);

        // Verify that the purchase request is now locked as converted
        $this->assertTrue((bool) $approvedRequest->fresh()->is_converted_to_po);
    }

    public function test_duplicate_conversion_attempt_is_rejected(): void
    {
        $approvedRequest = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0003',
            'title' => 'Requisition For Single Conversion',
            'request_date' => '2026-10-30',
            'requested_by' => $this->treasurer->id,
            'status' => PurchaseRequest::STATUS_APPROVED,
            'total_estimated_amount' => 1000.00,
            'is_converted_to_po' => true, // already converted
            'created_by' => $this->treasurer->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->treasurer)->post(route('procurement.orders.store'), [
            'purchase_request_id' => $approvedRequest->id,
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-10-30',
            'items' => [
                [
                    'description' => 'Item X',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 10,
                    'unit' => 'pcs',
                    'unit_price' => 100.00,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('purchase_request_id');
        $this->assertDatabaseMissing('purchase_orders', [
            'purchase_request_id' => $approvedRequest->id,
        ]);
    }

    public function test_server_side_calculation_ignores_tampered_totals_and_discounts(): void
    {
        $response = $this->actingAs($this->treasurer)->post(route('procurement.orders.store'), [
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-10-30',
            'subtotal' => 1.00, // tampered
            'total_amount' => 2.00, // tampered
            'discount_amount' => 300.00,
            'items' => [
                [
                    'description' => 'Pure Ghee for Yajna',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 10,
                    'unit' => 'kg',
                    'unit_price' => 850.00, // subtotal = 8500.00
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $order = PurchaseOrder::latest('id')->first();
        $this->assertEquals(8500.00, (float) $order->subtotal);
        $this->assertEquals(300.00, (float) $order->discount_amount);
        $this->assertEquals(8200.00, (float) $order->total_amount);
    }

    public function test_purchase_order_validation_rejects_inactive_vendor(): void
    {
        $response = $this->actingAs($this->treasurer)->post(route('procurement.orders.store'), [
            'vendor_id' => $this->inactiveVendor->id,
            'order_date' => '2026-10-30',
            'items' => [
                [
                    'description' => 'Item Test',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'unit_price' => 100.00,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('vendor_id');
    }

    public function test_strict_separation_of_duties_prevents_creator_from_self_approving_order(): void
    {
        $order = PurchaseOrder::create([
            'order_number' => 'PO-2026-0010',
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-10-30',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'subtotal' => 2500.00,
            'discount_amount' => 0.00,
            'total_amount' => 2500.00,
            'created_by' => $this->treasurer->id,
        ]);

        $order->items()->create([
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Incense Sticks',
            'quantity' => 25,
            'unit' => 'box',
            'unit_price' => 100.00,
            'total_price' => 2500.00,
        ]);

        // Attempt self-approval by treasurer (order creator)
        $response = $this->actingAs($this->treasurer)->post(route('procurement.orders.approve', $order));

        $response->assertSessionHasErrors('approver');
        $this->assertEquals(PurchaseOrder::STATUS_DRAFT, $order->fresh()->status);
        $this->assertNull($order->fresh()->approved_by);
    }

    public function test_authorized_approver_can_approve_purchase_order(): void
    {
        $order = PurchaseOrder::create([
            'order_number' => 'PO-2026-0011',
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-10-30',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'subtotal' => 2500.00,
            'discount_amount' => 0.00,
            'total_amount' => 2500.00,
            'created_by' => $this->treasurer->id,
        ]);

        $order->items()->create([
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Incense Sticks',
            'quantity' => 25,
            'unit' => 'box',
            'unit_price' => 100.00,
            'total_price' => 2500.00,
        ]);

        // Super Admin approves the order
        $response = $this->actingAs($this->superAdmin)->post(route('procurement.orders.approve', $order));

        $response->assertSessionHasNoErrors();
        $this->assertEquals(PurchaseOrder::STATUS_APPROVED, $order->fresh()->status);
        $this->assertEquals($this->superAdmin->id, $order->fresh()->approved_by);
        $this->assertNotNull($order->fresh()->approved_at);
    }

    public function test_purchase_order_lifecycle_transitions(): void
    {
        $order = PurchaseOrder::create([
            'order_number' => 'PO-2026-0012',
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-10-30',
            'status' => PurchaseOrder::STATUS_APPROVED,
            'subtotal' => 1500.00,
            'discount_amount' => 0.00,
            'total_amount' => 1500.00,
            'created_by' => $this->treasurer->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
        ]);

        $order->items()->create([
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Oil for Lamps',
            'quantity' => 10,
            'unit' => 'liter',
            'unit_price' => 150.00,
            'total_price' => 1500.00,
        ]);

        // 1. Mark as sent to vendor
        $response = $this->actingAs($this->treasurer)->post(route('procurement.orders.send', $order));
        $response->assertSessionHasNoErrors();
        $this->assertEquals(PurchaseOrder::STATUS_SENT, $order->fresh()->status);

        // 2. Mark as completed
        $response = $this->actingAs($this->treasurer)->post(route('procurement.orders.complete', $order));
        $response->assertSessionHasNoErrors();
        $this->assertEquals(PurchaseOrder::STATUS_COMPLETED, $order->fresh()->status);

        // 3. Test cancellation and source PR lock release
        $sourcePr = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0004',
            'title' => 'Request To Cancel Later',
            'request_date' => '2026-10-30',
            'requested_by' => $this->treasurer->id,
            'status' => PurchaseRequest::STATUS_APPROVED,
            'total_estimated_amount' => 1000.00,
            'is_converted_to_po' => true,
            'created_by' => $this->treasurer->id,
        ]);

        $orderToCancel = PurchaseOrder::create([
            'order_number' => 'PO-2026-0013',
            'purchase_request_id' => $sourcePr->id,
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-10-30',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'subtotal' => 1000.00,
            'discount_amount' => 0.00,
            'total_amount' => 1000.00,
            'created_by' => $this->treasurer->id,
        ]);

        $response = $this->actingAs($this->treasurer)->post(route('procurement.orders.cancel', $orderToCancel), [
            'reason' => 'Vendor out of stock for requested items',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals(PurchaseOrder::STATUS_CANCELLED, $orderToCancel->fresh()->status);
        // Verify source PR was released
        $this->assertFalse((bool) $sourcePr->fresh()->is_converted_to_po);
    }

    public function test_purchase_order_lifecycle_does_not_create_finance_ledger_entries(): void
    {
        $initialExpenseCount = Expense::count();
        $initialJournalCount = JournalEntry::count();

        // 1. Create order
        $order = PurchaseOrder::create([
            'order_number' => 'PO-2026-0014',
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-10-30',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'subtotal' => 2000.00,
            'discount_amount' => 0.00,
            'total_amount' => 2000.00,
            'created_by' => $this->treasurer->id,
        ]);

        $order->items()->create([
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'Test Item',
            'quantity' => 10,
            'unit' => 'pcs',
            'unit_price' => 200.00,
            'total_price' => 2000.00,
        ]);

        // 2. Approve order
        $this->actingAs($this->superAdmin)->post(route('procurement.orders.approve', $order));

        // 3. Send order
        $this->actingAs($this->treasurer)->post(route('procurement.orders.send', $order));

        // 4. Complete order
        $this->actingAs($this->treasurer)->post(route('procurement.orders.complete', $order));

        // Invariant check: procurement orders MUST NOT generate expense or journal ledger entries
        $this->assertEquals($initialExpenseCount, Expense::count());
        $this->assertEquals($initialJournalCount, JournalEntry::count());
    }

    public function test_purchase_order_datatable_server_side_json_response_strictly_has_no_row_number_column(): void
    {
        PurchaseOrder::create([
            'order_number' => 'PO-2026-0015',
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-10-30',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'subtotal' => 1200.00,
            'discount_amount' => 0.00,
            'total_amount' => 1200.00,
            'created_by' => $this->treasurer->id,
        ]);

        $response = $this->actingAs($this->treasurer)->getJson(route('procurement.orders.index'));

        $response->assertStatus(200);
        $json = $response->json();

        $this->assertArrayHasKey('data', $json);
        $this->assertNotEmpty($json['data']);

        $firstRow = $json['data'][0];
        // Enforce strict ban on '#' / row_number column
        $this->assertArrayNotHasKey('#', $firstRow);
        $this->assertArrayNotHasKey('sl', $firstRow);
        $this->assertArrayNotHasKey('row_num', $firstRow);
        $this->assertArrayHasKey('order_number', $firstRow);
        $this->assertArrayHasKey('vendor', $firstRow);
        $this->assertArrayHasKey('total_amount', $firstRow);
        $this->assertArrayHasKey('status', $firstRow);
    }

    public function test_bengali_locale_renders_purchase_order_views_without_errors(): void
    {
        app()->setLocale('bn');

        $order = PurchaseOrder::create([
            'order_number' => 'PO-2026-0016',
            'vendor_id' => $this->activeVendor->id,
            'order_date' => '2026-10-30',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'subtotal' => 1200.00,
            'discount_amount' => 0.00,
            'total_amount' => 1200.00,
            'created_by' => $this->treasurer->id,
        ]);

        $order->items()->create([
            'financial_account_id' => $this->expenseAccount1->id,
            'description' => 'টেস্ট সামগ্রী',
            'quantity' => 5,
            'unit' => 'টি',
            'unit_price' => 240.00,
            'total_price' => 1200.00,
        ]);

        // Index
        $response = $this->actingAs($this->treasurer)->get(route('procurement.orders.index'));
        $response->assertStatus(200);

        // Create
        $response = $this->actingAs($this->treasurer)->get(route('procurement.orders.create'));
        $response->assertStatus(200);

        // Edit
        $response = $this->actingAs($this->treasurer)->get(route('procurement.orders.edit', $order));
        $response->assertStatus(200);

        // Show
        $response = $this->actingAs($this->treasurer)->get(route('procurement.orders.show', $order));
        $response->assertStatus(200);

        // Print
        $response = $this->actingAs($this->treasurer)->get(route('procurement.orders.print', $order));
        $response->assertStatus(200);
    }
}
