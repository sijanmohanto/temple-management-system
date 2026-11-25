<?php

namespace Tests\Feature\Devotee;

use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\ReceiverAccount;
use App\Models\User;
use App\Services\DevoteeCredentialService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevoteeDashboardAndEligibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected Person $devoteePerson;

    protected User $devoteeUser;

    protected EventCategory $pujaCategory;

    protected Event $runningEvent1;

    protected Event $runningEvent2;

    protected Event $upcomingEvent;

    protected Event $closedEvent;

    protected ReceiverAccount $receiverAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->adminUser = User::factory()->create([
            'email' => 'admin@temple.local',
            'status' => 'active',
        ]);
        $this->adminUser->assignRole('super_admin');

        $this->devoteePerson = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Biplab Mondal',
            'phone' => '01711778899',
            'email' => 'biplab@example.com',
            'status' => 'active',
        ]);

        $service = app(DevoteeCredentialService::class);
        $this->devoteeUser = $service->provisionDevoteeAccount($this->devoteePerson, 'Pass@123456', $this->adminUser);

        $this->pujaCategory = EventCategory::create([
            'name_en' => 'Puja Festival',
            'name_bn' => 'পূজা উৎসব',
            'slug' => 'puja-festival-test',
            'is_active' => true,
        ]);

        // Running Event 1 (Durga Puja 2026)
        $this->runningEvent1 = Event::create([
            'event_code' => 'EVT-2026-0001',
            'event_category_id' => $this->pujaCategory->id,
            'name_en' => 'Durga Puja 2026',
            'slug' => 'durga-puja-2026',
            'event_year' => 2026,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'collection_start_date' => now()->subDays(10),
            'collection_end_date' => now()->addDays(30),
            'created_by' => $this->adminUser->id,
        ]);

        // Running Event 2 (Kali Puja 2026)
        $this->runningEvent2 = Event::create([
            'event_code' => 'EVT-2026-0002',
            'event_category_id' => $this->pujaCategory->id,
            'name_en' => 'Kali Puja 2026',
            'slug' => 'kali-puja-2026',
            'event_year' => 2026,
            'start_date' => now()->subDays(3),
            'end_date' => now()->addDays(7),
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'collection_start_date' => now()->subDays(5),
            'collection_end_date' => now()->addDays(20),
            'created_by' => $this->adminUser->id,
        ]);

        // Upcoming Event (Saraswati Puja 2027)
        $this->upcomingEvent = Event::create([
            'event_code' => 'EVT-2027-0001',
            'event_category_id' => $this->pujaCategory->id,
            'name_en' => 'Saraswati Puja 2027',
            'slug' => 'saraswati-puja-2027',
            'event_year' => 2027,
            'start_date' => now()->addMonths(2),
            'end_date' => now()->addMonths(2)->addDays(3),
            'status' => Event::STATUS_UPCOMING,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        // Closed Event (Durga Puja 2025)
        $this->closedEvent = Event::create([
            'event_code' => 'EVT-2025-0001',
            'event_category_id' => $this->pujaCategory->id,
            'name_en' => 'Durga Puja 2025',
            'slug' => 'durga-puja-2025',
            'event_year' => 2025,
            'start_date' => now()->subMonths(7),
            'end_date' => now()->subMonths(7)->addDays(5),
            'status' => Event::STATUS_CLOSED,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'closing_mode' => Event::MODE_CLOSE_AND_LOCK,
            'closed_at' => now()->subMonths(6),
            'created_by' => $this->adminUser->id,
        ]);

        $this->receiverAccount = ReceiverAccount::create([
            'account_name' => 'Temple Official bKash',
            'account_number' => '01700000000',
            'payment_method' => 'bkash',
            'account_type' => 'merchant',
            'is_active' => true,
            'is_verified' => true,
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_devotee_dashboard_displays_accurate_independent_event_metrics(): void
    {
        // Pledge for Event 1: 5,000 BDT
        $pledge1 = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->runningEvent1->id,
            'person_id' => $this->devoteePerson->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => now(),
            'status' => Pledge::STATUS_PARTIAL,
            'created_by' => $this->adminUser->id,
        ]);

        // Verified Payment of 2,000 BDT for Event 1
        $payment1 = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0001',
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'payer_person_id' => $this->devoteePerson->id,
            'event_id' => $this->runningEvent1->id,
            'pledge_id' => $pledge1->id,
            'amount' => 2000.00,
            'payment_method' => 'bkash',
            'status' => CollectionPayment::STATUS_VERIFIED,
            'payment_date' => now(),
            'payment_year' => 2026,
            'submitted_by' => $this->devoteeUser->id,
            'created_by' => $this->adminUser->id,
            'verified_by' => $this->adminUser->id,
            'verified_at' => now(),
        ]);
        PaymentAllocation::create([
            'collection_payment_id' => $payment1->id,
            'event_id' => $this->runningEvent1->id,
            'pledge_id' => $pledge1->id,
            'target_type' => PaymentAllocation::TARGET_PLEDGE,
            'amount' => 2000.00,
            'is_active' => true,
        ]);

        // Pledge for Event 2: 3,000 BDT (Unpaid)
        $pledge2 = Pledge::create([
            'pledge_code' => 'PLG-2026-0002',
            'event_id' => $this->runningEvent2->id,
            'person_id' => $this->devoteePerson->id,
            'pledge_year' => 2026,
            'pledged_amount' => 3000.00,
            'pledged_on' => now(),
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        // Pending Payment of 1,000 BDT for Event 2 (Does NOT count as paid yet)
        CollectionPayment::create([
            'payment_code' => 'PAY-2026-0002',
            'collection_type' => CollectionPayment::TYPE_EVENT_PLEDGE,
            'payer_person_id' => $this->devoteePerson->id,
            'event_id' => $this->runningEvent2->id,
            'pledge_id' => $pledge2->id,
            'amount' => 1000.00,
            'payment_method' => 'bkash',
            'status' => CollectionPayment::STATUS_PENDING,
            'payment_date' => now(),
            'payment_year' => 2026,
            'submitted_by' => $this->devoteeUser->id,
            'created_by' => $this->devoteeUser->id,
        ]);

        $response = $this->actingAs($this->devoteeUser)->get(route('devotee.dashboard'));

        $response->assertStatus(200);

        // Check metrics:
        // Total Pledged: 5,000 + 3,000 = 8,000
        $response->assertSee('৳8,000.00');

        // Total Paid: 2,000.00 (Pending 1,000 is NOT counted)
        $response->assertSee('৳2,000.00');

        // Total Due: 3,000 (from event 1) + 3,000 (from event 2) = 6,000
        $response->assertSee('৳6,000.00');

        // Pending payments: 1,000.00
        $response->assertSee('৳1,000.00');

        // Check that both running events appear separately
        $response->assertSee('Durga Puja 2026');
        $response->assertSee('Kali Puja 2026');

        // Non-running events should not appear in running event cards
        $response->assertDontSee('Saraswati Puja 2027 •');
        $response->assertDontSee('Durga Puja 2025 •');
    }

    public function test_running_and_published_event_accepts_payment_submission(): void
    {
        $response = $this->actingAs($this->devoteeUser)->get(route('public.events.pay', $this->runningEvent1->slug));
        $response->assertStatus(200);

        $submitResponse = $this->actingAs($this->devoteeUser)->post(route('public.events.pay.submit', $this->runningEvent1->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->receiverAccount->id,
            'amount' => 1500.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711778899',
            'transaction_reference' => 'TRXRUNNING01',
        ]);

        $submitResponse->assertRedirect();
        $this->assertTrue(CollectionPayment::where('transaction_reference', 'TRXRUNNING01')->exists());
    }

    public function test_upcoming_event_rejects_new_payment_submission(): void
    {
        // 1. GET page rejected
        $getResponse = $this->actingAs($this->devoteeUser)->get(route('public.events.pay', $this->upcomingEvent->slug));
        $getResponse->assertRedirect(route('public.events.show', $this->upcomingEvent->slug));
        $getResponse->assertSessionHas('error');

        // 2. POST submission rejected
        $postResponse = $this->actingAs($this->devoteeUser)->post(route('public.events.pay.submit', $this->upcomingEvent->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->receiverAccount->id,
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711778899',
            'transaction_reference' => 'TRXUPCOMING',
        ]);

        $postResponse->assertStatus(404);
        $this->assertFalse(CollectionPayment::where('transaction_reference', 'TRXUPCOMING')->exists());
    }

    public function test_closed_event_rejects_new_payment_submission(): void
    {
        $getResponse = $this->actingAs($this->devoteeUser)->get(route('public.events.pay', $this->closedEvent->slug));
        $getResponse->assertRedirect(route('public.events.show', $this->closedEvent->slug));
        $getResponse->assertSessionHas('error');

        $postResponse = $this->actingAs($this->devoteeUser)->post(route('public.events.pay.submit', $this->closedEvent->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->receiverAccount->id,
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711778899',
            'transaction_reference' => 'TRXCLOSED',
        ]);

        $postResponse->assertStatus(404);
        $this->assertFalse(CollectionPayment::where('transaction_reference', 'TRXCLOSED')->exists());
    }

    public function test_unpublished_event_returns_404_and_rejects_payment(): void
    {
        $unpublishedEvent = Event::create([
            'event_code' => 'EVT-2026-0099',
            'event_category_id' => $this->pujaCategory->id,
            'name_en' => 'Unpublished Puja 2026',
            'slug' => 'unpublished-puja-2026',
            'event_year' => 2026,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(5),
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_UNPUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        $this->actingAs($this->devoteeUser)->get(route('public.events.pay', $unpublishedEvent->slug))->assertStatus(404);

        $postResponse = $this->actingAs($this->devoteeUser)->post(route('public.events.pay.submit', $unpublishedEvent->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->receiverAccount->id,
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711778899',
            'transaction_reference' => 'TRXUNPUBLISHED',
        ]);

        $postResponse->assertStatus(404);
    }

    public function test_out_of_collection_date_event_rejects_payments(): void
    {
        $expiredEvent = Event::create([
            'event_code' => 'EVT-2026-0088',
            'event_category_id' => $this->pujaCategory->id,
            'name_en' => 'Expired Collection Puja 2026',
            'slug' => 'expired-collection-puja-2026',
            'event_year' => 2026,
            'start_date' => now()->subDays(25),
            'end_date' => now()->subDays(1),
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'collection_start_date' => now()->subDays(20),
            'collection_end_date' => now()->subDays(2), // Ended 2 days ago
            'created_by' => $this->adminUser->id,
        ]);

        $getResponse = $this->actingAs($this->devoteeUser)->get(route('public.events.pay', $expiredEvent->slug));
        $getResponse->assertRedirect();
        $getResponse->assertSessionHas('error');

        $postResponse = $this->actingAs($this->devoteeUser)->post(route('public.events.pay.submit', $expiredEvent->slug), [
            'payment_method' => 'bkash',
            'receiver_account_id' => $this->receiverAccount->id,
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
            'sender_mobile' => '01711778899',
            'transaction_reference' => 'TRXEXPIRED',
        ]);

        $postResponse->assertSessionHasErrors(['event']);
        $this->assertFalse(CollectionPayment::where('transaction_reference', 'TRXEXPIRED')->exists());
    }
}
