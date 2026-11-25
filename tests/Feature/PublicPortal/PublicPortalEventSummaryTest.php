<?php

namespace Tests\Feature\PublicPortal;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\PaymentAllocation;
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

class PublicPortalEventSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected EventCategory $category;

    protected Event $event;

    protected Person $person;

    protected CollectionPaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $superAdminRole = Role::where('slug', 'super_admin')->firstOrFail();

        $this->adminUser = User::factory()->create([
            'status' => 'active',
        ]);
        $this->adminUser->roles()->attach($superAdminRole);

        $this->category = EventCategory::first();

        $this->event = Event::create([
            'event_code' => 'EVT-2026-0001',
            'slug' => 'sharadiya-durga-puja-2026',
            'name_en' => 'Sharadiya Durga Puja 2026',
            'name_bn' => 'শারদীয় দুর্গাপূজা ২০২৬',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'venue' => 'Main Temple Courtyard',
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-20',
            'collection_start_date' => '2026-08-01',
            'collection_end_date' => '2026-10-25',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        $this->person = Person::create([
            'person_code' => 'DEV-2026-0001',
            'name_en' => 'Shyamal Sen',
            'name_bn' => 'শ্যামল সেন',
            'phone' => '01711223344',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->paymentService = app(CollectionPaymentService::class);
    }

    public function test_running_published_event_displays_collection_summary_cards_and_progress(): void
    {
        // Pledge of ৳100,000
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 100000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_PARTIAL,
            'created_by' => $this->adminUser->id,
        ]);

        // Verified payment of ৳65,000
        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 65000.00,
            'payment_date' => '2026-09-01',
            'payment_method' => 'bank_transfer',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 65000.00],
        ], $this->adminUser->id);

        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $response = $this->get(route('public.events.show', $this->event->slug));

        $response->assertStatus(200);
        $response->assertSee('100,000.00'); // Total Pledged
        $response->assertSee('65,000.00');  // Total Verified Collection
        $response->assertSee('35,000.00');  // Total Due
        $response->assertSee('65%');        // Progress
        $response->assertSee('role="progressbar"', false);
    }

    public function test_correct_total_pledge_calculation_and_cancellation_exclusion(): void
    {
        // Active Pledge 1: ৳15,000
        Pledge::create([
            'pledge_code' => 'PLG-2026-0002',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 15000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        // Active Pledge 2: ৳10,000
        Pledge::create([
            'pledge_code' => 'PLG-2026-0003',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_PARTIAL,
            'created_by' => $this->adminUser->id,
        ]);

        // Cancelled Pledge: ৳8,000 (must be excluded)
        Pledge::create([
            'pledge_code' => 'PLG-2026-0004',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 8000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_CANCELLED,
            'created_by' => $this->adminUser->id,
        ]);

        // Soft-deleted Pledge: ৳5,000 (must be excluded)
        $deleted = Pledge::create([
            'pledge_code' => 'PLG-2026-0005',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);
        $deleted->delete();

        $response = $this->get(route('public.events.show', $this->event->slug));

        $response->assertStatus(200);
        $response->assertSee('25,000.00'); // Exactly 15,000 + 10,000
        $response->assertDontSee('33,000.00');
        $response->assertDontSee('38,000.00');
    }

    public function test_correct_verified_collection_calculation_combining_pledges_and_voluntary(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0006',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 20000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        // Verified Pledge Payment: ৳12,000
        $payment1 = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 12000.00,
            'payment_date' => '2026-09-05',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 12000.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($payment1, $this->adminUser->id);

        // Verified Voluntary Event Donation: ৳3,000
        $payment2 = $this->paymentService->submitPayment([
            'collection_type' => 'event_donation',
            'event_id' => $this->event->id,
            'payer_name_override' => 'Anonymous Devotee',
            'amount' => 3000.00,
            'payment_date' => '2026-09-06',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT, 'event_id' => $this->event->id, 'amount' => 3000.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($payment2, $this->adminUser->id);

        $response = $this->get(route('public.events.show', $this->event->slug));

        $response->assertStatus(200);
        $response->assertSee('20,000.00'); // Total Pledged
        $response->assertSee('15,000.00'); // Total Verified Collection (12,000 + 3,000)
        $response->assertSee('8,000.00');  // Total Due on Pledges (20,000 - 12,000)
        $response->assertSee('60%');       // Progress (12,000 / 20,000 = 60%)
    }

    public function test_pending_payments_are_strictly_excluded(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0007',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        // Verified payment: ৳4,000
        $verifiedPayment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 4000.00,
            'payment_date' => '2026-09-01',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 4000.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($verifiedPayment, $this->adminUser->id);

        // Pending unverified payment: ৳5,000
        $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 5000.00,
            'payment_date' => '2026-09-02',
            'payment_method' => 'bkash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 5000.00],
        ], $this->adminUser->id);

        $response = $this->get(route('public.events.show', $this->event->slug));

        $response->assertStatus(200);
        $response->assertSee('4,000.00'); // ONLY verified payment included
        $response->assertDontSee('9,000.00');
        $response->assertSee('6,000.00'); // Due is 10,000 - 4,000
        $response->assertSee('40%');      // Progress is 40% (not 90%)
    }

    public function test_rejected_payments_are_strictly_excluded(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0008',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        // Verified payment: ৳2,000
        $verifiedPayment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 2000.00,
            'payment_date' => '2026-09-01',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 2000.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($verifiedPayment, $this->adminUser->id);

        // Rejected payment: ৳5,000
        $rejectedPayment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 5000.00,
            'payment_date' => '2026-09-02',
            'payment_method' => 'bkash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 5000.00],
        ], $this->adminUser->id);
        $this->paymentService->rejectPayment($rejectedPayment, $this->adminUser->id, 'Invalid fake transaction reference');

        $response = $this->get(route('public.events.show', $this->event->slug));

        $response->assertStatus(200);
        $response->assertSee('2,000.00'); // Only verified ৳2,000
        $response->assertSee('8,000.00'); // Due remains ৳8,000
        $response->assertDontSee('7,000.00'); // Must not include the rejected ৳5,000
    }

    public function test_reversed_and_cancelled_payments_are_excluded_from_net_collection(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0009',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        // Payment 1: ৳5,000 verified then reversed
        $payment1 = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 5000.00,
            'payment_date' => '2026-09-01',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 5000.00],
        ], $this->adminUser->id);
        $verifiedPayment1 = $this->paymentService->verifyPayment($payment1, $this->adminUser->id);
        $this->paymentService->reversePayment($verifiedPayment1, $this->adminUser->id, 'Devotee cheque bounced');

        // Payment 2: ৳3,000 verified
        $payment2 = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 3000.00,
            'payment_date' => '2026-09-02',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 3000.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($payment2, $this->adminUser->id);

        $response = $this->get(route('public.events.show', $this->event->slug));

        $response->assertStatus(200);
        $response->assertSee('3,000.00'); // Exactly ৳3,000 net collection
        $response->assertDontSee('8,000.00');
        $response->assertSee('7,000.00'); // Due is 10,000 - 3,000 = 7,000
    }

    public function test_correct_due_calculation_and_progress_percentage(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0010',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 50000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 32500.00,
            'payment_date' => '2026-09-10',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 32500.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $response = $this->get(route('public.events.show', $this->event->slug));

        $response->assertStatus(200);
        $response->assertSee('50,000.00'); // Total Pledged
        $response->assertSee('32,500.00'); // Verified
        $response->assertSee('17,500.00'); // Due: 50,000 - 32,500 = 17,500
        $response->assertSee('65%');       // Progress: 32,500 / 50,000 = 65%
    }

    public function test_progress_visually_capped_at_100_percent_while_actual_collection_remains_uncapped(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0011',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        // Contributor pays ৳12,000 (৳10,000 pledge + ৳2,000 voluntary_event)
        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 12000.00,
            'payment_date' => '2026-09-15',
            'payment_method' => 'cash',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_PLEDGE,
                'pledge_id' => $pledge->id,
                'amount' => 12000.00,
                'excess_target_type' => 'voluntary_event',
            ],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $response = $this->get(route('public.events.show', $this->event->slug));

        $response->assertStatus(200);
        $response->assertSee('10,000.00'); // Total Pledged
        $response->assertSee('12,000.00'); // Actual collection is uncapped at ৳12,000.00
        $response->assertSee('0.00');      // Total Due is 0.00 (NOT negative)
        $response->assertSee('100%');      // Progress is 100%
        $response->assertSee('width: 100%;', false); // Visual progress bar capped at 100%
        $response->assertSee('2,000.00'); // Over-collected highlight
    }

    public function test_zero_pledge_event_displays_na_without_division_by_zero(): void
    {
        // Event has zero pledges, but has direct voluntary collection of ৳5,000
        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_donation',
            'event_id' => $this->event->id,
            'payer_name_override' => 'Visiting Family',
            'amount' => 5000.00,
            'payment_date' => '2026-09-20',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT, 'event_id' => $this->event->id, 'amount' => 5000.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $response = $this->get(route('public.events.show', $this->event->slug));

        $response->assertStatus(200);
        $response->assertSee('0.00');     // Total Pledged: 0.00
        $response->assertSee('5,000.00'); // Total Verified Collection: 5,000.00
        $response->assertSee('N/A');      // Progress displays N/A
        $response->assertDontSee('Division by zero');
    }

    public function test_excess_contribution_does_not_create_negative_due(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0012',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 8000.00,
            'payment_date' => '2026-09-20',
            'payment_method' => 'cash',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_PLEDGE,
                'pledge_id' => $pledge->id,
                'amount' => 8000.00,
                'excess_target_type' => 'voluntary_event',
            ],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $response = $this->get(route('public.events.show', $this->event->slug));

        $response->assertStatus(200);
        $response->assertSee('5,000.00'); // Pledged
        $response->assertSee('8,000.00'); // Total Verified
        $response->assertSee('0.00');     // Due is 0.00
        $response->assertDontSee('-3,000.00'); // MUST NOT BE NEGATIVE
    }

    public function test_excess_contribution_is_not_counted_twice(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0013',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 7000.00,
            'payment_date' => '2026-09-20',
            'payment_method' => 'cash',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_PLEDGE,
                'pledge_id' => $pledge->id,
                'amount' => 7000.00,
                'excess_target_type' => 'voluntary_event',
            ],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $response = $this->get(route('public.events.show', $this->event->slug));

        $response->assertStatus(200);
        // Total Verified must be exactly 7,000.00 (not 5,000 + 7,000 = 12,000)
        $response->assertSee('7,000.00');
        $response->assertDontSee('12,000.00');
    }

    public function test_unpublished_events_return_404_and_do_not_expose_financials(): void
    {
        $unpublishedEvent = Event::create([
            'event_code' => 'EVT-2026-0002',
            'slug' => 'secret-puja-2026',
            'name_en' => 'Secret Puja 2026',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-05',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_UNPUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get(route('public.events.show', $unpublishedEvent->slug));
        $response->assertStatus(404);
    }

    public function test_completed_published_events_remain_accessible_with_final_summary(): void
    {
        $closedEvent = Event::create([
            'event_code' => 'EVT-2026-0003',
            'slug' => 'completed-puja-2026',
            'name_en' => 'Completed Puja 2026',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-05',
            'status' => Event::STATUS_CLOSED,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        Pledge::create([
            'pledge_code' => 'PLG-2026-0014',
            'event_id' => $closedEvent->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 40000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get(route('public.events.show', $closedEvent->slug));
        $response->assertStatus(200);
        $response->assertSee('Completed Puja 2026');
        $response->assertSee('40,000.00');
    }

    public function test_no_private_donor_or_payment_details_are_exposed(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-SECRET',
            'event_id' => $this->event->id,
            'person_id' => $this->person->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->person->id,
            'amount' => 5000.00,
            'payment_date' => '2026-09-20',
            'payment_method' => 'bkash',
            'transaction_reference' => 'TXN-CONFIDENTIAL-9999',
            'notes' => 'Confidential donor personal note',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 5000.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $response = $this->get(route('public.events.show', $this->event->slug));

        $response->assertStatus(200);
        // Donor's phone number is displayed in the donor collection table per user specification
        $response->assertSee('01711223344');
        // Private transaction reference must NEVER be exposed
        $response->assertDontSee('TXN-CONFIDENTIAL-9999');
        // Confidential note must NEVER be exposed
        $response->assertDontSee('Confidential donor personal note');
    }

    public function test_bengali_and_english_localization_switch_on_summary_cards(): void
    {
        // Test in English
        $enResponse = $this->get(route('public.events.show', $this->event->slug));
        $enResponse->assertStatus(200);
        $enResponse->assertSee('Total Pledged');
        $enResponse->assertSee('Total Verified Collection');
        $enResponse->assertSee('Total Due');
        $enResponse->assertSee('Collection Progress');

        // Test in Bengali
        $bnResponse = $this->withSession(['locale' => 'bn'])->get(route('public.events.show', $this->event->slug));
        $bnResponse->assertStatus(200);
        $bnResponse->assertSee('মোট প্রতিশ্রুত চাঁদা');
        $bnResponse->assertSee('মোট যাচাইকৃত আদায়');
        $bnResponse->assertSee('মোট বকেয়া');
        $bnResponse->assertSee('চাঁদা সংগ্রহের অগ্রগতি');
    }
}
