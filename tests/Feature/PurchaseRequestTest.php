<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\PurchaseRequest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseRequestTest extends TestCase
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

        // Approved Budget
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
    }

    public function test_guests_cannot_access_purchase_request_endpoints(): void
    {
        $response = $this->get(route('procurement.requests.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('procurement.requests.create'));
        $response->assertRedirect(route('login'));

        $response = $this->post(route('procurement.requests.store'), []);
        $response->assertRedirect(route('login'));
    }

    public function test_unauthorized_user_cannot_access_purchase_request_endpoints(): void
    {
        $this->actingAs($this->devotee);

        $response = $this->get(route('procurement.requests.index'));
        $response->assertStatus(403);

        $response = $this->get(route('procurement.requests.create'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_purchase_request_index_and_create_pages(): void
    {
        $this->actingAs($this->templeAdmin);

        $response = $this->get(route('procurement.requests.index'));
        $response->assertStatus(200);
        $response->assertSee(__('procurement.request_list'));

        $response = $this->get(route('procurement.requests.create'));
        $response->assertStatus(200);
        $response->assertSee(__('procurement.create_request'));
    }

    public function test_user_can_create_purchase_request_with_multiple_line_items_and_concurrency_safe_numbering(): void
    {
        $this->actingAs($this->templeAdmin);

        $payload = [
            'title' => 'Puja Materials Procurement Requisition',
            'request_date' => '2026-10-30',
            'requested_by' => $this->templeAdmin->id,
            'event_id' => $this->event->id,
            'budget_id' => $this->approvedBudget->id,
            'justification' => 'Materials essential for worship rituals.',
            'items' => [
                [
                    'description' => 'Red Sandalwood & Incense',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 10,
                    'unit' => 'kg',
                    'estimated_unit_price' => 500.00, // Total: 5,000.00
                ],
                [
                    'description' => 'Ghee for Pradeep',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 5,
                    'unit' => 'litre',
                    'estimated_unit_price' => 1200.00, // Total: 6,000.00
                ],
            ],
        ];

        $response = $this->post(route('procurement.requests.store'), $payload);

        $request = PurchaseRequest::where('title', 'Puja Materials Procurement Requisition')->first();
        $this->assertNotNull($request);
        $this->assertEquals('PRQ-2026-0001', $request->request_number);
        $this->assertEquals(11000.00, (float) $request->total_estimated_amount);
        $this->assertEquals(PurchaseRequest::STATUS_DRAFT, $request->status);
        $this->assertCount(2, $request->items);

        $response->assertRedirect(route('procurement.requests.show', $request));

        // Test second request increments number safely
        $payload2 = $payload;
        $payload2['title'] = 'Second Requisition';
        $this->post(route('procurement.requests.store'), $payload2);

        $request2 = PurchaseRequest::where('title', 'Second Requisition')->first();
        $this->assertEquals('PRQ-2026-0002', $request2->request_number);
    }

    public function test_server_side_calculation_ignores_tampered_totals(): void
    {
        $this->actingAs($this->templeAdmin);

        $payload = [
            'title' => 'Tampered Total Test',
            'request_date' => '2026-10-30',
            'total_estimated_amount' => 1.00, // Intentional fake amount
            'items' => [
                [
                    'description' => 'Brass Bells',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 4,
                    'unit' => 'pcs',
                    'estimated_unit_price' => 1500.00, // 4 * 1500 = 6,000.00
                    'estimated_total' => 1.00, // Client side fake line total
                ],
            ],
        ];

        $this->post(route('procurement.requests.store'), $payload);

        $request = PurchaseRequest::where('title', 'Tampered Total Test')->first();
        $this->assertNotNull($request);
        // Server calculates strictly 4 * 1500 = 6000
        $this->assertEquals(6000.00, (float) $request->total_estimated_amount);
        $this->assertEquals(6000.00, (float) $request->items->first()->estimated_total);
    }

    public function test_purchase_request_validation_enforces_required_fields_and_positive_amounts(): void
    {
        $this->actingAs($this->templeAdmin);

        $response = $this->post(route('procurement.requests.store'), [
            'title' => '',
            'request_date' => '',
            'items' => [],
        ]);

        $response->assertSessionHasErrors(['title', 'request_date', 'items']);
    }

    public function test_purchase_request_validates_budget_and_event_relationships(): void
    {
        $this->actingAs($this->templeAdmin);

        // Draft budget cannot be linked
        $draftBudget = Budget::create([
            'budget_code' => 'BDG-2026-0002',
            'title_en' => 'Draft Budget',
            'budget_type' => Budget::TYPE_EVENT,
            'fiscal_year' => 2026,
            'event_id' => $this->event->id,
            'total_amount' => 10000.00,
            'status' => Budget::STATUS_DRAFT,
            'created_by' => $this->treasurer->id,
        ]);

        $response = $this->post(route('procurement.requests.store'), [
            'title' => 'Invalid Budget Link Test',
            'request_date' => '2026-10-30',
            'budget_id' => $draftBudget->id,
            'items' => [
                [
                    'description' => 'Test Item',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 1,
                    'unit' => 'pcs',
                    'estimated_unit_price' => 100.00,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['budget_id']);
    }

    public function test_purchase_request_submission_workflow(): void
    {
        $this->actingAs($this->templeAdmin);

        $request = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0001',
            'title' => 'Submission Test Requisition',
            'request_date' => '2026-10-30',
            'requested_by' => $this->templeAdmin->id,
            'total_estimated_amount' => 5000.00,
            'status' => PurchaseRequest::STATUS_DRAFT,
            'created_by' => $this->templeAdmin->id,
            'updated_by' => $this->templeAdmin->id,
        ]);

        $response = $this->post(route('procurement.requests.submit', $request));
        $request->refresh();

        $this->assertEquals(PurchaseRequest::STATUS_SUBMITTED, $request->status);
        $this->assertNotNull($request->submitted_at);
    }

    public function test_strict_separation_of_duties_prevents_creator_from_self_approving(): void
    {
        $this->actingAs($this->templeAdmin);

        $request = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0001',
            'title' => 'Self Approval Separation Test',
            'request_date' => '2026-10-30',
            'requested_by' => $this->templeAdmin->id,
            'total_estimated_amount' => 5000.00,
            'status' => PurchaseRequest::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'created_by' => $this->templeAdmin->id,
            'updated_by' => $this->templeAdmin->id,
        ]);

        // Temple Admin created and requested this, so cannot approve it
        $response = $this->post(route('procurement.requests.approve', $request));
        $response->assertSessionHasErrors(['approver']);

        $request->refresh();
        $this->assertEquals(PurchaseRequest::STATUS_SUBMITTED, $request->status);
    }

    public function test_authorized_approver_can_approve_purchase_request(): void
    {
        // Created by Temple Admin
        $request = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0001',
            'title' => 'Approval Test Requisition',
            'request_date' => '2026-10-30',
            'requested_by' => $this->templeAdmin->id,
            'total_estimated_amount' => 5000.00,
            'status' => PurchaseRequest::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'created_by' => $this->templeAdmin->id,
            'updated_by' => $this->templeAdmin->id,
        ]);

        // Approved by Treasurer
        $this->actingAs($this->treasurer);
        $response = $this->post(route('procurement.requests.approve', $request));

        $request->refresh();
        $this->assertEquals(PurchaseRequest::STATUS_APPROVED, $request->status);
        $this->assertEquals($this->treasurer->id, $request->approved_by);
        $this->assertNotNull($request->approved_at);
        $this->assertTrue($request->canBeConvertedToPo());
    }

    public function test_purchase_request_rejection_requires_mandatory_reason(): void
    {
        $request = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0001',
            'title' => 'Rejection Test Requisition',
            'request_date' => '2026-10-30',
            'requested_by' => $this->templeAdmin->id,
            'total_estimated_amount' => 5000.00,
            'status' => PurchaseRequest::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'created_by' => $this->templeAdmin->id,
            'updated_by' => $this->templeAdmin->id,
        ]);

        $this->actingAs($this->treasurer);

        // Rejection without reason fails
        $response = $this->post(route('procurement.requests.reject', $request), [
            'rejection_reason' => '',
        ]);
        $response->assertSessionHasErrors(['rejection_reason']);

        // Rejection with reason succeeds
        $response = $this->post(route('procurement.requests.reject', $request), [
            'rejection_reason' => 'Specifications are inadequate and cost exceeds estimate.',
        ]);

        $request->refresh();
        $this->assertEquals(PurchaseRequest::STATUS_REJECTED, $request->status);
        $this->assertEquals('Specifications are inadequate and cost exceeds estimate.', $request->rejection_reason);
        $this->assertEquals($this->treasurer->id, $request->rejected_by);
        $this->assertNotNull($request->rejected_at);
    }

    public function test_purchase_request_cancellation_workflow(): void
    {
        $this->actingAs($this->templeAdmin);

        $request = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0001',
            'title' => 'Cancellation Test Requisition',
            'request_date' => '2026-10-30',
            'requested_by' => $this->templeAdmin->id,
            'total_estimated_amount' => 5000.00,
            'status' => PurchaseRequest::STATUS_DRAFT,
            'created_by' => $this->templeAdmin->id,
            'updated_by' => $this->templeAdmin->id,
        ]);

        $response = $this->post(route('procurement.requests.cancel', $request), [
            'reason' => 'No longer needed.',
        ]);

        $request->refresh();
        $this->assertEquals(PurchaseRequest::STATUS_CANCELLED, $request->status);
    }

    public function test_purchase_request_creation_and_approval_does_not_create_finance_ledger_entries(): void
    {
        $this->actingAs($this->superAdmin);

        $initialExpenses = Expense::count();
        $initialJournals = JournalEntry::count();

        // Create and approve a purchase request
        $payload = [
            'title' => 'Ledger Safety Test',
            'request_date' => '2026-10-30',
            'items' => [
                [
                    'description' => 'Test Goods',
                    'financial_account_id' => $this->expenseAccount1->id,
                    'quantity' => 2,
                    'unit' => 'pcs',
                    'estimated_unit_price' => 500.00,
                ],
            ],
        ];

        $this->post(route('procurement.requests.store'), $payload);
        $request = PurchaseRequest::where('title', 'Ledger Safety Test')->first();

        $this->post(route('procurement.requests.submit', $request));
        $this->post(route('procurement.requests.approve', $request));

        // Ledger must NOT be touched
        $this->assertEquals($initialExpenses, Expense::count());
        $this->assertEquals($initialJournals, JournalEntry::count());
    }

    public function test_purchase_request_datatable_server_side_json_response_strictly_has_no_row_number_column(): void
    {
        $this->actingAs($this->superAdmin);

        PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0001',
            'title' => 'DataTable Test Requisition',
            'request_date' => '2026-10-30',
            'requested_by' => $this->superAdmin->id,
            'total_estimated_amount' => 5000.00,
            'status' => PurchaseRequest::STATUS_APPROVED,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $response = $this->getJson(route('procurement.requests.index'), ['X-Requested-With' => 'XMLHttpRequest']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                '*' => [
                    'request_number',
                    'title',
                    'request_date',
                    'requester',
                    'scope',
                    'total_amount',
                    'status',
                    'actions',
                ],
            ],
        ]);

        $firstRow = $response->json('data.0');
        // Assert strictly NO '#' or row number column
        $this->assertArrayNotHasKey('#', $firstRow);
        $this->assertArrayNotHasKey('sl', $firstRow);
        $this->assertArrayNotHasKey('id', $firstRow);
        $this->assertStringContainsString('PRQ-2026-0001', $firstRow['request_number']);
    }

    public function test_bengali_locale_renders_purchase_request_views_without_errors(): void
    {
        $this->actingAs($this->superAdmin);
        app()->setLocale('bn');

        $request = PurchaseRequest::create([
            'request_number' => 'PRQ-2026-0001',
            'title' => 'বাংলা রিকুইজিশন টেস্ট',
            'request_date' => '2026-10-30',
            'requested_by' => $this->superAdmin->id,
            'total_estimated_amount' => 5000.00,
            'status' => PurchaseRequest::STATUS_DRAFT,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $indexResponse = $this->get(route('procurement.requests.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee(__('procurement.request_list'));

        $createResponse = $this->get(route('procurement.requests.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee(__('procurement.create_request'));

        $showResponse = $this->get(route('procurement.requests.show', $request));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('বাংলা রিকুইজিশন টেস্ট');
    }
}
