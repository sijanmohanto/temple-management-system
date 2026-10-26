<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventLifecycleLog;
use App\Models\User;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $templeAdmin;

    protected User $devotee;

    protected EventCategory $durgaPujaCategory;

    protected EventCategory $kaliPujaCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->templeAdmin = User::factory()->create();
        $this->templeAdmin->assignRole('temple_admin');

        $this->devotee = User::factory()->create();
        $this->devotee->assignRole('devotee');

        $this->durgaPujaCategory = EventCategory::where('slug', 'durga-puja')->firstOrFail();
        $this->kaliPujaCategory = EventCategory::where('slug', 'kali-puja')->firstOrFail();
    }

    public function test_guest_cannot_access_events_dashboard_or_list(): void
    {
        $response = $this->get(route('events.dashboard'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('events.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_events(): void
    {
        $unauthorizedUser = User::factory()->create();

        $response = $this->actingAs($unauthorizedUser)->get(route('events.dashboard'));
        $response->assertForbidden();

        $response = $this->actingAs($unauthorizedUser)->get(route('events.index'));
        $response->assertForbidden();

        // Devotee has view_events, but lacks manage_events
        $createResponse = $this->actingAs($this->devotee)->get(route('events.create'));
        $createResponse->assertForbidden();

        $storeResponse = $this->actingAs($this->devotee)->post(route('events.store'), []);
        $storeResponse->assertForbidden();
    }

    public function test_authorized_user_can_view_events_dashboard_and_list(): void
    {
        $response = $this->actingAs($this->templeAdmin)->get(route('events.dashboard'));
        $response->assertOk();
        $response->assertViewIs('events.dashboard');
        $response->assertViewHas(['metrics', 'activeEvents', 'recentLogs']);

        $response = $this->actingAs($this->templeAdmin)->get(route('events.index'));
        $response->assertOk();
        $response->assertViewIs('events.index');
        $response->assertViewHas(['events', 'categories', 'years']);
    }

    public function test_authorized_user_can_create_event_with_independent_collection_period(): void
    {
        $eventData = [
            'name_en' => 'Grand Durga Puja 2027',
            'name_bn' => 'মহতি দুর্গাপূজা ১৪৩৪',
            'event_category_id' => $this->durgaPujaCategory->id,
            'event_year' => 2027,
            'venue' => 'Main Temple Courtyard',
            'start_date' => '2027-10-10',
            'end_date' => '2027-10-14',
            'collection_start_date' => '2026-12-01', // Preceding year collection window!
            'collection_end_date' => '2027-10-31',
            'status' => 'upcoming',
            'description_en' => 'Annual grand Sharadiya festival.',
            'description_bn' => 'বাৎসরিক শারদীয় মহোৎসব।',
        ];

        $response = $this->actingAs($this->templeAdmin)->post(route('events.store'), $eventData);

        $response->assertRedirect();
        $this->assertDatabaseHas('events', [
            'name_en' => 'Grand Durga Puja 2027',
            'event_code' => 'EVT-2027-0001',
            'event_year' => 2027,
            'status' => 'upcoming',
        ]);

        $event = Event::where('event_code', 'EVT-2027-0001')->firstOrFail();
        $this->assertDatabaseHas('event_lifecycle_logs', [
            'event_id' => $event->id,
            'action' => 'created',
            'to_status' => 'upcoming',
        ]);
    }

    public function test_event_code_increments_per_year_independently(): void
    {
        $code1 = Event::generateEventCode(2027);
        $this->assertEquals('EVT-2027-0001', $code1);

        Event::create([
            'event_code' => $code1,
            'name_en' => 'Event 1 2027',
            'event_category_id' => $this->durgaPujaCategory->id,
            'event_year' => 2027,
            'start_date' => '2027-01-01',
            'end_date' => '2027-01-02',
            'status' => 'draft',
            'created_by' => $this->superAdmin->id,
        ]);

        $code2 = Event::generateEventCode(2027);
        $this->assertEquals('EVT-2027-0002', $code2);

        $codeOtherYear = Event::generateEventCode(2028);
        $this->assertEquals('EVT-2028-0001', $codeOtherYear);
    }

    public function test_system_supports_multiple_concurrent_active_events(): void
    {
        $evt1 = Event::create([
            'event_code' => 'EVT-2026-0001',
            'name_en' => 'Active Puja 1',
            'event_category_id' => $this->durgaPujaCategory->id,
            'event_year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $evt2 = Event::create([
            'event_code' => 'EVT-2026-0002',
            'name_en' => 'Active Puja 2',
            'event_category_id' => $this->kaliPujaCategory->id,
            'event_year' => 2026,
            'start_date' => '2026-10-02',
            'end_date' => '2026-10-06',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->assertEquals(2, Event::where('status', 'active')->count());
        $this->assertTrue($evt1->isActive());
        $this->assertTrue($evt2->isActive());
    }

    public function test_event_lifecycle_transitions(): void
    {
        $event = Event::create([
            'event_code' => 'EVT-2026-0003',
            'name_en' => 'Lifecycle Test Event',
            'event_category_id' => $this->durgaPujaCategory->id,
            'event_year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'status' => 'draft',
            'created_by' => $this->superAdmin->id,
        ]);

        // Draft -> Upcoming
        $response = $this->actingAs($this->templeAdmin)->post(route('events.transition', $event), [
            'target_status' => 'upcoming',
            'reason' => 'Preparation announced',
        ]);
        $response->assertRedirect();
        $this->assertEquals('upcoming', $event->fresh()->status);

        // Upcoming -> Active
        $response = $this->actingAs($this->templeAdmin)->post(route('events.transition', $event), [
            'target_status' => 'active',
            'reason' => 'Puja started',
        ]);
        $response->assertRedirect();
        $this->assertEquals('active', $event->fresh()->status);

        // Active -> Closing
        $response = $this->actingAs($this->templeAdmin)->post(route('events.transition', $event), [
            'target_status' => 'closing',
            'reason' => 'Immersion completed',
        ]);
        $response->assertRedirect();
        $this->assertEquals('closing', $event->fresh()->status);

        $this->assertDatabaseHas('event_lifecycle_logs', [
            'event_id' => $event->id,
            'action' => 'status_changed',
            'from_status' => 'active',
            'to_status' => 'closing',
        ]);
    }

    public function test_closing_mode_a_close_and_lock_blocks_ordinary_and_post_closing_modifications(): void
    {
        $event = Event::create([
            'event_code' => 'EVT-2026-0004',
            'name_en' => 'Mode A Event',
            'event_category_id' => $this->durgaPujaCategory->id,
            'event_year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        // Close under Mode A
        $response = $this->actingAs($this->templeAdmin)->post(route('events.close', $event), [
            'closing_mode' => 'close_and_lock',
            'closing_reason' => 'All rituals and reconciliation finalized. Fully locked.',
        ]);

        $response->assertRedirect(route('events.show', $event));
        $fresh = $event->fresh();
        $this->assertEquals('closed', $fresh->status);
        $this->assertEquals('close_and_lock', $fresh->closing_mode);
        $this->assertTrue($fresh->isLocked());
        $this->assertFalse($fresh->allowsAuthorizedAdjustments());

        // Attempting normal CRUD update is forbidden
        $updateResponse = $this->actingAs($this->templeAdmin)->put(route('events.update', $event), [
            'name_en' => 'Hacked Name',
            'event_category_id' => $this->durgaPujaCategory->id,
            'event_year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'status' => 'active',
        ]);
        $updateResponse->assertForbidden();

        // Attempting post-closing adjustment on Mode A is forbidden
        $adjustResponse = $this->actingAs($this->templeAdmin)->post(route('events.adjust', $event), [
            'adjustment_reason' => 'Trying to adjust locked event',
            'venue' => 'New Venue',
        ]);
        $adjustResponse->assertForbidden();
    }

    public function test_closing_mode_b_permits_authorized_adjustments_with_reason_and_diff_logging(): void
    {
        $event = Event::create([
            'event_code' => 'EVT-2026-0005',
            'name_en' => 'Mode B Event',
            'description_en' => 'Original description',
            'venue' => 'Courtyard A',
            'event_category_id' => $this->durgaPujaCategory->id,
            'event_year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        // Close under Mode B
        $response = $this->actingAs($this->templeAdmin)->post(route('events.close', $event), [
            'closing_mode' => 'close_with_authorized_adjustments',
            'closing_reason' => 'Event ended but reports and historical description will be finalized soon.',
        ]);

        $response->assertRedirect(route('events.show', $event));
        $fresh = $event->fresh();
        $this->assertEquals('closed', $fresh->status);
        $this->assertEquals('close_with_authorized_adjustments', $fresh->closing_mode);
        $this->assertFalse($fresh->isLocked());
        $this->assertTrue($fresh->allowsAuthorizedAdjustments());

        // Normal CRUD update is still forbidden
        $updateResponse = $this->actingAs($this->templeAdmin)->put(route('events.update', $event), [
            'name_en' => 'Should Not Work',
            'event_category_id' => $this->durgaPujaCategory->id,
            'event_year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'status' => 'active',
        ]);
        $updateResponse->assertForbidden();

        // Authorized post-closing adjustment succeeds
        $adjustResponse = $this->actingAs($this->templeAdmin)->post(route('events.adjust', $event), [
            'adjustment_reason' => 'Correcting final venue documentation and adding historical narrative.',
            'venue' => 'Courtyard A & Natmandir Hall',
            'description_en' => 'Final detailed commemorative description of celebrations.',
        ]);

        $adjustResponse->assertRedirect(route('events.show', $event));
        $adjusted = $event->fresh();
        $this->assertEquals('Courtyard A & Natmandir Hall', $adjusted->venue);
        $this->assertEquals('Final detailed commemorative description of celebrations.', $adjusted->description_en);

        // Verify lifecycle log records diff
        $log = EventLifecycleLog::where('event_id', $event->id)
            ->where('action', 'post_close_adjusted')
            ->firstOrFail();

        $this->assertArrayHasKey('venue', $log->changes);
        $this->assertEquals('Courtyard A', $log->changes['venue']['old']);
        $this->assertEquals('Courtyard A & Natmandir Hall', $log->changes['venue']['new']);
    }

    public function test_privileged_reopening_preserves_historical_closing_details(): void
    {
        $event = Event::create([
            'event_code' => 'EVT-2026-0006',
            'name_en' => 'Reopen Test Event',
            'event_category_id' => $this->durgaPujaCategory->id,
            'event_year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'status' => 'closed',
            'closing_mode' => 'close_and_lock',
            'closing_reason' => 'Previously fully locked upon completion',
            'closed_by' => $this->superAdmin->id,
            'closed_at' => now()->subDays(5),
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('events.reopen', $event), [
            'target_status' => 'active',
            'reopen_reason' => 'Executive committee requested supplementary devotional ceremony.',
        ]);

        $response->assertRedirect(route('events.show', $event));
        $fresh = $event->fresh();
        $this->assertEquals('active', $fresh->status);
        $this->assertNull($fresh->closing_mode);
        $this->assertNull($fresh->closing_reason);
        $this->assertEquals($this->superAdmin->id, $fresh->reopened_by);
        $this->assertNotNull($fresh->reopened_at);

        // Historical closing details preserved in lifecycle log
        $reopenLog = EventLifecycleLog::where('event_id', $event->id)
            ->where('action', 'reopened')
            ->firstOrFail();

        $this->assertEquals('Executive committee requested supplementary devotional ceremony.', $reopenLog->reason);
        $this->assertEquals('close_and_lock', $reopenLog->changes['previous_closing_mode']);
        $this->assertEquals('Previously fully locked upon completion', $reopenLog->changes['previous_closing_reason']);
    }

    public function test_draft_event_can_be_deleted_but_non_draft_cannot(): void
    {
        $draftEvent = Event::create([
            'event_code' => 'EVT-2026-0007',
            'name_en' => 'Draft Event',
            'event_category_id' => $this->durgaPujaCategory->id,
            'event_year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'status' => 'draft',
            'created_by' => $this->superAdmin->id,
        ]);

        $activeEvent = Event::create([
            'event_code' => 'EVT-2026-0008',
            'name_en' => 'Active Event',
            'event_category_id' => $this->durgaPujaCategory->id,
            'event_year' => 2026,
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-05',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        // Active cannot be deleted
        $response = $this->actingAs($this->templeAdmin)->delete(route('events.destroy', $activeEvent));
        $response->assertRedirect(route('events.show', $activeEvent));
        $this->assertDatabaseHas('events', ['id' => $activeEvent->id]);

        // Draft can be deleted
        $deleteDraftResponse = $this->actingAs($this->templeAdmin)->delete(route('events.destroy', $draftEvent));
        $deleteDraftResponse->assertRedirect(route('events.index'));
        $this->assertSoftDeleted('events', ['id' => $draftEvent->id]);
    }

    public function test_event_category_management_and_safe_deletion(): void
    {
        // Create custom category
        $createResponse = $this->actingAs($this->templeAdmin)->post(route('events.categories.store'), [
            'name_en' => 'Janmashtami Festival',
            'name_bn' => 'শ্রীকৃষ্ণ জন্মাষ্টমী',
            'slug' => 'janmashtami-festival',
            'status' => 'active',
        ]);
        $createResponse->assertRedirect();
        $this->assertDatabaseHas('event_categories', ['slug' => 'janmashtami-festival']);

        $category = EventCategory::where('slug', 'janmashtami-festival')->firstOrFail();

        // Assign event to category
        $event = Event::create([
            'event_code' => 'EVT-2026-0009',
            'name_en' => 'Janmashtami 2026',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-16',
            'status' => 'draft',
            'created_by' => $this->superAdmin->id,
        ]);

        // Deletion must be prevented because event exists
        $this->assertFalse($category->canBeSafelyDeleted());
        $delResponse = $this->actingAs($this->templeAdmin)->delete(route('events.categories.destroy', $category));
        $delResponse->assertRedirect();
        $this->assertDatabaseHas('event_categories', ['id' => $category->id]);

        // Remove event and delete category safely
        $event->forceDelete();
        $this->assertTrue($category->fresh()->canBeSafelyDeleted());

        $delResponseSuccess = $this->actingAs($this->templeAdmin)->delete(route('events.categories.destroy', $category));
        $delResponseSuccess->assertRedirect();
        $this->assertDatabaseMissing('event_categories', ['id' => $category->id]);
    }

    public function test_datatable_server_side_json_response_for_events(): void
    {
        Event::create([
            'event_code' => 'EVT-2026-0010',
            'name_en' => 'Searchable Maha Shivaratri',
            'event_category_id' => $this->durgaPujaCategory->id,
            'event_year' => 2026,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-02',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->templeAdmin)->getJson(route('events.index', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'Shivaratri'],
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data',
        ]);

        $json = $response->json();
        $this->assertEquals(1, $json['draw']);
        $this->assertGreaterThanOrEqual(1, $json['recordsFiltered']);
        $this->assertStringContainsString('Shivaratri', json_encode($json['data']));
    }
}
