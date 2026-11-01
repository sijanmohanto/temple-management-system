<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\Role;
use App\Models\User;
use App\Services\BudgetService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $templeAdmin;

    protected User $devotee;

    protected Event $event;

    protected FinancialAccount $expenseAccount1;

    protected FinancialAccount $expenseAccount2;

    protected FinancialAccount $paymentAccount;

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
            'slug' => 'religious-puja',
            'is_active' => true,
        ]);

        $this->event = Event::create([
            'event_code' => 'EVT-2026-0001',
            'name_en' => 'Durga Puja 2026',
            'name_bn' => 'শারদীয় দুর্গোৎসব ২০২৬',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-20',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->expenseAccount1 = FinancialAccount::create([
            'account_code' => '5100',
            'account_name_en' => 'Puja Materials & Flowers',
            'account_name_bn' => 'পূজা সামগ্রী ও ফুল',
            'account_type' => 'expense',
            'normal_balance' => 'debit',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->expenseAccount2 = FinancialAccount::create([
            'account_code' => '5200',
            'account_name_en' => 'Prasad & Bhog Expenses',
            'account_name_bn' => 'প্রসাদ ও ভোগ খরচ',
            'account_type' => 'expense',
            'normal_balance' => 'debit',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->paymentAccount = FinancialAccount::create([
            'account_code' => '1010',
            'account_name_en' => 'Main Cash Book',
            'account_name_bn' => 'প্রধান ক্যাশ বহি',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_access_budgets(): void
    {
        $response = $this->get(route('procurement.budgets.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_cannot_access_budgets(): void
    {
        $response = $this->actingAs($this->devotee)->get(route('procurement.budgets.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_budget_list_and_create_pages(): void
    {
        $response = $this->actingAs($this->treasurer)->get(route('procurement.budgets.index'));
        $response->assertStatus(200);
        $response->assertSee('Budget List');
        $response->assertSee('budgetTable');

        $responseCreate = $this->actingAs($this->treasurer)->get(route('procurement.budgets.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('Create Budget');
        $responseCreate->assertSee('Puja Materials & Flowers');
    }

    public function test_user_can_create_event_budget_with_unique_code_generation(): void
    {
        $payload = [
            'title_en' => 'Durga Puja 2026 Official Budget',
            'title_bn' => 'দুর্গাপূজা ২০২৬ অফিশিয়াল বাজেট',
            'budget_type' => Budget::TYPE_EVENT,
            'fiscal_year' => 2026,
            'event_id' => $this->event->id,
            'notes' => 'Allocations for flowers, prasad, and priest seva.',
            'items' => [
                [
                    'financial_account_id' => $this->expenseAccount1->id,
                    'allocated_amount' => 50000.00,
                    'notes' => 'Decorations & flowers',
                ],
                [
                    'financial_account_id' => $this->expenseAccount2->id,
                    'allocated_amount' => 30000.00,
                    'notes' => 'Bhog raw materials',
                ],
            ],
        ];

        $response = $this->actingAs($this->treasurer)->post(route('procurement.budgets.store'), $payload);

        $budget = Budget::where('event_id', $this->event->id)->first();
        $this->assertNotNull($budget);
        $response->assertRedirect(route('procurement.budgets.show', $budget));

        $this->assertMatchesRegularExpression('/^BDG-2026-\d{4}$/', $budget->budget_code);
        $this->assertEquals(80000.00, (float) $budget->total_amount);
        $this->assertEquals(Budget::STATUS_DRAFT, $budget->status);
        $this->assertEquals(2, $budget->items()->count());
    }

    public function test_event_budget_requires_event_id(): void
    {
        $payload = [
            'title_en' => 'Event Budget Missing Event',
            'budget_type' => Budget::TYPE_EVENT,
            'fiscal_year' => 2026,
            'event_id' => null,
            'items' => [
                [
                    'financial_account_id' => $this->expenseAccount1->id,
                    'allocated_amount' => 10000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->treasurer)->post(route('procurement.budgets.store'), $payload);
        $response->assertSessionHasErrors('event_id');
    }

    public function test_duplicate_active_or_approved_budget_for_same_scope_is_prevented(): void
    {
        // Create and approve budget for Durga Puja
        $budget1 = Budget::create([
            'budget_code' => 'BDG-2026-0001',
            'title_en' => 'Durga Puja Budget 1',
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
            'budget_id' => $budget1->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'allocated_amount' => 50000.00,
        ]);

        // Attempting to create a second budget for same event should fail validation
        $payload = [
            'title_en' => 'Durga Puja Budget 2 (Duplicate)',
            'budget_type' => Budget::TYPE_EVENT,
            'fiscal_year' => 2026,
            'event_id' => $this->event->id,
            'items' => [
                [
                    'financial_account_id' => $this->expenseAccount2->id,
                    'allocated_amount' => 20000.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->treasurer)->post(route('procurement.budgets.store'), $payload);
        $response->assertSessionHasErrors('budget_type');
    }

    public function test_budget_submission_workflow(): void
    {
        $budget = Budget::create([
            'budget_code' => 'BDG-2026-0002',
            'title_en' => 'Annual General Fund Budget 2026',
            'budget_type' => Budget::TYPE_ANNUAL,
            'fiscal_year' => 2026,
            'event_id' => null,
            'total_amount' => 100000.00,
            'status' => Budget::STATUS_DRAFT,
            'created_by' => $this->templeAdmin->id,
        ]);

        BudgetItem::create([
            'budget_id' => $budget->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'allocated_amount' => 100000.00,
        ]);

        $response = $this->actingAs($this->templeAdmin)->post(route('procurement.budgets.submit', $budget));
        $response->assertRedirect();

        $budget->refresh();
        $this->assertEquals(Budget::STATUS_SUBMITTED, $budget->status);
        $this->assertEquals($this->templeAdmin->id, $budget->submitted_by);
        $this->assertNotNull($budget->submitted_at);
    }

    public function test_strict_separation_of_duties_prevents_self_approval(): void
    {
        // Temple Admin creates and submits budget
        $budget = Budget::create([
            'budget_code' => 'BDG-2026-0003',
            'title_en' => 'General Budget',
            'budget_type' => Budget::TYPE_GENERAL_FUND,
            'fiscal_year' => 2026,
            'event_id' => null,
            'total_amount' => 60000.00,
            'status' => Budget::STATUS_SUBMITTED,
            'created_by' => $this->treasurer->id,
            'submitted_by' => $this->treasurer->id,
            'submitted_at' => now(),
        ]);

        BudgetItem::create([
            'budget_id' => $budget->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'allocated_amount' => 60000.00,
        ]);

        // Treasurer cannot self-approve their own created budget
        $response = $this->actingAs($this->treasurer)->post(route('procurement.budgets.approve', $budget));
        $response->assertSessionHasErrors('budget');

        $budget->refresh();
        $this->assertEquals(Budget::STATUS_SUBMITTED, $budget->status);

        // Super Admin CAN approve
        $responseAdmin = $this->actingAs($this->superAdmin)->post(route('procurement.budgets.approve', $budget));
        $responseAdmin->assertRedirect();

        $budget->refresh();
        $this->assertEquals(Budget::STATUS_APPROVED, $budget->status);
        $this->assertEquals($this->superAdmin->id, $budget->approved_by);
    }

    public function test_budget_rejection_requires_mandatory_reason(): void
    {
        $budget = Budget::create([
            'budget_code' => 'BDG-2026-0004',
            'title_en' => 'Submitted Budget',
            'budget_type' => Budget::TYPE_ANNUAL,
            'fiscal_year' => 2026,
            'event_id' => null,
            'total_amount' => 45000.00,
            'status' => Budget::STATUS_SUBMITTED,
            'created_by' => $this->templeAdmin->id,
        ]);

        BudgetItem::create([
            'budget_id' => $budget->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'allocated_amount' => 45000.00,
        ]);

        // Rejection without reason fails
        $responseNoReason = $this->actingAs($this->treasurer)->post(route('procurement.budgets.reject', $budget), [
            'rejection_reason' => '',
        ]);
        $responseNoReason->assertSessionHasErrors('rejection_reason');

        // Rejection with reason succeeds
        $responseWithReason = $this->actingAs($this->treasurer)->post(route('procurement.budgets.reject', $budget), [
            'rejection_reason' => 'Total allocation exceeds expected treasury cash flows for Q1.',
        ]);
        $responseWithReason->assertRedirect();

        $budget->refresh();
        $this->assertEquals(Budget::STATUS_REJECTED, $budget->status);
        $this->assertEquals('Total allocation exceeds expected treasury cash flows for Q1.', $budget->rejection_reason);
        $this->assertEquals($this->treasurer->id, $budget->rejected_by);
    }

    public function test_budget_vs_actual_calculation_accuracy_and_single_expense_counting(): void
    {
        // 1. Create and approve budget for Durga Puja:
        // Flowers: 50,000, Prasad: 30,000. Total Budget: 80,000.
        $budget = Budget::create([
            'budget_code' => 'BDG-2026-0005',
            'title_en' => 'Durga Puja 2026 Budget',
            'budget_type' => Budget::TYPE_EVENT,
            'fiscal_year' => 2026,
            'event_id' => $this->event->id,
            'total_amount' => 80000.00,
            'status' => Budget::STATUS_APPROVED,
            'created_by' => $this->templeAdmin->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
        ]);

        BudgetItem::create([
            'budget_id' => $budget->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'allocated_amount' => 50000.00,
        ]);

        BudgetItem::create([
            'budget_id' => $budget->id,
            'financial_account_id' => $this->expenseAccount2->id,
            'allocated_amount' => 30000.00,
        ]);

        // 2. Add approved expense for Flowers: 35,000
        Expense::create([
            'expense_number' => 'EXP-2026-0001',
            'expense_date' => '2026-10-16',
            'financial_account_id' => $this->expenseAccount1->id,
            'payment_account_id' => $this->paymentAccount->id,
            'amount' => 35000.00,
            'title_en' => 'Flowers for Puja',
            'event_id' => $this->event->id,
            'status' => Expense::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
        ]);

        // 3. Add pending expense for Flowers: 10,000 (must be separate, not added to actual)
        Expense::create([
            'expense_number' => 'EXP-2026-0002',
            'expense_date' => '2026-10-17',
            'financial_account_id' => $this->expenseAccount1->id,
            'payment_account_id' => $this->paymentAccount->id,
            'amount' => 10000.00,
            'title_en' => 'Pending Flower Invoice',
            'event_id' => $this->event->id,
            'status' => Expense::STATUS_PENDING,
            'created_by' => $this->treasurer->id,
        ]);

        // 4. Add rejected expense for Prasad: 15,000 (must be completely ignored)
        Expense::create([
            'expense_number' => 'EXP-2026-0003',
            'expense_date' => '2026-10-17',
            'financial_account_id' => $this->expenseAccount2->id,
            'payment_account_id' => $this->paymentAccount->id,
            'amount' => 15000.00,
            'title_en' => 'Rejected Prasad bill',
            'event_id' => $this->event->id,
            'status' => 'rejected',
            'created_by' => $this->treasurer->id,
            'rejected_by' => $this->superAdmin->id,
            'rejection_reason' => 'Invalid voucher',
        ]);

        // 5. Add approved expense for Prasad: 20,000
        Expense::create([
            'expense_number' => 'EXP-2026-0004',
            'expense_date' => '2026-10-18',
            'financial_account_id' => $this->expenseAccount2->id,
            'payment_account_id' => $this->paymentAccount->id,
            'amount' => 20000.00,
            'title_en' => 'Bhog Milk & Fruits',
            'event_id' => $this->event->id,
            'status' => Expense::STATUS_APPROVED,
            'created_by' => $this->treasurer->id,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
        ]);

        // Run calculation
        $service = app(BudgetService::class);
        $result = $service->calculateBudgetVsActual($budget);

        $summary = $result['summary'];
        $this->assertEquals(80000.00, $summary['total_budget']);
        $this->assertEquals(55000.00, $summary['total_actual']); // 35,000 + 20,000
        $this->assertEquals(10000.00, $summary['total_pending']); // 10,000 pending
        $this->assertEquals(25000.00, $summary['total_remaining']); // 80,000 - 55,000
        $this->assertEquals(0.00, $summary['total_over_budget']);
        $this->assertEquals(68.8, $summary['overall_utilization_pct']); // 55000 / 80000 * 100 = 68.75% -> 68.8%

        // View the Budget vs Actual page
        $response = $this->actingAs($this->treasurer)->get(route('procurement.reports.budget-vs-actual', ['budget_id' => $budget->id]));
        $response->assertStatus(200);
        $response->assertSee('৳55,000.00'); // Total actual
        $response->assertSee('৳25,000.00'); // Total remaining
        $response->assertSee('৳10,000.00'); // Total pending
        $response->assertSee('68.8%');
    }

    public function test_budget_datatable_server_side_json_response_has_strictly_no_row_number_column(): void
    {
        Budget::create([
            'budget_code' => 'BDG-2026-0006',
            'title_en' => 'Test Budget DT',
            'budget_type' => Budget::TYPE_ANNUAL,
            'fiscal_year' => 2026,
            'total_amount' => 12500.00,
            'status' => Budget::STATUS_APPROVED,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson(route('procurement.budgets.index', ['draw' => 1]));
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertArrayHasKey('data', $json);
        $this->assertNotEmpty($json['data']);

        $firstRow = $json['data'][0];
        $expectedKeys = ['budget_code', 'title', 'budget_type', 'scope_period', 'total_amount', 'status', 'actions'];

        // Assert strictly NO row number / '#' column
        $this->assertArrayNotHasKey('#', $firstRow);
        $this->assertArrayNotHasKey('sl', $firstRow);
        $this->assertArrayNotHasKey('id', $firstRow);
        $this->assertEquals($expectedKeys, array_keys($firstRow));
    }

    public function test_bengali_locale_renders_clean_localized_labels_without_raw_translation_keys(): void
    {
        $budget = Budget::create([
            'budget_code' => 'BDG-2026-0007',
            'title_en' => 'Durga Puja Budget',
            'title_bn' => 'শারদীয় দুর্গোৎসব বাজেট',
            'budget_type' => Budget::TYPE_EVENT,
            'fiscal_year' => 2026,
            'event_id' => $this->event->id,
            'total_amount' => 25000.00,
            'status' => Budget::STATUS_APPROVED,
            'created_by' => $this->superAdmin->id,
        ]);

        BudgetItem::create([
            'budget_id' => $budget->id,
            'financial_account_id' => $this->expenseAccount1->id,
            'allocated_amount' => 25000.00,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'bn'])
            ->get(route('procurement.budgets.show', $budget));

        $response->assertStatus(200);
        $response->assertSee('শারদীয় দুর্গোৎসব বাজেট');
        $response->assertSee('অনুমোদিত');
        $response->assertSee('বাজেট বনাম প্রকৃত ব্যয়');

        // Assert no unrendered translation keys appear
        $response->assertDontSee('procurement.');
    }
}
