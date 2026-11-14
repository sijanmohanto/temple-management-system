<?php

namespace Tests\Feature\PublicPortal;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPortalHomeAndEventsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected EventCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);

        $superAdminRole = Role::where('slug', 'super_admin')->firstOrFail();

        $this->adminUser = User::factory()->create([
            'status' => 'active',
        ]);
        $this->adminUser->roles()->attach($superAdminRole);

        $this->category = EventCategory::first() ?? EventCategory::create([
            'name_en' => 'Durga Puja',
            'name_bn' => 'দুর্গাপূজা',
            'slug' => 'durga-puja',
            'status' => 'active',
        ]);
    }

    public function test_public_homepage_loads_without_authentication(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertGuest();
        $response->assertSee(config('app.name', 'Temple Management System'));
    }

    public function test_published_running_events_are_displayed(): void
    {
        $event = Event::create([
            'event_code' => 'EVT-2026-0001',
            'slug' => 'durga-puja-2026',
            'name_en' => 'Sharadiya Durga Puja 2026',
            'name_bn' => 'শারদীয় দুর্গাপূজা ২০২৬',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-20',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        // Check on Home page
        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('Sharadiya Durga Puja 2026');
        $homeResponse->assertSee(route('public.events.show', 'durga-puja-2026'));

        // Check on Events Listing page
        $eventsResponse = $this->get('/events');
        $eventsResponse->assertStatus(200);
        $eventsResponse->assertSee('Sharadiya Durga Puja 2026');
        $eventsResponse->assertSee(route('public.events.show', 'durga-puja-2026'));
    }

    public function test_published_upcoming_events_are_displayed(): void
    {
        $event = Event::create([
            'event_code' => 'EVT-2026-0002',
            'slug' => 'kali-puja-2026',
            'name_en' => 'Shyama Kali Puja 2026',
            'name_bn' => 'শ্যামা কালীপূজা ২০২৬',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-12',
            'status' => Event::STATUS_UPCOMING,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Shyama Kali Puja 2026');

        $eventsResponse = $this->get('/events');
        $eventsResponse->assertStatus(200);
        $eventsResponse->assertSee('Shyama Kali Puja 2026');
    }

    public function test_published_completed_events_remain_accessible(): void
    {
        $event = Event::create([
            'event_code' => 'EVT-2026-0003',
            'slug' => 'saraswati-puja-2026',
            'name_en' => 'Saraswati Puja 2026',
            'name_bn' => 'সরস্বতী পূজা ২০২৬',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'start_date' => '2026-02-14',
            'end_date' => '2026-02-15',
            'status' => Event::STATUS_CLOSED,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('Saraswati Puja 2026');

        $eventsResponse = $this->get('/events');
        $eventsResponse->assertStatus(200);
        $eventsResponse->assertSee('Saraswati Puja 2026');

        $detailsResponse = $this->get('/events/saraswati-puja-2026');
        $detailsResponse->assertStatus(200);
        $detailsResponse->assertSee('Saraswati Puja 2026');
    }

    public function test_draft_events_are_not_visible(): void
    {
        Event::create([
            'event_code' => 'EVT-2026-0004',
            'slug' => 'secret-draft-puja',
            'name_en' => 'Internal Draft Puja 2026',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'status' => Event::STATUS_DRAFT,
            'publication_status' => Event::PUB_STATUS_DRAFT,
            'created_by' => $this->adminUser->id,
        ]);

        $homeResponse = $this->get('/');
        $homeResponse->assertDontSee('Internal Draft Puja 2026');

        $eventsResponse = $this->get('/events');
        $eventsResponse->assertDontSee('Internal Draft Puja 2026');
    }

    public function test_unpublished_events_are_not_visible(): void
    {
        Event::create([
            'event_code' => 'EVT-2026-0005',
            'slug' => 'unpublished-active-puja',
            'name_en' => 'Unpublished Active Puja 2026',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-05',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_UNPUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        $homeResponse = $this->get('/');
        $homeResponse->assertDontSee('Unpublished Active Puja 2026');

        $eventsResponse = $this->get('/events');
        $eventsResponse->assertDontSee('Unpublished Active Puja 2026');
    }

    public function test_event_details_work_through_slug(): void
    {
        $event = Event::create([
            'event_code' => 'EVT-2026-0006',
            'slug' => 'janmashtami-celebration-2026',
            'name_en' => 'Sri Krishna Janmashtami 2026',
            'name_bn' => 'শ্রীকৃষ্ণ জন্মাষ্টমী ২০২৬',
            'description_en' => 'Sacred celebration of Sri Krishna birth anniversary with bhajans and prasad.',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'venue' => 'Main Temple Natmandir',
            'start_date' => '2026-08-25',
            'end_date' => '2026-08-26',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get('/events/janmashtami-celebration-2026');

        $response->assertStatus(200);
        $response->assertSee('Sri Krishna Janmashtami 2026');
        $response->assertSee('Main Temple Natmandir');
        $response->assertSee('Sacred celebration of Sri Krishna birth anniversary');
        $response->assertSee(route('public.events.index'));
    }

    public function test_invalid_or_nonexistent_slug_returns_404(): void
    {
        $response = $this->get('/events/non-existent-event-slug');
        $response->assertStatus(404);
    }

    public function test_unpublished_event_slug_returns_404(): void
    {
        Event::create([
            'event_code' => 'EVT-2026-0007',
            'slug' => 'confidential-event-slug',
            'name_en' => 'Confidential Event',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-21',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_UNPUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get('/events/confidential-event-slug');
        $response->assertStatus(404);
    }

    public function test_draft_event_slug_returns_404(): void
    {
        Event::create([
            'event_code' => 'EVT-2026-0008',
            'slug' => 'draft-puja-slug',
            'name_en' => 'Draft Puja',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'start_date' => '2026-11-25',
            'end_date' => '2026-11-26',
            'status' => Event::STATUS_DRAFT,
            'publication_status' => Event::PUB_STATUS_DRAFT,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get('/events/draft-puja-slug');
        $response->assertStatus(404);
    }

    public function test_public_routes_never_expose_database_ids(): void
    {
        $event = Event::create([
            'event_code' => 'EVT-2026-0009',
            'slug' => 'rath-yatra-2026',
            'name_en' => 'Sri Sri Jagannath Rath Yatra 2026',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'start_date' => '2026-07-07',
            'end_date' => '2026-07-15',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        $homeResponse = $this->get('/');
        // The URL must contain the slug, not the database ID
        $homeResponse->assertSee('/events/rath-yatra-2026');
        $homeResponse->assertDontSee('/events/'.$event->id);

        $eventsResponse = $this->get('/events');
        $eventsResponse->assertSee('/events/rath-yatra-2026');
        $eventsResponse->assertDontSee('/events/'.$event->id);

        // Attempting to query by database ID directly should return 404
        $idResponse = $this->get('/events/'.$event->id);
        $idResponse->assertStatus(404);
    }

    public function test_bengali_and_english_localization_work(): void
    {
        Event::create([
            'event_code' => 'EVT-2026-0010',
            'slug' => 'loknath-puja-2026',
            'name_en' => 'Baba Loknath Tirtha Utsav 2026',
            'name_bn' => 'বাবা লোকনাথ তিরোধান উৎসব ২০২৬',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'start_date' => '2026-06-03',
            'end_date' => '2026-06-03',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        // English check
        $enResponse = $this->withSession(['locale' => 'en'])->get('/events');
        $enResponse->assertStatus(200);
        $enResponse->assertSee('Baba Loknath Tirtha Utsav 2026');
        $enResponse->assertSee('Events &amp; Festivals', false);

        // Bengali check
        $bnResponse = $this->withSession(['locale' => 'bn'])->get('/events');
        $bnResponse->assertStatus(200);
        $bnResponse->assertSee('বাবা লোকনাথ তিরোধান উৎসব ২০২৬');
        $bnResponse->assertSee('অনুষ্ঠান ও পূজাসমূহ');
    }

    public function test_existing_admin_event_functionality_remains_unaffected(): void
    {
        // Unauthenticated guest cannot access admin event index
        $guestResponse = $this->get(route('events.index'));
        $guestResponse->assertRedirect(route('login'));

        // Authorized admin user can access admin event index
        $adminResponse = $this->actingAs($this->adminUser)->get(route('events.index'));
        $adminResponse->assertStatus(200);

        // Admin dashboard remains accessible
        $dashboardResponse = $this->actingAs($this->adminUser)->get(route('events.dashboard'));
        $dashboardResponse->assertStatus(200);
    }
}
