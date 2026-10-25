<?php

namespace Tests\Feature;

use App\Models\Classification;
use App\Models\Committee;
use App\Models\CommitteeMembership;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\ClassificationSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommitteeManagementTest extends TestCase
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

    public function test_guest_cannot_access_committees(): void
    {
        $response = $this->get(route('committees.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_cannot_manage_committees(): void
    {
        $response = $this->actingAs($this->devotee)->get(route('committees.create'));
        $response->assertForbidden();
    }

    public function test_admin_can_create_committee(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('committees.store'), [
            'name_en' => 'Executive Committee 2026-2028',
            'name_bn' => 'কার্যনির্বাহী পরিষদ ২০২৬-২০২৮',
            'status' => 'active',
            'term_start_date' => '2026-01-01',
            'term_end_date' => '2028-12-31',
        ]);

        $this->assertDatabaseHas('committees', [
            'name_en' => 'Executive Committee 2026-2028',
            'status' => 'active',
        ]);

        $committee = Committee::where('name_en', 'Executive Committee 2026-2028')->first();
        $response->assertRedirect(route('committees.show', $committee));
    }

    public function test_assign_existing_person_does_not_create_duplicate_person(): void
    {
        $committee = Committee::factory()->create();
        $person = Person::factory()->create(['name_en' => 'Dipankar Chatterjee']);
        $initialPersonCount = Person::count();

        $response = $this->actingAs($this->superAdmin)->post(route('committees.members.add', $committee), [
            'committee_id' => $committee->id,
            'person_id' => $person->id,
            'designation_en' => 'President',
            'designation_bn' => 'সভাপতি',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('committees.show', $committee));

        // Person count must remain unchanged
        $this->assertEquals($initialPersonCount, Person::count());

        // Membership record created
        $this->assertDatabaseHas('committee_memberships', [
            'committee_id' => $committee->id,
            'person_id' => $person->id,
            'designation_en' => 'President',
            'status' => 'active',
        ]);

        // Person automatically gains 'committee_member' classification
        $this->assertTrue($person->fresh()->hasClassification('committee_member'));
    }

    public function test_admin_can_update_member_position(): void
    {
        $committee = Committee::factory()->create();
        $person = Person::factory()->create();
        $membership = CommitteeMembership::factory()->create([
            'committee_id' => $committee->id,
            'person_id' => $person->id,
            'designation_en' => 'Member',
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('committees.members.update', [$committee, $membership]), [
            'designation_en' => 'Vice President',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('committees.show', $committee));
        $this->assertEquals('Vice President', $membership->fresh()->designation_en);
    }

    public function test_concluding_membership_preserves_historical_records(): void
    {
        $committee = Committee::factory()->create();
        $person = Person::factory()->create();
        $membership = CommitteeMembership::factory()->create([
            'committee_id' => $committee->id,
            'person_id' => $person->id,
            'designation_en' => 'Secretary',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('committees.members.end', [$committee, $membership]), [
            'end_date' => '2026-10-25',
            'status' => 'ended',
            'notes' => 'Completed term honorably',
        ]);

        $response->assertRedirect(route('committees.show', $committee));

        // Membership record is NOT deleted, but updated to ended status
        $this->assertEquals('ended', $membership->fresh()->status);
        $this->assertEquals('2026-10-25', $membership->fresh()->end_date?->toDateString());
        $this->assertEquals(1, $committee->memberships()->count());
        $this->assertEquals(0, $committee->activeMemberships()->count());
        $this->assertEquals(1, $committee->pastMemberships()->count());
    }

    public function test_person_can_participate_in_multiple_terms(): void
    {
        $execCommittee = Committee::factory()->create(['name_en' => 'Executive Committee']);
        $festivalCommittee = Committee::factory()->create(['name_en' => 'Festival Committee']);
        $person = Person::factory()->create(['name_en' => 'Subrata Majumder']);

        // First membership
        CommitteeMembership::factory()->create([
            'committee_id' => $execCommittee->id,
            'person_id' => $person->id,
            'designation_en' => 'General Secretary',
            'status' => 'active',
        ]);

        // Second membership (different committee)
        CommitteeMembership::factory()->create([
            'committee_id' => $festivalCommittee->id,
            'person_id' => $person->id,
            'designation_en' => 'Chief Coordinator',
            'status' => 'active',
        ]);

        $this->assertEquals(2, $person->committeeMemberships()->count());
        $this->assertEquals(1, Person::where('name_en', 'Subrata Majumder')->count());
    }
}
