<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Family;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\ClassificationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiStandardizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $devotee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ClassificationSeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->devotee = User::factory()->create();
        $this->devotee->assignRole('devotee');
    }

    public function test_guest_cannot_access_people_search_ajax(): void
    {
        $response = $this->getJson(route('people.search', ['q' => 'Subrata']));
        $response->assertUnauthorized();
    }

    public function test_devotee_without_permission_cannot_access_people_search(): void
    {
        $response = $this->actingAs($this->devotee)->getJson(route('people.search', ['q' => 'Subrata']));
        $response->assertForbidden();
    }

    public function test_admin_can_search_people_via_select2_ajax(): void
    {
        $activePerson = Person::factory()->create([
            'name_en' => 'Subrata Majumder',
            'status' => 'active',
        ]);

        $inactivePerson = Person::factory()->create([
            'name_en' => 'Subrata Das (Inactive)',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson(route('people.search', ['q' => 'Subrata']));

        $response->assertOk()
            ->assertJsonStructure([
                'results' => [
                    '*' => ['id', 'text'],
                ],
            ]);

        $results = $response->json('results');
        $this->assertCount(1, $results);
        $this->assertEquals($activePerson->id, $results[0]['id']);
        $this->assertStringContainsString('Subrata Majumder', $results[0]['text']);
        $this->assertStringContainsString($activePerson->person_code, $results[0]['text']);
    }

    public function test_guest_cannot_access_families_search_ajax(): void
    {
        $response = $this->getJson(route('families.search', ['q' => 'Chatterjee']));
        $response->assertUnauthorized();
    }

    public function test_devotee_without_permission_cannot_access_families_search(): void
    {
        $response = $this->actingAs($this->devotee)->getJson(route('families.search', ['q' => 'Chatterjee']));
        $response->assertForbidden();
    }

    public function test_admin_can_search_families_via_select2_ajax(): void
    {
        $activeFamily = Family::factory()->create([
            'family_name' => 'Chatterjee Bari',
            'status' => 'active',
        ]);

        $inactiveFamily = Family::factory()->create([
            'family_name' => 'Chatterjee Branch (Inactive)',
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson(route('families.search', ['q' => 'Chatterjee']));

        $response->assertOk()
            ->assertJsonStructure([
                'results' => [
                    '*' => ['id', 'text'],
                ],
            ]);

        $results = $response->json('results');
        $this->assertCount(1, $results);
        $this->assertEquals($activeFamily->id, $results[0]['id']);
        $this->assertStringContainsString('Chatterjee Bari', $results[0]['text']);
    }

    public function test_people_datatable_server_side_json_response(): void
    {
        Person::factory()->count(5)->create(['status' => 'active']);
        Person::factory()->count(2)->create(['status' => 'inactive']);

        $response = $this->actingAs($this->superAdmin)->getJson(route('people.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'status' => 'active',
            'search' => ['value' => '', 'regex' => 'false'],
        ]));

        $response->assertOk()
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertEquals(7, $json['recordsTotal']);
        $this->assertEquals(5, $json['recordsFiltered']);
        $this->assertCount(5, $json['data']);
    }

    public function test_people_datatable_sorting_and_page_length_change(): void
    {
        Person::factory()->create(['name_en' => 'Aaron Banerjee', 'person_code' => 'DEV-00001', 'status' => 'active']);
        Person::factory()->create(['name_en' => 'Zack Mukherjee', 'person_code' => 'DEV-00002', 'status' => 'active']);
        Person::factory()->create(['name_en' => 'Bikash Roy', 'person_code' => 'DEV-00003', 'status' => 'inactive']);

        // 1. Sort by name ascending
        $sortAsc = $this->actingAs($this->superAdmin)->getJson(route('people.index', [
            'draw' => 10,
            'start' => 0,
            'length' => 25,
            'order' => [['column' => '1', 'dir' => 'asc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ]));
        $sortAsc->assertOk();
        $this->assertEquals(10, $sortAsc->json('draw'));
        $this->assertEquals(3, $sortAsc->json('recordsTotal'));
        $this->assertStringContainsString('Aaron Banerjee', $sortAsc->json('data.0.1'));

        // 2. Sort by name descending
        $sortDesc = $this->actingAs($this->superAdmin)->getJson(route('people.index', [
            'draw' => 11,
            'start' => 0,
            'length' => 50,
            'order' => [['column' => '1', 'dir' => 'desc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ]));
        $sortDesc->assertOk();
        $this->assertEquals(11, $sortDesc->json('draw'));
        $this->assertStringContainsString('Zack Mukherjee', $sortDesc->json('data.0.1'));

        // 3. Page length change to 1
        $lenChange = $this->actingAs($this->superAdmin)->getJson(route('people.index', [
            'draw' => 12,
            'start' => 0,
            'length' => 1,
            'order' => [['column' => '0', 'dir' => 'asc']],
            'search' => ['value' => '', 'regex' => 'false'],
        ]));
        $lenChange->assertOk();
        $this->assertCount(1, $lenChange->json('data'));
        $this->assertEquals(3, $lenChange->json('recordsTotal'));

        // 4. DataTables search query array
        $searchRes = $this->actingAs($this->superAdmin)->getJson(route('people.index', [
            'draw' => 13,
            'start' => 0,
            'length' => 15,
            'order' => [['column' => '0', 'dir' => 'asc']],
            'search' => ['value' => 'Bikash', 'regex' => 'false'],
        ]));
        $searchRes->assertOk();
        $this->assertEquals(1, $searchRes->json('recordsFiltered'));
        $this->assertCount(1, $searchRes->json('data'));
        $this->assertStringContainsString('Bikash Roy', $searchRes->json('data.0.1'));
    }

    public function test_families_datatable_server_side_json_response(): void
    {
        Family::factory()->count(4)->create();

        $response = $this->actingAs($this->superAdmin)->getJson(route('families.index', [
            'draw' => 2,
            'start' => 0,
            'length' => 10,
        ]));

        $response->assertOk()
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);

        $json = $response->json();
        $this->assertEquals(2, $json['draw']);
        $this->assertEquals(4, $json['recordsTotal']);
        $this->assertEquals(4, $json['recordsFiltered']);
        $this->assertCount(4, $json['data']);
    }

    public function test_audit_logs_datatable_server_side_json_response(): void
    {
        AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'login',
            'description' => 'Super admin logged into dashboard',
            'ip_address' => '127.0.0.1',
        ]);
        AuditLog::create([
            'user_id' => $this->superAdmin->id,
            'action' => 'create_person',
            'description' => 'Created devotee Subrata',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->superAdmin)->getJson(route('admin.audit-logs', [
            'draw' => 3,
            'start' => 0,
            'length' => 10,
            'order' => [['column' => '0', 'dir' => 'desc']],
            'search' => ['value' => 'Subrata'],
        ]));

        $response->assertOk()
            ->assertJsonStructure([
                'draw',
                'recordsTotal',
                'recordsFiltered',
                'data',
            ]);

        $json = $response->json();
        $this->assertEquals(3, $json['draw']);
        $this->assertEquals(1, $json['recordsFiltered']);
        $this->assertCount(1, $json['data']);
        $this->assertStringContainsString('Subrata', $json['data'][0][4]);
    }

    public function test_audit_logs_page_renders_bootstrap_five_pagination(): void
    {
        for ($i = 0; $i < 30; $i++) {
            AuditLog::create([
                'user_id' => $this->superAdmin->id,
                'action' => "action_{$i}",
                'description' => "Test action description {$i}",
                'ip_address' => '127.0.0.1',
            ]);
        }

        $response = $this->actingAs($this->superAdmin)->get(route('admin.audit-logs'));
        $response->assertOk();
        $content = $response->getContent();

        // Must render Bootstrap 5 pagination markup
        $this->assertStringContainsString('pagination', $content);
        $this->assertStringContainsString('page-item', $content);
        $this->assertStringContainsString('page-link', $content);
        // Must NOT render unstyled Tailwind navigation
        $this->assertStringNotContainsString('flex items-center justify-between', $content);
    }

    public function test_datatables_and_select2_localization_parity(): void
    {
        // DataTables
        $enDatatables = trans('datatables', [], 'en');
        $bnDatatables = trans('datatables', [], 'bn');

        $this->assertIsArray($enDatatables);
        $this->assertIsArray($bnDatatables);
        $this->assertArrayHasKey('search', $enDatatables);
        $this->assertArrayHasKey('search', $bnDatatables);
        $this->assertArrayHasKey('zeroRecords', $enDatatables);
        $this->assertArrayHasKey('zeroRecords', $bnDatatables);

        // Select2
        $enSelect2 = trans('select2', [], 'en');
        $bnSelect2 = trans('select2', [], 'bn');

        $this->assertIsArray($enSelect2);
        $this->assertIsArray($bnSelect2);
        $this->assertArrayHasKey('no_results', $enSelect2);
        $this->assertArrayHasKey('no_results', $bnSelect2);
        $this->assertArrayHasKey('searching', $enSelect2);
        $this->assertArrayHasKey('searching', $bnSelect2);

        // Common loading & toastr keys
        $this->assertNotEmpty(__('common.loading', [], 'en'));
        $this->assertNotEmpty(__('common.loading', [], 'bn'));
        $this->assertNotEmpty(__('common.validation_error', [], 'en'));
        $this->assertNotEmpty(__('common.validation_error', [], 'bn'));
    }

    public function test_ui_assets_are_present(): void
    {
        $this->assertFileExists(public_path('assets/vendor/toastr/toastr.min.css'));
        $this->assertFileExists(public_path('assets/vendor/toastr/toastr.min.js'));
        $this->assertFileExists(public_path('assets/vendor/select2/select2.min.css'));
        $this->assertFileExists(public_path('assets/vendor/select2/select2.min.js'));
        $this->assertFileExists(public_path('assets/vendor/select2/select2-bootstrap-5-theme.min.css'));
        $this->assertFileExists(public_path('assets/vendor/datatables/jquery.dataTables.min.js'));
        $this->assertFileExists(public_path('assets/vendor/datatables/dataTables.bootstrap5.min.js'));
        $this->assertFileExists(public_path('assets/vendor/datatables/dataTables.responsive.min.js'));
        $this->assertFileExists(public_path('assets/vendor/datatables/responsive.bootstrap5.min.js'));
        $this->assertFileExists(public_path('assets/js/temple-ui.js'));
    }
}
