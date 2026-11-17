<?php

namespace Tests\Feature\PublicPortal;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\Role;
use App\Models\User;
use App\Services\CollectionPaymentService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventDonorSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected EventCategory $category;

    protected Event $event;

    protected Person $publicDevotee;

    protected Person $privateDevotee;

    protected CollectionPaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $superAdminRole = Role::where('slug', 'super_admin')->firstOrFail();
        $this->adminUser = User::factory()->create(['status' => 'active']);
        $this->adminUser->roles()->attach($superAdminRole);

        $this->paymentService = app(CollectionPaymentService::class);
        $this->category = EventCategory::first();

        $this->event = Event::create([
            'event_code' => 'EVT-2026-SEARCH',
            'slug' => 'durga-puja-2026-search',
            'name_en' => 'Durga Puja 2026',
            'name_bn' => 'দুর্গাপূজা ২০২৬',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'venue' => 'Main Temple',
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-20',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'published_at' => now(),
            'created_by' => $this->adminUser->id,
        ]);

        // Public Devotee
        $this->publicDevotee = Person::create([
            'person_code' => 'DEV-001',
            'name_en' => 'Subhashish Roy',
            'name_bn' => 'শুভাশীষ রায়',
            'phone' => '01711100001',
            'email' => 'subhashish@example.com',
            'status' => 'active',
            'visibility' => Person::VISIBILITY_PUBLIC,
            'created_by' => $this->adminUser->id,
        ]);

        // Private Devotee
        $this->privateDevotee = Person::create([
            'person_code' => 'DEV-002',
            'name_en' => 'Ananya Sen',
            'name_bn' => 'অনন্যা সেন',
            'phone' => '01811200002',
            'email' => 'ananya@example.com',
            'status' => 'active',
            'visibility' => Person::VISIBILITY_PRIVATE,
            'created_by' => $this->adminUser->id,
        ]);

        // Create Pledge for Public Devotee
        Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->publicDevotee->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-08-01',
            'status' => Pledge::STATUS_UNPAID,
            'visibility' => 'public',
            'created_by' => $this->adminUser->id,
        ]);

        // Create Pledge for Private Devotee
        Pledge::create([
            'pledge_code' => 'PLG-2026-0002',
            'event_id' => $this->event->id,
            'person_id' => $this->privateDevotee->id,
            'pledge_year' => 2026,
            'pledged_amount' => 15000.00,
            'pledged_on' => '2026-08-05',
            'status' => Pledge::STATUS_UNPAID,
            'visibility' => 'private',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_can_search_event_donor_by_english_name(): void
    {
        $response = $this->getJson(route('public.events.donors.search', [
            'slug' => $this->event->slug,
            'q' => 'Subhashish',
        ]));

        $response->assertOk()
            ->assertJsonPath('results.0.code', 'PLG-2026-0001')
            ->assertJsonPath('results.0.name', 'Subhashish Roy')
            ->assertJsonPath('results.0.phone', '01711100001');

        $this->assertStringNotContainsString('PLG-', $response->json('results.0.text'));
    }

    public function test_search_results_do_not_contain_plg_number_in_text(): void
    {
        $response = $this->getJson(route('public.events.donors.search', [
            'slug' => $this->event->slug,
            'q' => 'Subhashish',
        ]));

        $response->assertOk();
        $text = $response->json('results.0.text');
        $this->assertStringNotContainsString('PLG-', $text);
        $this->assertEquals('Subhashish Roy - 01711100001', $text);
    }

    public function test_can_search_event_donor_by_bengali_name(): void
    {
        $response = $this->getJson(route('public.events.donors.search', [
            'slug' => $this->event->slug,
            'q' => 'শুভাশীষ',
        ]));

        $response->assertOk()
            ->assertJsonPath('results.0.code', 'PLG-2026-0001');
    }

    public function test_can_search_event_donor_by_mobile_number(): void
    {
        $response = $this->getJson(route('public.events.donors.search', [
            'slug' => $this->event->slug,
            'q' => '01711100001',
        ]));

        $response->assertOk()
            ->assertJsonPath('results.0.code', 'PLG-2026-0001')
            ->assertJsonPath('results.0.phone', '01711100001');
    }

    public function test_private_donor_phone_and_name_are_masked(): void
    {
        // Search by private devotee code
        $response = $this->getJson(route('public.events.donors.search', [
            'slug' => $this->event->slug,
            'q' => 'PLG-2026-0002',
        ]));

        $response->assertOk();
        $results = $response->json('results');
        $this->assertNotEmpty($results);

        $privateResult = $results[0];
        // Must NOT expose actual private name or phone
        $this->assertNull($privateResult['phone']);
        $this->assertStringNotContainsString('Ananya Sen', $privateResult['text']);
        $this->assertStringNotContainsString('01811200002', $privateResult['text']);
    }

    public function test_search_with_empty_query_returns_empty_results(): void
    {
        $response = $this->getJson(route('public.events.donors.search', [
            'slug' => $this->event->slug,
            'q' => '',
        ]));

        $response->assertOk()
            ->assertExactJson(['results' => []]);
    }

    public function test_admin_person_search_returns_name_and_phone(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson(route('people.search', [
            'q' => 'Subhashish',
        ]));

        $response->assertOk()
            ->assertJsonPath('results.0.id', $this->publicDevotee->id)
            ->assertJsonPath('results.0.phone', '01711100001')
            ->assertJsonPath('results.0.person_code', 'DEV-001');
    }

    public function test_admin_person_search_by_phone(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson(route('people.search', [
            'q' => '01711100001',
        ]));

        $response->assertOk()
            ->assertJsonPath('results.0.id', $this->publicDevotee->id);
    }

    public function test_payment_person_search_returns_name_and_phone(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson(route('portal.payments.search-persons', [
            'q' => '01711100001',
        ]));

        $response->assertOk()
            ->assertJsonPath('results.0.id', $this->publicDevotee->id)
            ->assertJsonPath('results.0.phone', '01711100001');
    }
}
