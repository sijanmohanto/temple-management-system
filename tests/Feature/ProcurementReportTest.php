<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\FinancialAccount;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Models\VendorBillItem;
use App\Models\VendorPayment;
use App\Models\VendorPaymentAllocation;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $templeAdmin;

    protected User $devotee;

    protected Vendor $vendor1;

    protected Vendor $vendor2;

    protected FinancialAccount $expenseAccount;

    protected FinancialAccount $liquidAccount;

    protected Budget $budget;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $treasurerRole = Role::where('slug', 'treasurer')->first();
        $templeAdminRole = Role::where('slug', 'temple_admin')->first();
        $devoteeRole = Role::where('slug', 'devotee')->first();

        $this->superAdmin = User::factory()->create(['status' => 'active', 'name' => 'Super Admin']);
        $this->superAdmin->roles()->attach($superAdminRole);

        $this->treasurer = User::factory()->create(['status' => 'active', 'name' => 'Treasurer User']);
        $this->treasurer->roles()->attach($treasurerRole);

        $this->templeAdmin = User::factory()->create(['status' => 'active', 'name' => 'Temple Admin User']);
        $this->templeAdmin->roles()->attach($templeAdminRole);

        $this->devotee = User::factory()->create(['status' => 'active', 'name' => 'Devotee User']);
        $this->devotee->roles()->attach($devoteeRole);

        // Core accounts
        $this->expenseAccount = FinancialAccount::create([
            'account_code' => '5100',
            'account_name_en' => 'Puja Materials Expense',
            'account_name_bn' => 'পূজা সামগ্রী খরচ',
            'account_type' => FinancialAccount::TYPE_EXPENSE,
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $this->liquidAccount = FinancialAccount::create([
            'account_code' => '1020',
            'account_name_en' => 'Operational Bank Account',
            'account_name_bn' => 'চলতি ব্যাংক হিসাব',
            'account_type' => FinancialAccount::TYPE_ASSET,
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        // Vendors
        $this->vendor1 = Vendor::create([
            'vendor_code' => 'VND-2026-0001',
            'name' => 'M/S Roy Brothers',
            'vendor_type' => 'puja_materials',
            'phone' => '01711000001',
            'email' => 'roy@example.com',
            'is_active' => true,
        ]);

        $this->vendor2 = Vendor::create([
            'vendor_code' => 'VND-2026-0002',
            'name' => 'Bengal Decor & Tent House',
            'vendor_type' => 'decoration_lighting',
            'phone' => '01711000002',
            'is_active' => true,
        ]);

        $eventCategory = EventCategory::create(['name_en' => 'Puja', 'name_bn' => 'পূজা', 'slug' => 'puja']);
        $this->event = Event::create([
            'event_code' => 'EVT-2026-0001',
            'name_en' => 'Durga Puja 2026',
            'name_bn' => 'দুর্গাপূজা ২০২৬',
            'event_category_id' => $eventCategory->id,
            'event_year' => 2026,
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-19',
            'created_by' => $this->superAdmin->id,
            'is_active' => true,
        ]);

        $this->budget = Budget::create([
            'budget_code' => 'BDG-2026-0001',
            'title_en' => 'Annual Puja Budget',
            'title_bn' => 'বার্ষিক পূজা বাজেট',
            'budget_type' => Budget::TYPE_ANNUAL,
            'fiscal_year' => 2026,
            'status' => Budget::STATUS_APPROVED,
            'total_amount' => 500000.00,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
            'created_by' => $this->treasurer->id,
        ]);

        BudgetItem::create([
            'budget_id' => $this->budget->id,
            'financial_account_id' => $this->expenseAccount->id,
            'allocated_amount' => 200000.00,
        ]);
    }

    /**
     * -------------------------------------------------------------------------
     * 1. PERMISSION & AUTH TESTS
     * -------------------------------------------------------------------------
     */
    public function test_guests_are_redirected_to_login_for_procurement_reports(): void
    {
        $this->get(route('procurement.reports.requests'))->assertRedirect(route('login'));
        $this->get(route('procurement.reports.orders'))->assertRedirect(route('login'));
        $this->get(route('procurement.reports.outstanding-bills'))->assertRedirect(route('login'));
    }

    public function test_unauthorized_user_cannot_access_procurement_reports(): void
    {
        $this->actingAs($this->devotee);

        $this->get(route('procurement.reports.requests'))->assertForbidden();
        $this->get(route('procurement.reports.orders'))->assertForbidden();
        $this->get(route('procurement.reports.outstanding-bills'))->assertForbidden();
        $this->get(route('procurement.reports.requests.export'))->assertForbidden();
        $this->get(route('procurement.reports.orders.export'))->assertForbidden();
        $this->get(route('procurement.reports.outstanding-bills.export'))->assertForbidden();
    }

    public function test_authorized_user_and_super_admin_can_access_report_screens(): void
    {
        $this->actingAs($this->treasurer);
        $this->get(route('procurement.reports.requests'))->assertOk()->assertSee(__('procurement.purchase_requisitions_report_title'));
        $this->get(route('procurement.reports.orders'))->assertOk()->assertSee(__('procurement.purchase_orders_report_title'));
        $this->get(route('procurement.reports.outstanding-bills'))->assertOk()->assertSee(__('procurement.outstanding_bills_report_title'));

        $this->actingAs($this->superAdmin);
        $this->get(route('procurement.reports.requests'))->assertOk();
        $this->get(route('procurement.reports.orders'))->assertOk();
        $this->get(route('procurement.reports.outstanding-bills'))->assertOk();
    }

    /**
     * -------------------------------------------------------------------------
     * 2. PURCHASE REQUISITIONS REPORT TESTS
     * -------------------------------------------------------------------------
     */
    public function test_purchase_requisitions_report_datatables_ajax_and_filters(): void
    {
        $this->actingAs($this->superAdmin);

        // Create PR 1 (Approved)
        $pr1 = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0001',
            'title' => 'Puja Ghee and Incense',
            'justification' => 'Essential for daily arati',
            'requested_by' => $this->treasurer->id,
            'request_date' => '2026-01-10',
            'required_date' => '2026-01-20',
            'status' => PurchaseRequest::STATUS_APPROVED,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => '2026-01-12 10:00:00',
            'total_estimated_amount' => 15000.00,
            'budget_id' => $this->budget->id,
            'created_by' => $this->treasurer->id,
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr1->id,
            'financial_account_id' => $this->expenseAccount->id,
            'description' => 'Pure Cow Ghee',
            'quantity' => 20.00,
            'unit' => 'kg',
            'estimated_unit_price' => 750.00,
            'estimated_total' => 15000.00,
        ]);

        // Create PR 2 (Draft)
        $pr2 = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0002',
            'title' => 'Cleaning Supplies',
            'requested_by' => $this->templeAdmin->id,
            'request_date' => '2026-02-01',
            'status' => PurchaseRequest::STATUS_DRAFT,
            'total_estimated_amount' => 5000.00,
            'budget_id' => $this->budget->id,
            'created_by' => $this->templeAdmin->id,
        ]);

        // 1. DataTables standard AJAX query
        $res = $this->getJson(route('procurement.reports.requests'), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $res->assertOk();
        $json = $res->json();
        $this->assertEquals(2, $json['recordsTotal']);
        $this->assertEquals(2, $json['recordsFiltered']);
        $this->assertEquals(20000.00, $json['summary']['total_amount']);
        $this->assertEquals(1, $json['summary']['approved_count']);
        $this->assertEquals(1, $json['summary']['draft_count']);

        // 2. Filter by status = approved
        $resApproved = $this->getJson(route('procurement.reports.requests', ['status' => 'approved']), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $jsonApproved = $resApproved->json();
        $this->assertEquals(1, $jsonApproved['recordsFiltered']);
        $this->assertEquals('PRQ-2026-0001', strip_tags($jsonApproved['data'][0]['request_number']));

        // 3. Filter by search reference
        $resSearch = $this->getJson(route('procurement.reports.requests', ['search' => 'Cleaning']), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $jsonSearch = $resSearch->json();
        $this->assertEquals(1, $jsonSearch['recordsFiltered']);
        $this->assertStringContainsString('PRQ-2026-0002', $jsonSearch['data'][0]['request_number']);
    }

    public function test_purchase_requisition_details_offcanvas_and_export(): void
    {
        $this->actingAs($this->superAdmin);

        $pr = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0010',
            'title' => 'Brass Bells and Lamps',
            'justification' => 'Mandir altar renovation',
            'requested_by' => $this->treasurer->id,
            'request_date' => '2026-03-01',
            'status' => PurchaseRequest::STATUS_APPROVED,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
            'total_estimated_amount' => 8000.00,
            'budget_id' => $this->budget->id,
            'created_by' => $this->treasurer->id,
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr->id,
            'financial_account_id' => $this->expenseAccount->id,
            'description' => 'Brass Lamp',
            'quantity' => 4.00,
            'unit' => 'pcs',
            'estimated_unit_price' => 2000.00,
            'estimated_total' => 8000.00,
        ]);

        // Details endpoint
        $detailsRes = $this->get(route('procurement.reports.requests.details', $pr->id), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $detailsRes->assertOk()->assertSee('Brass Lamp')->assertSee('PRQ-2026-0010');

        // Export CSV endpoint
        $exportRes = $this->get(route('procurement.reports.requests.export'));
        $exportRes->assertOk();
        $this->assertStringContainsString('text/csv', $exportRes->headers->get('Content-Type'));

        // Print endpoint
        $printRes = $this->get(route('procurement.reports.requests.print'));
        $printRes->assertOk()->assertSee('PRQ-2026-0010');
    }

    /**
     * -------------------------------------------------------------------------
     * 3. PURCHASE ORDERS REPORT TESTS
     * -------------------------------------------------------------------------
     */
    public function test_purchase_orders_report_datatables_ajax_and_summaries(): void
    {
        $this->actingAs($this->treasurer);

        // PO 1: Approved (Active Committed)
        PurchaseOrder::create([
            'order_number' => 'PO-2026-0001',
            'vendor_id' => $this->vendor1->id,
            'order_date' => '2026-02-10',
            'expected_delivery_date' => '2026-02-18',
            'status' => PurchaseOrder::STATUS_APPROVED,
            'total_amount' => 25000.00,
            'created_by' => $this->treasurer->id,
        ]);

        // PO 2: Completed
        PurchaseOrder::create([
            'order_number' => 'PO-2026-0002',
            'vendor_id' => $this->vendor2->id,
            'order_date' => '2026-01-15',
            'expected_delivery_date' => '2026-01-20',
            'status' => PurchaseOrder::STATUS_COMPLETED,
            'total_amount' => 40000.00,
            'created_by' => $this->treasurer->id,
        ]);

        // PO 3: Cancelled (Must be strictly segregated from active committed)
        PurchaseOrder::create([
            'order_number' => 'PO-2026-0003',
            'vendor_id' => $this->vendor1->id,
            'order_date' => '2026-01-05',
            'status' => PurchaseOrder::STATUS_CANCELLED,
            'total_amount' => 10000.00,
            'created_by' => $this->treasurer->id,
        ]);

        $res = $this->getJson(route('procurement.reports.orders'), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $res->assertOk();
        $json = $res->json();
        $this->assertEquals(3, $json['recordsTotal']);
        $this->assertEquals(75000.00, $json['summary']['total_historical_amount']);
        // Active committed: only approved (25,000)
        $this->assertEquals(25000.00, $json['summary']['active_committed_amount']);
        $this->assertEquals(1, $json['summary']['active_committed_count']);
        // Completed: 40,000
        $this->assertEquals(40000.00, $json['summary']['completed_amount']);
        // Cancelled: 10,000
        $this->assertEquals(10000.00, $json['summary']['cancelled_amount']);
        $this->assertEquals(1, $json['summary']['cancelled_count']);

        // Filter by Vendor
        $resVendor = $this->getJson(route('procurement.reports.orders', ['vendor_id' => $this->vendor1->id]), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $jsonVendor = $resVendor->json();
        $this->assertEquals(2, $jsonVendor['recordsFiltered']);
    }

    public function test_purchase_order_details_offcanvas_and_export(): void
    {
        $this->actingAs($this->superAdmin);

        $po = PurchaseOrder::create([
            'order_number' => 'PO-2026-0050',
            'vendor_id' => $this->vendor1->id,
            'order_date' => '2026-03-05',
            'expected_delivery_date' => '2026-03-12',
            'status' => PurchaseOrder::STATUS_APPROVED,
            'total_amount' => 12000.00,
            'delivery_address' => 'Temple Main Altar Compound',
            'created_by' => $this->treasurer->id,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'financial_account_id' => $this->expenseAccount->id,
            'description' => 'Puja Flowers',
            'quantity' => 10.00,
            'unit' => 'garlands',
            'unit_price' => 1200.00,
            'line_total' => 12000.00,
        ]);

        // Details endpoint
        $detailsRes = $this->get(route('procurement.reports.orders.details', $po->id), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $detailsRes->assertOk()->assertSee('Puja Flowers')->assertSee('PO-2026-0050');

        // Export endpoint
        $exportRes = $this->get(route('procurement.reports.orders.export'));
        $exportRes->assertOk();
        $this->assertStringContainsString('text/csv', $exportRes->headers->get('Content-Type'));

        // Print endpoint
        $printRes = $this->get(route('procurement.reports.orders.print'));
        $printRes->assertOk()->assertSee('PO-2026-0050');
    }

    /**
     * -------------------------------------------------------------------------
     * 4. OUTSTANDING VENDOR BILLS REPORT TESTS (FINANCIAL INTEGRITY)
     * -------------------------------------------------------------------------
     */
    public function test_outstanding_bills_authoritative_calculation_and_reversals(): void
    {
        $this->actingAs($this->superAdmin);

        // Bill 1: Approved Bill, Total = 50,000, Unpaid (No payments)
        $bill1 = VendorBill::create([
            'bill_number' => 'VB-2026-0001',
            'vendor_invoice_number' => 'INV-9001',
            'vendor_id' => $this->vendor1->id,
            'bill_date' => '2026-01-10',
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => VendorBill::STATUS_APPROVED,
            'total_amount' => 50000.00,
            'paid_amount' => 0.00,
            'payment_status' => 'unpaid',
            'created_by' => $this->treasurer->id,
        ]);

        // Bill 2: Approved Bill, Total = 30,000, Partially paid by Posted Payment (12,000)
        $bill2 = VendorBill::create([
            'bill_number' => 'VB-2026-0002',
            'vendor_invoice_number' => 'INV-9002',
            'vendor_id' => $this->vendor1->id,
            'bill_date' => '2026-01-15',
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => VendorBill::STATUS_APPROVED,
            'total_amount' => 30000.00,
            'paid_amount' => 12000.00,
            'payment_status' => 'partial',
            'created_by' => $this->treasurer->id,
        ]);

        $paymentPosted = VendorPayment::create([
            'payment_number' => 'VPY-2026-0001',
            'vendor_id' => $this->vendor1->id,
            'payment_date' => '2026-01-20',
            'payment_method' => 'bank_transfer',
            'financial_account_id' => $this->liquidAccount->id,
            'amount' => 12000.00,
            'status' => VendorPayment::STATUS_POSTED,
            'created_by' => $this->treasurer->id,
            'posted_at' => now(),
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $paymentPosted->id,
            'vendor_bill_id' => $bill2->id,
            'allocated_amount' => 12000.00,
        ]);

        // Bill 3: Approved Bill, Total = 20,000, Fully paid by Posted Payment (20,000)
        $bill3 = VendorBill::create([
            'bill_number' => 'VB-2026-0003',
            'vendor_invoice_number' => 'INV-9003',
            'vendor_id' => $this->vendor2->id,
            'bill_date' => '2026-01-18',
            'due_date' => now()->addDays(15)->toDateString(),
            'status' => VendorBill::STATUS_APPROVED,
            'total_amount' => 20000.00,
            'paid_amount' => 20000.00,
            'payment_status' => 'paid',
            'created_by' => $this->treasurer->id,
        ]);

        $paymentFull = VendorPayment::create([
            'payment_number' => 'VPY-2026-0002',
            'vendor_id' => $this->vendor2->id,
            'payment_date' => '2026-01-22',
            'payment_method' => 'cheque',
            'financial_account_id' => $this->liquidAccount->id,
            'amount' => 20000.00,
            'status' => VendorPayment::STATUS_POSTED,
            'created_by' => $this->treasurer->id,
            'posted_at' => now(),
        ]);

        VendorPaymentAllocation::create([
            'vendor_payment_id' => $paymentFull->id,
            'vendor_bill_id' => $bill3->id,
            'allocated_amount' => 20000.00,
        ]);

        // Bill 4: Approved Bill, Total = 15,000, Overdue (Due 10 days ago, no payment)
        $bill4 = VendorBill::create([
            'bill_number' => 'VB-2026-0004',
            'vendor_invoice_number' => 'INV-9004',
            'vendor_id' => $this->vendor2->id,
            'bill_date' => now()->subDays(30)->toDateString(),
            'due_date' => now()->subDays(10)->toDateString(),
            'status' => VendorBill::STATUS_APPROVED,
            'total_amount' => 15000.00,
            'paid_amount' => 0.00,
            'payment_status' => 'unpaid',
            'created_by' => $this->treasurer->id,
        ]);

        // Bill 5: Draft bill (Must NOT appear in approved payable report)
        VendorBill::create([
            'bill_number' => 'VB-2026-0005',
            'vendor_invoice_number' => 'INV-9005',
            'vendor_id' => $this->vendor1->id,
            'bill_date' => '2026-02-01',
            'status' => VendorBill::STATUS_DRAFT,
            'total_amount' => 100000.00,
            'created_by' => $this->treasurer->id,
        ]);

        // Query outstanding bills report via AJAX
        $res = $this->getJson(route('procurement.reports.outstanding-bills'), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $res->assertOk();
        $json = $res->json();

        // Total bills in approved status = 4 (Draft bill excluded)
        $this->assertEquals(4, $json['recordsTotal']);
        $this->assertEquals(4, $json['recordsFiltered']);

        // Summary financial checks:
        // Approved total: 50,000 + 30,000 + 20,000 + 15,000 = 115,000
        $this->assertEquals(115000.00, $json['summary']['total_approved_amount']);
        // Net posted paid: 12,000 + 20,000 = 32,000
        $this->assertEquals(32000.00, $json['summary']['total_paid_amount']);
        // Outstanding: 115,000 - 32,000 = 83,000
        $this->assertEquals(83000.00, $json['summary']['total_outstanding_amount']);
        // Counts
        $this->assertEquals(2, $json['summary']['unpaid_bills_count']); // Bill 1, Bill 4
        $this->assertEquals(1, $json['summary']['partially_paid_bills_count']); // Bill 2
        $this->assertEquals(1, $json['summary']['fully_paid_bills_count']); // Bill 3
        $this->assertEquals(1, $json['summary']['overdue_bills_count']); // Bill 4
        $this->assertEquals(15000.00, $json['summary']['overdue_amount']);

        // Test Filter: Overdue Only
        $resOverdue = $this->getJson(route('procurement.reports.outstanding-bills', ['payment_status' => 'overdue']), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $jsonOverdue = $resOverdue->json();
        $this->assertEquals(1, $jsonOverdue['recordsFiltered']);
        $this->assertStringContainsString('VB-2026-0004', $jsonOverdue['data'][0]['bill_number']);

        // Test Filter: Unpaid Only
        $resUnpaid = $this->getJson(route('procurement.reports.outstanding-bills', ['payment_status' => 'unpaid']), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $jsonUnpaid = $resUnpaid->json();
        $this->assertEquals(2, $jsonUnpaid['recordsFiltered']);

        // Test Filter: Fully Paid Only
        $resPaid = $this->getJson(route('procurement.reports.outstanding-bills', ['payment_status' => 'paid']), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $jsonPaid = $resPaid->json();
        $this->assertEquals(1, $jsonPaid['recordsFiltered']);
        $this->assertStringContainsString('VB-2026-0003', $jsonPaid['data'][0]['bill_number']);

        // Reversal Test: Reverse $paymentFull (20,000)
        // Outstanding balance for Bill 3 must restore from 0 to 20,000!
        $paymentFull->update([
            'status' => VendorPayment::STATUS_REVERSED,
            'reversed_at' => now(),
            'reversal_reason' => 'Cheque dishonored by bank',
            'reversed_by' => $this->superAdmin->id,
        ]);

        $resAfterReversal = $this->getJson(route('procurement.reports.outstanding-bills'), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $jsonAfterReversal = $resAfterReversal->json();

        // Net posted paid is now only $paymentPosted (12,000)
        $this->assertEquals(12000.00, $jsonAfterReversal['summary']['total_paid_amount']);
        // Outstanding is now 115,000 - 12,000 = 103,000!
        $this->assertEquals(103000.00, $jsonAfterReversal['summary']['total_outstanding_amount']);
        // Fully paid bills count drops from 1 to 0
        $this->assertEquals(0, $jsonAfterReversal['summary']['fully_paid_bills_count']);
        // Unpaid bills count increases from 2 to 3 (Bill 1, Bill 3, Bill 4)
        $this->assertEquals(3, $jsonAfterReversal['summary']['unpaid_bills_count']);
    }

    public function test_outstanding_bill_details_offcanvas_and_export(): void
    {
        $this->actingAs($this->superAdmin);

        $bill = VendorBill::create([
            'bill_number' => 'VB-2026-0020',
            'vendor_invoice_number' => 'INV-777',
            'vendor_id' => $this->vendor1->id,
            'bill_date' => '2026-03-01',
            'due_date' => '2026-03-25',
            'status' => VendorBill::STATUS_APPROVED,
            'total_amount' => 18000.00,
            'created_by' => $this->treasurer->id,
        ]);

        VendorBillItem::create([
            'vendor_bill_id' => $bill->id,
            'financial_account_id' => $this->expenseAccount->id,
            'description' => 'Mandir Audio Equipment',
            'quantity' => 2.00,
            'unit' => 'set',
            'unit_price' => 9000.00,
            'total_price' => 18000.00,
        ]);

        // Details offcanvas
        $detailsRes = $this->get(route('procurement.reports.outstanding-bills.details', $bill->id), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $detailsRes->assertOk()->assertSee('Mandir Audio Equipment')->assertSee('VB-2026-0020');

        // Export endpoint
        $exportRes = $this->get(route('procurement.reports.outstanding-bills.export'));
        $exportRes->assertOk();
        $this->assertStringContainsString('text/csv', $exportRes->headers->get('Content-Type'));

        // Print endpoint
        $printRes = $this->get(route('procurement.reports.outstanding-bills.print'));
        $printRes->assertOk()->assertSee('VB-2026-0020');
    }
}
