<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\ClassificationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyManagementTest extends TestCase
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

    public function test_guest_cannot_access_families(): void
    {
        $response = $this->get(route('families.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_cannot_access_families(): void
    {
        $response = $this->actingAs($this->devotee)->get(route('families.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_families_list(): void
    {
        Family::factory()->count(2)->create();

        $response = $this->actingAs($this->superAdmin)->get(route('families.index'));
        $response->assertOk();
        $response->assertViewIs('families.index');
        $response->assertViewHas('families');
    }

    public function test_admin_can_create_family(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('families.store'), [
            'family_name' => 'Bhattacharya Family',
            'phone' => '01711223344',
            'area' => 'Uttara',
            'status' => 'active',
            'address' => 'House 12, Road 4',
        ]);

        $this->assertDatabaseHas('families', [
            'family_name' => 'Bhattacharya Family',
            'family_code' => 'FAM-0001',
        ]);

        $family = Family::where('family_name', 'Bhattacharya Family')->first();
        $response->assertRedirect(route('families.show', $family));
    }

    public function test_admin_can_assign_existing_person_to_family(): void
    {
        $family = Family::factory()->create();
        $person = Person::factory()->create(['family_id' => null]);

        $response = $this->actingAs($this->superAdmin)->post(route('families.members.add', $family), [
            'person_id' => $person->id,
            'family_relationship' => 'head',
        ]);

        $response->assertRedirect(route('families.show', $family));

        $this->assertEquals($family->id, $person->fresh()->family_id);
        $this->assertEquals('head', $person->fresh()->family_relationship);
        $this->assertEquals($person->id, $family->fresh()->primary_contact_person_id);
    }

    public function test_admin_can_remove_member_from_family(): void
    {
        $family = Family::factory()->create();
        $person = Person::factory()->create(['family_id' => $family->id]);

        $response = $this->actingAs($this->superAdmin)->delete(route('families.members.remove', [$family, $person]));
        $response->assertRedirect(route('families.show', $family));

        $this->assertNull($person->fresh()->family_id);
    }

    public function test_cannot_delete_family_with_existing_members(): void
    {
        $family = Family::factory()->create();
        Person::factory()->create(['family_id' => $family->id]);

        $response = $this->actingAs($this->superAdmin)->delete(route('families.destroy', $family));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('families', ['id' => $family->id]);
    }

    public function test_can_delete_empty_family(): void
    {
        $family = Family::factory()->create();

        $response = $this->actingAs($this->superAdmin)->delete(route('families.destroy', $family));

        $response->assertRedirect(route('families.index'));
        $this->assertDatabaseMissing('families', ['id' => $family->id]);
    }
}
