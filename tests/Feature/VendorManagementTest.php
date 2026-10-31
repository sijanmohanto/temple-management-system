<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $templeAdmin;

    protected User $devotee;

    protected FinancialAccount $expenseAccount;

    protected FinancialAccount $paymentAccount;

    protected Event $event;

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
            'name_en' => 'Puja Festival',
            'name_bn' => 'পূজা উৎসব',
            'slug' => 'puja-festival-'.uniqid(),
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

        $this->expenseAccount = FinancialAccount::create([
            'account_code' => '5101',
            'account_name_en' => 'Puja Materials Expense',
            'account_name_bn' => 'পূজা সামগ্রী খরচ',
            'account_type' => 'expense',
            'normal_balance' => 'debit',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->paymentAccount = FinancialAccount::create([
            'account_code' => '1001',
            'account_name_en' => 'Main Cash In Hand',
            'account_name_bn' => 'প্রধান নগদ তহবিল',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_system' => true,
            'is_active' => true,
        ]);
    }

    public function test_guests_cannot_access_vendor_endpoints(): void
    {
        $response = $this->get(route('procurement.vendors.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('procurement.vendors.create'));
        $response->assertRedirect(route('login'));

        $response = $this->post(route('procurement.vendors.store'), []);
        $response->assertRedirect(route('login'));
    }

    public function test_unauthorized_user_cannot_access_vendor_endpoints(): void
    {
        $this->actingAs($this->devotee);

        $response = $this->get(route('procurement.vendors.index'));
        $response->assertStatus(403);

        $response = $this->get(route('procurement.vendors.create'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_vendor_index_page(): void
    {
        $this->actingAs($this->templeAdmin);

        $response = $this->get(route('procurement.vendors.index'));
        $response->assertStatus(200);
        $response->assertSee(__('procurement.vendor_list'));
        $response->assertSee(__('procurement.total_vendors'));
    }

    public function test_vendor_code_is_generated_in_vnd_format(): void
    {
        $code1 = Vendor::generateVendorCode();
        $this->assertEquals('VND-0001', $code1);

        $vendor1 = Vendor::create([
            'vendor_code' => $code1,
            'name' => 'Roy Sweet Store',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $code2 = Vendor::generateVendorCode();
        $this->assertEquals('VND-0002', $code2);
    }

    public function test_authorized_user_can_create_vendor_with_valid_data(): void
    {
        $this->actingAs($this->templeAdmin);

        $payload = [
            'name' => 'Goddess Decorators',
            'vendor_type' => Vendor::TYPE_DECORATION_LIGHTING,
            'contact_person' => 'Subhashish Dey',
            'phone' => '01711223344',
            'email' => 'subhashish@example.com',
            'tax_id' => 'TR-1029384',
            'address' => 'Station Road, Temple Bazaar',
            'notes' => 'Decorations and sound lighting specialist.',
            'is_active' => '1',
        ];

        $response = $this->post(route('procurement.vendors.store'), $payload);

        $vendor = Vendor::where('name', 'Goddess Decorators')->first();
        $this->assertNotNull($vendor);
        $this->assertEquals('VND-0001', $vendor->vendor_code);
        $this->assertEquals('Subhashish Dey', $vendor->contact_person);
        $this->assertEquals('01711223344', $vendor->phone);
        $this->assertTrue($vendor->is_active);
        $this->assertEquals($this->templeAdmin->id, $vendor->created_by);

        $response->assertRedirect(route('procurement.vendors.show', $vendor));
    }

    public function test_vendor_validation_prevents_duplicate_phone_and_tax_id(): void
    {
        $this->actingAs($this->superAdmin);

        Vendor::create([
            'vendor_code' => 'VND-0001',
            'name' => 'Existing Vendor',
            'phone' => '01700112233',
            'tax_id' => 'TIN-99999',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        // Attempt to create another vendor with same phone and tax_id
        $response = $this->post(route('procurement.vendors.store'), [
            'name' => 'Duplicate Vendor',
            'phone' => '01700112233',
            'tax_id' => 'TIN-99999',
        ]);

        $response->assertSessionHasErrors(['phone', 'tax_id']);
    }

    public function test_vendor_update_allows_keeping_same_phone_and_tax_id(): void
    {
        $this->actingAs($this->superAdmin);

        $vendor = Vendor::create([
            'vendor_code' => 'VND-0001',
            'name' => 'Original Vendor',
            'phone' => '01700112233',
            'tax_id' => 'TIN-99999',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $response = $this->put(route('procurement.vendors.update', $vendor), [
            'name' => 'Updated Vendor Name',
            'phone' => '01700112233',
            'tax_id' => 'TIN-99999',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('procurement.vendors.show', $vendor));
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => 'Updated Vendor Name',
            'phone' => '01700112233',
        ]);
    }

    public function test_vendor_active_status_can_be_toggled(): void
    {
        $this->actingAs($this->treasurer);

        $vendor = Vendor::create([
            'vendor_code' => 'VND-0001',
            'name' => 'Active Vendor',
            'is_active' => true,
            'created_by' => $this->treasurer->id,
            'updated_by' => $this->treasurer->id,
        ]);

        // Toggle from active to inactive
        $response = $this->post(route('procurement.vendors.toggle-status', $vendor));
        $vendor->refresh();
        $this->assertFalse($vendor->is_active);

        // Toggle from inactive back to active
        $response = $this->post(route('procurement.vendors.toggle-status', $vendor));
        $vendor->refresh();
        $this->assertTrue($vendor->is_active);
    }

    public function test_cannot_delete_vendor_with_linked_expenses(): void
    {
        $this->actingAs($this->superAdmin);

        $vendor = Vendor::create([
            'vendor_code' => 'VND-0001',
            'name' => 'Protected Vendor',
            'phone' => '01911998877',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        // Create an expense linked to this vendor
        Expense::create([
            'expense_number' => 'EXP-2026-0001',
            'expense_date' => now()->toDateString(),
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name,
            'vendor_phone' => $vendor->phone,
            'financial_account_id' => $this->expenseAccount->id,
            'payment_account_id' => $this->paymentAccount->id,
            'amount' => 5000.00,
            'title_en' => 'Puja flowers and fruits',
            'payment_method' => 'cash',
            'status' => Expense::STATUS_APPROVED,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertTrue($vendor->hasProtectedTransactions());

        // Attempting to delete should fail
        $response = $this->delete(route('procurement.vendors.destroy', $vendor));

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_delete_vendor_without_linked_expenses(): void
    {
        $this->actingAs($this->superAdmin);

        $vendor = Vendor::create([
            'vendor_code' => 'VND-0001',
            'name' => 'Unused Vendor',
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $this->assertFalse($vendor->hasProtectedTransactions());

        $response = $this->delete(route('procurement.vendors.destroy', $vendor));
        $response->assertRedirect(route('procurement.vendors.index'));

        $this->assertSoftDeleted('vendors', [
            'id' => $vendor->id,
        ]);
    }

    public function test_vendor_search_ajax_returns_select2_formatted_data(): void
    {
        $this->actingAs($this->treasurer);

        $activeVendor = Vendor::create([
            'vendor_code' => 'VND-0001',
            'name' => 'Active Sweet Shop',
            'phone' => '01811223344',
            'is_active' => true,
            'created_by' => $this->treasurer->id,
            'updated_by' => $this->treasurer->id,
        ]);

        $inactiveVendor = Vendor::create([
            'vendor_code' => 'VND-0002',
            'name' => 'Inactive Shop',
            'phone' => '01899887766',
            'is_active' => false,
            'created_by' => $this->treasurer->id,
            'updated_by' => $this->treasurer->id,
        ]);

        $response = $this->getJson(route('procurement.vendors.search', ['q' => 'Sweet']));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'results' => [
                '*' => ['id', 'text', 'name', 'vendor_code'],
            ],
        ]);

        $data = $response->json('results');
        $this->assertCount(1, $data);
        $this->assertEquals($activeVendor->id, $data[0]['id']);

        // When inactive allowed, both return if matching
        $responseAll = $this->getJson(route('procurement.vendors.search', ['q' => 'Shop', 'active_only' => '0']));
        $this->assertCount(2, $responseAll->json('results'));
    }

    public function test_datatables_json_endpoint_returns_correct_columns_without_row_number(): void
    {
        $this->actingAs($this->superAdmin);

        Vendor::create([
            'vendor_code' => 'VND-0001',
            'name' => 'Choudhury Brass Emporium',
            'vendor_type' => Vendor::TYPE_PUJA_MATERIALS,
            'phone' => '01722334455',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $response = $this->getJson(route('procurement.vendors.index'), ['X-Requested-With' => 'XMLHttpRequest']);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                '*' => [
                    'vendor_code',
                    'name',
                    'contact',
                    'vendor_type',
                    'transactions',
                    'status',
                    'actions',
                ],
            ],
        ]);

        $firstRow = $response->json('data.0');
        // Assert that NO serial number / # column exists
        $this->assertArrayNotHasKey('#', $firstRow);
        $this->assertArrayNotHasKey('sl', $firstRow);
        $this->assertArrayNotHasKey('id', $firstRow);
        $this->assertStringContainsString('VND-0001', $firstRow['vendor_code']);
        $this->assertStringContainsString('Choudhury Brass Emporium', $firstRow['name']);
    }

    public function test_bengali_locale_renders_vendor_views_without_errors(): void
    {
        $this->actingAs($this->superAdmin);
        app()->setLocale('bn');

        $vendor = Vendor::create([
            'vendor_code' => 'VND-0001',
            'name' => 'রয় মিষ্টি ভাণ্ডার',
            'vendor_type' => Vendor::TYPE_FOOD_PRASAD,
            'contact_person' => 'বিজয় রয়',
            'phone' => '০১৭১১২২৩৩৪৪',
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
            'updated_by' => $this->superAdmin->id,
        ]);

        $indexResponse = $this->get(route('procurement.vendors.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee(__('procurement.vendor_list'));

        $showResponse = $this->get(route('procurement.vendors.show', $vendor));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('রয় মিষ্টি ভাণ্ডার');
        $showResponse->assertSee(__('procurement.vendor_details'));

        $createResponse = $this->get(route('procurement.vendors.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee(__('procurement.add_vendor'));
    }
}
