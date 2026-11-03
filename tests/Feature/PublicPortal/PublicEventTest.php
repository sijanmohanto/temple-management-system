<?php

namespace Tests\Feature\PublicPortal;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicEventTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $templeAdmin;

    protected User $treasurer;

    protected User $devotee;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);

        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $templeAdminRole = Role::where('slug', 'temple_admin')->first();
        $treasurerRole = Role::where('slug', 'treasurer')->first();
        $devoteeRole = Role::where('slug', 'devotee')->first();

        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->roles()->attach($superAdminRole);

        $this->templeAdmin = User::factory()->create(['status' => 'active']);
        $this->templeAdmin->roles()->attach($templeAdminRole);

        $this->treasurer = User::factory()->create(['status' => 'active']);
        $this->treasurer->roles()->attach($treasurerRole);

        $this->devotee = User::factory()->create(['status' => 'active']);
        $this->devotee->roles()->attach($devoteeRole);

        $category = EventCategory::first();

        $this->event = Event::create([
            'event_code' => 'EVT-2026-TEST',
            'slug' => 'durga-puja-2026',
            'name_en' => 'Durga Puja 2026',
            'name_bn' => 'দুর্গাপূজা ২০২৬',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'venue' => 'Main Temple Hall',
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-20',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_DRAFT,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    public function test_guest_cannot_access_public_event_admin_routes(): void
    {
        $this->get(route('portal.events.index'))->assertRedirect(route('login'));
        $this->get(route('portal.events.show', $this->event))->assertRedirect(route('login'));
        $this->get(route('portal.events.edit', $this->event))->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_cannot_access_public_event_admin(): void
    {
        $this->actingAs($this->devotee)
            ->get(route('portal.events.index'))
            ->assertForbidden();

        $this->actingAs($this->devotee)
            ->get(route('portal.events.show', $this->event))
            ->assertForbidden();

        $this->actingAs($this->devotee)
            ->get(route('portal.events.edit', $this->event))
            ->assertForbidden();
    }

    public function test_authorized_user_can_view_public_events_index_and_show(): void
    {
        $response = $this->actingAs($this->templeAdmin)
            ->get(route('portal.events.index'));

        $response->assertOk();
        $response->assertSee('Public Events');
        $response->assertSee('publicEventsTable');

        $showResponse = $this->actingAs($this->templeAdmin)
            ->get(route('portal.events.show', $this->event));

        $showResponse->assertOk();
        $showResponse->assertSee($this->event->name_en);
        $showResponse->assertSee('durga-puja-2026');
    }

    public function test_datatable_server_side_response_strictly_has_no_row_number_column(): void
    {
        $response = $this->actingAs($this->templeAdmin)
            ->getJson(route('portal.events.index'), [
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ]);

        $response->assertOk();
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $firstRow = $data[0];
        $this->assertArrayNotHasKey('#', $firstRow);
        $this->assertArrayNotHasKey('sl', $firstRow);
        $this->assertArrayNotHasKey('row_number', $firstRow);
        $this->assertArrayHasKey('event_details', $firstRow);
        $this->assertArrayHasKey('slug', $firstRow);
        $this->assertArrayHasKey('category_year', $firstRow);
        $this->assertArrayHasKey('dates', $firstRow);
        $this->assertArrayHasKey('event_status', $firstRow);
        $this->assertArrayHasKey('publication_status', $firstRow);
        $this->assertArrayHasKey('actions', $firstRow);
    }

    public function test_admin_can_update_event_public_metadata_with_banner(): void
    {
        Storage::fake('public');

        $banner = UploadedFile::fake()->image('banner.jpg', 1200, 600);

        $response = $this->actingAs($this->templeAdmin)
            ->put(route('portal.events.update', $this->event), [
                'slug' => 'maha-durga-puja-2026',
                'short_description_en' => 'Grand annual celebration of Durga Puja with sacred bhog and arati.',
                'short_description_bn' => 'পবিত্র ভোগ ও আরতি সহযোগে মহা দুর্গাপূজার সার্বজনীন আয়োজন।',
                'banner_image' => $banner,
            ]);

        $response->assertRedirect(route('portal.events.show', $this->event));
        $response->assertSessionHas('success');

        $this->event->refresh();
        $this->assertEquals('maha-durga-puja-2026', $this->event->slug);
        $this->assertEquals('Grand annual celebration of Durga Puja with sacred bhog and arati.', $this->event->short_description_en);
        $this->assertNotNull($this->event->banner_image_path);
        Storage::disk('public')->assertExists($this->event->banner_image_path);
    }

    public function test_slug_validation_enforces_format_and_uniqueness(): void
    {
        // 1. Invalid characters in slug
        $response = $this->actingAs($this->templeAdmin)
            ->put(route('portal.events.update', $this->event), [
                'slug' => 'Invalid Slug With Spaces!',
            ]);
        $response->assertSessionHasErrors('slug');

        // 2. Duplicate slug with another event
        $category = EventCategory::first();
        Event::create([
            'event_code' => 'EVT-2026-OTHER',
            'slug' => 'existing-other-slug',
            'name_en' => 'Other Event',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
            'status' => Event::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $dupResponse = $this->actingAs($this->templeAdmin)
            ->put(route('portal.events.update', $this->event), [
                'slug' => 'existing-other-slug',
            ]);
        $dupResponse->assertSessionHasErrors('slug');
    }

    public function test_banner_validation_rejects_non_image_or_oversized_files(): void
    {
        Storage::fake('public');

        // Text file instead of image
        $fakeTextFile = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->templeAdmin)
            ->put(route('portal.events.update', $this->event), [
                'slug' => $this->event->slug,
                'banner_image' => $fakeTextFile,
            ]);

        $response->assertSessionHasErrors('banner_image');

        // Oversized image (> 3MB)
        $oversizedImage = UploadedFile::fake()->image('huge.jpg')->size(4000); // 4MB

        $response2 = $this->actingAs($this->templeAdmin)
            ->put(route('portal.events.update', $this->event), [
                'slug' => $this->event->slug,
                'banner_image' => $oversizedImage,
            ]);

        $response2->assertSessionHasErrors('banner_image');
    }

    public function test_authorized_user_can_publish_and_unpublish_event(): void
    {
        // 1. Publish event
        $response = $this->actingAs($this->templeAdmin)
            ->post(route('portal.events.publish', $this->event));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->event->refresh();
        $this->assertEquals(Event::PUB_STATUS_PUBLISHED, $this->event->publication_status);
        $this->assertTrue($this->event->isPublished());
        $this->assertNotNull($this->event->published_at);
        $this->assertEquals($this->templeAdmin->id, $this->event->published_by);

        // 2. Unpublish event
        $response2 = $this->actingAs($this->templeAdmin)
            ->post(route('portal.events.unpublish', $this->event));

        $response2->assertRedirect();
        $response2->assertSessionHas('success');

        $this->event->refresh();
        $this->assertEquals(Event::PUB_STATUS_UNPUBLISHED, $this->event->publication_status);
        $this->assertTrue($this->event->isUnpublished());
        $this->assertNotNull($this->event->unpublished_at);
        $this->assertEquals($this->templeAdmin->id, $this->event->unpublished_by);
    }

    public function test_user_without_publish_permission_cannot_publish(): void
    {
        // Treasurer only has view/manage permissions, not publish
        $response = $this->actingAs($this->treasurer)
            ->post(route('portal.events.publish', $this->event));

        $response->assertForbidden();
    }

    public function test_publishing_does_not_modify_financial_or_liturgical_event_status(): void
    {
        // Initial liturgical status
        $originalStatus = $this->event->status;
        $this->assertEquals(Event::STATUS_ACTIVE, $originalStatus);

        // Publish event
        $this->actingAs($this->superAdmin)
            ->post(route('portal.events.publish', $this->event));

        $this->event->refresh();

        // Invariant check: status is untouched
        $this->assertEquals(Event::STATUS_ACTIVE, $this->event->status);
        $this->assertNull($this->event->closed_at);
        $this->assertNull($this->event->closed_by);
        $this->assertEquals(Event::PUB_STATUS_PUBLISHED, $this->event->publication_status);
    }

    public function test_bengali_locale_renders_cleanly_without_missing_keys(): void
    {
        app()->setLocale('bn');

        $response = $this->actingAs($this->templeAdmin)
            ->get(route('portal.events.index'));

        $response->assertOk();
        $response->assertSee('পাবলিক অনুষ্ঠানসমূহ');
        $response->assertDontSee('portal.');
    }

    public function test_event_language_sanitization_and_no_raw_translation_keys(): void
    {
        foreach (['en', 'bn'] as $locale) {
            app()->setLocale($locale);

            // Index page
            $response = $this->actingAs($this->templeAdmin)->get(route('portal.events.index'));
            $response->assertOk();
            $response->assertDontSee('events.event_name');
            $response->assertDontSee('events.dates');
            $response->assertDontSee('events.date');

            // Show page
            $response = $this->actingAs($this->templeAdmin)->get(route('portal.events.show', $this->event));
            $response->assertOk();
            $response->assertDontSee('events.event_name');
            $response->assertDontSee('events.dates');
            $response->assertDontSee('events.event_duration');
            $response->assertDontSee('events.view_details');

            // Edit page
            $response = $this->actingAs($this->templeAdmin)->get(route('portal.events.edit', $this->event));
            $response->assertOk();
            $response->assertDontSee('events.dates');
            $response->assertDontSee('events.event_name');
        }
    }
}
