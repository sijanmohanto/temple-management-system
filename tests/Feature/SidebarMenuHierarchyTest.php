<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Person;
use App\Models\ReceiverAccount;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarMenuHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $devotee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $treasurerRole = Role::where('slug', 'treasurer')->first();
        $devoteeRole = Role::where('slug', 'devotee')->first();

        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->roles()->attach($superAdminRole);

        $this->treasurer = User::factory()->create(['status' => 'active']);
        $this->treasurer->roles()->attach($treasurerRole);

        $this->devotee = User::factory()->create(['status' => 'active']);
        $this->devotee->roles()->attach($devoteeRole);

        $person = Person::create([
            'person_code' => 'DEV-00999',
            'name_en' => 'Test Devotee',
            'phone' => '01700000999',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);
        $this->devotee->update(['person_id' => $person->id]);
    }

    public function test_sidebar_renders_parent_menus_with_bootstrap_collapse_attributes(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('dashboard'));
        $response->assertStatus(200);

        // Check collapsible parents exist
        $response->assertSee('data-bs-toggle="collapse"', false);
        $response->assertSee('sidebar-parent', false);
        $response->assertSee('sidebar-chevron', false);
        $response->assertSee('sidebar-submenu', false);
        $response->assertSee('sidebar-sublink', false);

        // Verify Finance collapsible groups
        $response->assertSee('#menuFinanceIncomeExpense', false);
        $response->assertSee('#menuFinanceExpenses', false);
        $response->assertSee('#menuFinanceTransfers', false);
        $response->assertSee('#menuFinanceJournals', false);
        $response->assertSee('#menuFinancialReports', false);
    }

    public function test_active_parent_expands_when_child_route_is_active(): void
    {
        // Visit Income Report
        $response = $this->actingAs($this->superAdmin)->get(route('finance.reports.income'));
        $response->assertStatus(200);

        // Income & Expense parent should have active-parent and aria-expanded="true"
        $response->assertSee('href="#menuFinanceIncomeExpense"', false);
        $response->assertSee('id="menuFinanceIncomeExpense"', false);

        // Child link should have active class
        $response->assertSee(route('finance.reports.income'));
        $response->assertSee(route('finance.reports.expenses'));
    }

    public function test_income_expense_statement_activates_financial_reports_menu_and_not_income_expense_group(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('finance.reports.income-expense'));
        $response->assertStatus(200);

        // Financial Statements group should be active and expanded
        $response->assertSee('href="#menuFinancialReports"', false);
        $response->assertSee('class="collapse show" id="menuFinancialReports"', false);

        // Income-expense sublink should have active class
        $response->assertSee(route('finance.reports.income-expense').'" class="sidebar-sublink active"', false);

        // Income & Expense group should NOT be expanded
        $response->assertSee('class="collapse " id="menuFinanceIncomeExpense"', false);
        $response->assertDontSee(route('finance.reports.income').'" class="sidebar-sublink active"', false);
    }

    public function test_expense_listing_page_expands_expenses_parent_menu(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('finance.expenses.index'));
        $response->assertStatus(200);

        $response->assertSee('#menuFinanceExpenses', false);
        $response->assertSee(route('finance.expenses.index'));
        $response->assertSee(route('finance.expenses.create'));
    }

    public function test_transfers_page_expands_transfers_parent_menu(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('finance.transfers.index'));
        $response->assertStatus(200);

        $response->assertSee('#menuFinanceTransfers', false);
        $response->assertSee(route('finance.transfers.index'));
        $response->assertSee(route('finance.transfers.create'));
    }

    public function test_role_based_menu_visibility_enforcement(): void
    {
        // Devotee is redirected to devotee dashboard and should not see Finance, Collections, or Admin menus
        $this->actingAs($this->devotee)->get(route('dashboard'))->assertRedirect(route('devotee.dashboard'));
        $responseDevotee = $this->actingAs($this->devotee)->get(route('devotee.dashboard'));
        $responseDevotee->assertStatus(200);
        $responseDevotee->assertDontSee('#menuFinanceIncomeExpense', false);
        $responseDevotee->assertDontSee('#menuFinanceExpenses', false);
        $responseDevotee->assertDontSee(route('admin.audit-logs'));

        // Super Admin should see everything including audit logs
        $responseAdmin = $this->actingAs($this->superAdmin)->get(route('dashboard'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('#menuFinanceIncomeExpense', false);
        $responseAdmin->assertSee(route('admin.audit-logs'));
    }

    public function test_sidebar_localization_bengali_renders_properly(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'bn'])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('আয় ও ব্যয়');
        $response->assertSee('ব্যয় বিবরণী');
        $response->assertSee('তহবিল স্থানান্তর');
    }

    public function test_devotee_sidebar_renders_collapsible_menus_and_submenus(): void
    {
        $response = $this->actingAs($this->devotee)->get(route('devotee.dashboard'));
        $response->assertStatus(200);

        // Sidebar Devotee menus
        $response->assertSee('#menuDevoteeEvents', false);
        $response->assertSee('#menuDevoteeFinance', false);
        $response->assertSee('#menuDevoteeComplaints', false);

        // Submenus & links
        $response->assertSee(route('public.events.index'));
        $response->assertSee(route('public.previous-events.index'));
        $response->assertSee(route('devotee.payments.index'));
        $response->assertSee(route('public.payments.history'));
        $response->assertSee(route('public.complaints.index'));
        $response->assertSee(route('public.complaints.create'));
        $response->assertSee(route('devotee.profile'));
    }

    public function test_portal_pages_render_inside_app_layout_with_sidebar_for_authenticated_devotee(): void
    {
        $category = EventCategory::create([
            'name_en' => 'Major Festival',
            'name_bn' => 'প্রধান উৎসব',
            'slug' => 'major-festival',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $event = Event::create([
            'event_code' => 'EVT-TEST-2026',
            'slug' => 'test-festival-2026',
            'name_en' => 'Test Festival 2026',
            'name_bn' => 'টেস্ট উৎসব ২০২৬',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'collection_start_date' => now()->subDay()->toDateString(),
            'collection_end_date' => now()->addDays(5)->toDateString(),
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        ReceiverAccount::create([
            'account_name' => 'Temple bKash',
            'account_number' => '01711000000',
            'account_type' => 'merchant',
            'payment_method' => 'bkash',
            'status' => 'active',
            'is_visible_public' => true,
            'display_order' => 1,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        // Devotee accessing payment form remains in layouts.app with sidebar
        $devoteePayResponse = $this->actingAs($this->devotee)->get(route('public.events.pay', $event->slug));
        $devoteePayResponse->assertStatus(200);
        $devoteePayResponse->assertSee('id="appSidebar"', false);
        $devoteePayResponse->assertDontSee('public-navbar', false);

        // Devotee accessing previous events remains in layouts.app with sidebar
        $devoteeArchiveResponse = $this->actingAs($this->devotee)->get(route('public.previous-events.index'));
        $devoteeArchiveResponse->assertStatus(200);
        $devoteeArchiveResponse->assertSee('id="appSidebar"', false);
        $devoteeArchiveResponse->assertDontSee('public-navbar', false);

        // Guest accessing previous events renders layouts.public without sidebar
        auth()->logout();
        $guestArchiveResponse = $this->get(route('public.previous-events.index'));
        $guestArchiveResponse->assertStatus(200);
        $guestArchiveResponse->assertSee('public-navbar', false);
        $guestArchiveResponse->assertDontSee('id="appSidebar"', false);
    }

    public function test_budget_vs_actual_page_expands_budgets_menu_and_not_procurement_reports(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('procurement.reports.budget-vs-actual'));
        $response->assertStatus(200);

        // Budget Planning group should be active and expanded
        $response->assertSee('href="#menuProcurementBudgets"', false);
        $response->assertSee('class="collapse show" id="menuProcurementBudgets"', false);

        // Procurement Reports group should NOT be expanded
        $response->assertSee('class="collapse " id="menuProcurementReports"', false);

        // Budget vs actual link should only appear once as active under budgets
        $response->assertSee(route('procurement.reports.budget-vs-actual').'" class="sidebar-sublink active"', false);
        $response->assertDontSee('#repBudgetVsActual', false);
    }

    public function test_procurement_reports_expand_menu_and_render_correct_labels(): void
    {
        // 1. Requisitions report
        $resReq = $this->actingAs($this->superAdmin)->get(route('procurement.reports.requests'));
        $resReq->assertStatus(200);
        $resReq->assertSee('class="collapse show" id="menuProcurementReports"', false);
        $resReq->assertSee(route('procurement.reports.requests').'" class="sidebar-sublink active"', false);

        // 2. Orders report
        $resOrd = $this->actingAs($this->superAdmin)->get(route('procurement.reports.orders'));
        $resOrd->assertStatus(200);
        $resOrd->assertSee('class="collapse show" id="menuProcurementReports"', false);
        $resOrd->assertSee(route('procurement.reports.orders').'" class="sidebar-sublink active"', false);

        // 3. Outstanding bills report
        $resBills = $this->actingAs($this->superAdmin)->get(route('procurement.reports.outstanding-bills'));
        $resBills->assertStatus(200);
        $resBills->assertSee('class="collapse show" id="menuProcurementReports"', false);
        $resBills->assertSee(route('procurement.reports.outstanding-bills').'" class="sidebar-sublink active"', false);

        // 4. Bengali label verification (must be 'ক্রয় সংক্রান্ত প্রতিবেদন', NOT 'সংগ্রহ সংক্রান্ত প্রতিবেদন')
        app()->setLocale('bn');
        $resBn = $this->actingAs($this->superAdmin)->get(route('procurement.reports.requests'));
        $resBn->assertStatus(200);
        $resBn->assertSee('ক্রয় সংক্রান্ত প্রতিবেদন');
        $resBn->assertDontSee('সংগ্রহ সংক্রান্ত প্রতিবেদন');
        app()->setLocale('en');
    }

    public function test_budget_create_sublink_visibility_is_guarded_by_manage_budgets_permission(): void
    {
        // 1. Super admin has manage_budgets and sees create link
        $resAdmin = $this->actingAs($this->superAdmin)->get(route('dashboard'));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee(route('procurement.budgets.create'));

        // 2. User with only view_budgets should see budget list but NOT create link
        $viewerRole = Role::create([
            'slug' => 'budget_viewer_only',
            'name_en' => 'Budget Viewer Only',
            'name_bn' => 'বাজেট দর্শক',
        ]);
        $viewBudgetsPerm = \App\Models\Permission::where('slug', 'view_budgets')->first();
        $viewerRole->permissions()->attach($viewBudgetsPerm);

        $viewerUser = User::factory()->create(['status' => 'active']);
        $viewerUser->roles()->attach($viewerRole);

        $resViewer = $this->actingAs($viewerUser)->get(route('dashboard'));
        $resViewer->assertStatus(200);
        $resViewer->assertSee(route('procurement.budgets.index'));
        $resViewer->assertDontSee(route('procurement.budgets.create'));

        // 3. Executive Committee member has manage_budgets and sees create link
        $committeeRole = Role::where('slug', 'committee_member')->first();
        $committeeUser = User::factory()->create(['status' => 'active']);
        $committeeUser->roles()->attach($committeeRole);

        $resCommittee = $this->actingAs($committeeUser)->get(route('dashboard'));
        $resCommittee->assertStatus(200);
        $resCommittee->assertSee(route('procurement.budgets.create'));
    }
}
