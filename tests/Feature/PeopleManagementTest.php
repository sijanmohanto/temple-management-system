<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\ClassificationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleManagementTest extends TestCase
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

    public function test_guest_cannot_access_people_directory(): void
    {
        $response = $this->get(route('people.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_cannot_access_people_directory(): void
    {
        $response = $this->actingAs($this->devotee)->get(route('people.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_people_list(): void
    {
        Person::factory()->count(3)->create();

        $response = $this->actingAs($this->superAdmin)->get(route('people.index'));

        $response->assertOk();
        $response->assertViewIs('people.index');
        $response->assertViewHas('people');
    }

    public function test_admin_can_filter_people_by_search_and_classification(): void
    {
        $resident = Classification::where('slug', 'resident')->first();
        $donor = Classification::where('slug', 'donor')->first();

        $p1 = Person::factory()->create(['name_en' => 'Joydip Mukherjee']);
        $p1->assignClassification($resident);

        $p2 = Person::factory()->create(['name_en' => 'Shyamal Sen']);
        $p2->assignClassification($donor);

        // Filter by name
        $response = $this->actingAs($this->superAdmin)->get(route('people.index', ['search' => 'Joydip']));
        $response->assertOk();
        $response->assertSee('Joydip Mukherjee');
        $response->assertDontSee('Shyamal Sen');

        // Filter by classification
        $responseClass = $this->actingAs($this->superAdmin)->get(route('people.index', ['classification' => 'donor']));
        $responseClass->assertOk();
        $responseClass->assertSee('Shyamal Sen');
        $responseClass->assertDontSee('Joydip Mukherjee');
    }

    public function test_admin_can_create_person_with_auto_generated_code(): void
    {
        $resident = Classification::where('slug', 'resident')->first();

        $response = $this->actingAs($this->superAdmin)->post(route('people.store'), [
            'name_en' => 'Pranab Ghosh',
            'name_bn' => 'প্রণব ঘোষ',
            'phone' => '01715555555',
            'email' => 'pranab@temple.local',
            'status' => 'active',
            'gotra' => 'Kashyapa',
            'classifications' => [$resident->id],
        ]);

        $this->assertDatabaseHas('people', [
            'name_en' => 'Pranab Ghosh',
            'phone' => '01715555555',
            'person_code' => 'DEV-00001',
        ]);

        $person = Person::where('phone', '01715555555')->first();
        $this->assertTrue($person->hasClassification('resident'));
        $response->assertRedirect(route('people.show', $person));
    }

    public function test_duplicate_warning_returned_when_matching_profile_exists(): void
    {
        Person::factory()->create([
            'name_en' => 'Subrata Majumder',
            'phone' => '01712000222',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->from(route('people.create'))
            ->post(route('people.store'), [
                'name_en' => 'Subrata Majumder',
                'phone' => '01712000222',
                'status' => 'active',
            ]);

        // Should redirect back to create form with duplicate warning
        $response->assertRedirect(route('people.create'));
        $response->assertSessionHas('warning_duplicates');
        $this->assertEquals(1, Person::count());
    }

    public function test_user_can_proceed_with_registration_when_confirming_distinct_person(): void
    {
        Person::factory()->create([
            'name_en' => 'Subrata Majumder',
            'phone' => '01712000222',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('people.store'), [
            'name_en' => 'Subrata Majumder Jr.',
            'phone' => '01712000222',
            'status' => 'active',
            'confirm_duplicate' => 1,
        ]);

        $this->assertEquals(2, Person::count());
        $response->assertSessionMissing('warning_duplicates');
    }

    public function test_admin_can_update_person_profile(): void
    {
        $person = Person::factory()->create([
            'name_en' => 'Old Name',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('people.update', $person), [
            'name_en' => 'Updated Name',
            'status' => 'active',
            'gotra' => 'Shandilya',
        ]);

        $response->assertRedirect(route('people.show', $person));
        $this->assertDatabaseHas('people', [
            'id' => $person->id,
            'name_en' => 'Updated Name',
            'gotra' => 'Shandilya',
        ]);
    }

    public function test_admin_can_toggle_person_active_status(): void
    {
        $person = Person::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->superAdmin)->post(route('people.toggle-status', $person));
        $response->assertRedirect();

        $this->assertEquals('inactive', $person->fresh()->status);

        // Reactivate
        $this->actingAs($this->superAdmin)->post(route('people.toggle-status', $person));
        $this->assertEquals('active', $person->fresh()->status);
    }

    public function test_person_can_have_multiple_classifications_without_duplicates(): void
    {
        $person = Person::factory()->create();
        $resident = Classification::where('slug', 'resident')->first();
        $volunteer = Classification::where('slug', 'volunteer')->first();

        $person->assignClassification($resident);
        $person->assignClassification($volunteer);

        $this->assertEquals(2, $person->classifications()->count());

        // Assign resident again - should not duplicate
        $person->assignClassification($resident);
        $this->assertEquals(2, $person->classifications()->count());
    }

    public function test_private_notes_are_protected_and_only_visible_to_authorized_users(): void
    {
        $person = Person::factory()->create([
            'public_notes' => 'Open public comment',
            'private_notes' => 'Secret administrative background remark',
        ]);

        // Super Admin can view private notes
        $responseAdmin = $this->actingAs($this->superAdmin)->get(route('people.show', $person));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Secret administrative background remark');

        // Create user with only 'view_people' permission (e.g. committee member)
        $committeeUser = User::factory()->create();
        $committeeUser->assignRole('committee_member');

        $responseCommittee = $this->actingAs($committeeUser)->get(route('people.show', $person));
        $responseCommittee->assertOk();
        $responseCommittee->assertSee('Open public comment');
        $responseCommittee->assertDontSee('Secret administrative background remark');
    }
}
