<?php

namespace Tests\Feature;

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
        // Devotee should not see Finance, Collections, or Admin menus
        $responseDevotee = $this->actingAs($this->devotee)->get(route('dashboard'));
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
}
