<?php

namespace Tests\Feature\PublicPortal;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\JournalEntry;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\Role;
use App\Models\User;
use App\Services\CollectionPaymentService;
use App\Services\EventCollectionReportService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPortalDonorReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $devoteeUser;

    protected User $otherDevoteeUser;

    protected EventCategory $category;

    protected Event $event;

    protected Person $devoteeA;

    protected Person $devoteeB;

    protected Person $devoteeAnonymous;

    protected Person $devoteePrivate;

    protected CollectionPaymentService $paymentService;

    protected EventCollectionReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $superAdminRole = Role::where('slug', 'super_admin')->firstOrFail();

        $this->adminUser = User::factory()->create([
            'status' => 'active',
            'phone' => '01800000000',
            'email' => 'admin@temple.test',
        ]);
        $this->adminUser->roles()->attach($superAdminRole);

        $this->devoteeUser = User::factory()->create([
            'name' => 'Arun Kumar',
            'status' => 'active',
            'phone' => '01711000001',
            'email' => 'arun@temple.test',
        ]);

        $this->otherDevoteeUser = User::factory()->create([
            'name' => 'Bikash Das',
            'status' => 'active',
            'phone' => '01711000002',
            'email' => 'bikash@temple.test',
        ]);

        $this->category = EventCategory::first();

        $this->event = Event::create([
            'event_code' => 'EVT-2026-0001',
            'slug' => 'durga-puja-2026',
            'name_en' => 'Durga Puja 2026',
            'name_bn' => 'শারদীয় দুর্গাপূজা ২০২৬',
            'event_category_id' => $this->category->id,
            'event_year' => 2026,
            'venue' => 'Main Temple Mandir',
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-20',
            'collection_start_date' => '2026-08-01',
            'collection_end_date' => '2026-10-25',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);

        $this->devoteeA = Person::create([
            'person_code' => 'DEV-2026-0001',
            'name_en' => 'Arun Kumar',
            'name_bn' => 'অরুণ কুমার',
            'phone' => '01711000001',
            'email' => 'arun@temple.test',
            'present_address' => 'House 12, Road 4, Sector 7, Uttara',
            'visibility' => 'public',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->devoteeB = Person::create([
            'person_code' => 'DEV-2026-0002',
            'name_en' => 'Bikash Das',
            'name_bn' => 'বিকাশ দাস',
            'phone' => '01711000002',
            'email' => 'bikash@temple.test',
            'visibility' => 'public',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->devoteeAnonymous = Person::create([
            'person_code' => 'DEV-2026-0003',
            'name_en' => 'Secret Devotee One',
            'name_bn' => 'গোপন ভক্ত এক',
            'phone' => '01711000003',
            'email' => 'secret1@temple.test',
            'visibility' => 'anonymous',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->devoteePrivate = Person::create([
            'person_code' => 'DEV-2026-0004',
            'name_en' => 'Secret Devotee Two',
            'name_bn' => 'গোপন ভক্ত দুই',
            'phone' => '01711000004',
            'email' => 'secret2@temple.test',
            'visibility' => 'private',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $this->paymentService = app(CollectionPaymentService::class);
        $this->reportService = app(EventCollectionReportService::class);
    }

    /**
     * 1. Running Event Details renders the donor report table with exact headers:
     * Name | Amount / Pledged | Paid | Status | Action
     */
    public function test_donor_wise_running_collection_table_is_rendered_with_exact_column_headers(): void
    {
        Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        $response->assertSee('Running Collection Report');
        $response->assertSee('Name');
        $response->assertSee('Amount / Pledged');
        $response->assertSee('Paid');
        $response->assertSee('Status');
        $response->assertSee('Action');
    }

    /**
     * 2. No serial number (#) column is present in the table.
     */
    public function test_strictly_no_serial_number_column_present_in_table(): void
    {
        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        $response->assertDontSee('<th scope="col" class="py-2">#</th>', false);
        $response->assertDontSee('<th>#</th>', false);
        $response->assertDontSee('SL</th>', false);
    }

    /**
     * 3. Status cell layout: Compact progress bar and percentage horizontally side by side.
     */
    public function test_status_cell_displays_compact_horizontal_side_by_side_progress_and_percentage(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_PARTIAL,
            'created_by' => $this->adminUser->id,
        ]);

        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->devoteeA->id,
            'amount' => 3000.00,
            'payment_date' => '2026-09-01',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 3000.00],
        ], $this->adminUser->id);

        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        // Asserts d-flex align-items-center gap-2 for horizontal layout
        $response->assertSee('d-flex align-items-center gap-2', false);
        $response->assertSee('60%', false);
    }

    /**
     * 4. Progress bar color is green (bg-success) when progress >= 50%.
     */
    public function test_status_progress_color_is_green_for_greater_equal_50_percent(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_PARTIAL,
            'created_by' => $this->adminUser->id,
        ]);

        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->devoteeA->id,
            'amount' => 3000.00, // 60%
            'payment_date' => '2026-09-01',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 3000.00],
        ], $this->adminUser->id);

        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        $response->assertSee('progress-bar bg-success', false);
        $response->assertSee('60%', false);
    }

    /**
     * 5. Progress bar color is red (bg-danger) when progress < 50%.
     */
    public function test_status_progress_color_is_red_for_less_than_50_percent(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_PARTIAL,
            'created_by' => $this->adminUser->id,
        ]);

        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->devoteeA->id,
            'amount' => 1500.00, // 30%
            'payment_date' => '2026-09-01',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 1500.00],
        ], $this->adminUser->id);

        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        $response->assertSee('progress-bar bg-danger', false);
        $response->assertSee('30%', false);
    }

    /**
     * 6. Zero-pledge voluntary event donation displays N/A for status.
     */
    public function test_zero_pledge_voluntary_donation_displays_na_status(): void
    {
        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_voluntary',
            'event_id' => $this->event->id,
            'payer_person_id' => $this->devoteeA->id,
            'amount' => 2000.00,
            'payment_date' => '2026-09-05',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT, 'event_id' => $this->event->id, 'amount' => 2000.00],
        ], $this->adminUser->id);

        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        $response->assertSee('N/A');
        $response->assertSee('৳2,000.00');
    }

    /**
     * 7. Progress bar width is capped visually at 100% while paid amount is uncapped.
     */
    public function test_progress_bar_is_capped_visually_at_100_percent_while_paid_amount_is_uncapped(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_PAID,
            'created_by' => $this->adminUser->id,
        ]);

        // Pay 5,000 against pledge
        $pay1 = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->devoteeA->id,
            'amount' => 5000.00,
            'payment_date' => '2026-09-01',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 5000.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($pay1, $this->adminUser->id);

        // Devotee pays additional ৳1,000 voluntary excess attributed to the event
        PaymentAllocation::create([
            'collection_payment_id' => $pay1->id,
            'event_id' => $this->event->id,
            'pledge_id' => $pledge->id,
            'target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT,
            'amount' => 1000.00,
            'is_active' => true,
        ]);

        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        // Paid uncapped -> ৳6,000.00
        $response->assertSee('৳6,000.00');
        // Visual width capped at 100%
        $response->assertSee('style="width: 100%;"', false);
    }

    /**
     * 8. Public donor visibility displays devotee's actual public name.
     */
    public function test_public_donor_visibility_displays_actual_devotee_name(): void
    {
        Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'visibility' => 'public',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        $response->assertSee('Arun Kumar');
    }

    /**
     * 9. Anonymous donor visibility displays "Anonymous Donor" (or "বেনামী ভক্ত").
     */
    public function test_anonymous_donor_visibility_displays_anonymous_donor_label(): void
    {
        Pledge::create([
            'pledge_code' => 'PLG-2026-0002',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeAnonymous->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-08-15',
            'visibility' => 'anonymous',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        $response->assertSee('Anonymous Donor');
        $response->assertDontSee('Secret Devotee One');
    }

    /**
     * 10. Private donor visibility displays "Private Devotee" (or "ব্যক্তিগত অনুদান").
     */
    public function test_private_donor_visibility_displays_private_devotee_label(): void
    {
        Pledge::create([
            'pledge_code' => 'PLG-2026-0003',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteePrivate->id,
            'pledge_year' => 2026,
            'pledged_amount' => 15000.00,
            'pledged_on' => '2026-08-15',
            'visibility' => 'private',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        $response->assertSee('Private Devotee');
        $response->assertDontSee('Secret Devotee Two');
    }

    /**
     * 11. Privacy invariants: NEVER leak phone numbers, emails, addresses, or committee titles.
     */
    public function test_privacy_invariants_never_leak_phone_email_address_or_committee_titles(): void
    {
        Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        $response->assertDontSee($this->devoteeA->phone);
        $response->assertDontSee($this->devoteeA->email);
        $response->assertDontSee($this->devoteeA->present_address);
    }

    /**
     * 12. Anonymous and private contributions remain fully included in financial totals.
     */
    public function test_anonymous_and_private_contributions_remain_fully_included_in_financial_totals(): void
    {
        $plgAnon = Pledge::create([
            'pledge_code' => 'PLG-2026-0002',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeAnonymous->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-08-15',
            'visibility' => 'anonymous',
            'status' => Pledge::STATUS_PAID,
            'created_by' => $this->adminUser->id,
        ]);

        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $plgAnon->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->devoteeAnonymous->id,
            'amount' => 10000.00,
            'payment_date' => '2026-09-01',
            'payment_method' => 'bank_transfer',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $plgAnon->id, 'amount' => 10000.00],
        ], $this->adminUser->id);

        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $summary = $this->reportService->getPublicSummary($this->event);

        $this->assertEquals(10000.00, $summary['total_pledged']);
        $this->assertEquals(10000.00, $summary['total_verified_collections']);
    }

    /**
     * 13. Action column contains compact View and Complain buttons with proper attributes.
     */
    public function test_action_column_contains_compact_view_and_complain_buttons(): void
    {
        Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        $response->assertSee('btn-donor-details', false);
        $response->assertSee('btn-donor-complain', false);
        $response->assertSee('bi bi-eye', false);
        $response->assertSee('bi bi-flag', false);
    }

    /**
     * 14. Unauthenticated guest accessing donor details receives a privacy security notice.
     */
    public function test_unauthenticated_guest_accessing_donor_details_receives_security_notice(): void
    {
        Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->getJson('/events/'.$this->event->slug.'/donor-details/PLG-2026-0001');

        $response->assertStatus(200);
        $response->assertJson([
            'authorized' => false,
        ]);
        $response->assertJsonFragment([
            'security_notice' => __('portal.public.unauthorized_donor_notice'),
        ]);
    }

    /**
     * 15. Cross-donor unauthorized access returns 403 Forbidden.
     */
    public function test_cross_donor_unauthorized_access_returns_403(): void
    {
        Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        // Acting as another non-admin devotee User B
        $response = $this->actingAs($this->otherDevoteeUser)
            ->getJson('/events/'.$this->event->slug.'/donor-details/PLG-2026-0001');

        $response->assertStatus(403);
    }

    /**
     * 16. Matching authorized devotee can view their own payment history.
     */
    public function test_matching_authorized_devotee_can_view_own_payment_history(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_PARTIAL,
            'created_by' => $this->adminUser->id,
        ]);

        $payment = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $pledge->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->devoteeA->id,
            'amount' => 3000.00,
            'payment_date' => '2026-09-01',
            'payment_method' => 'bKash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $pledge->id, 'amount' => 3000.00],
        ], $this->adminUser->id);

        $this->paymentService->verifyPayment($payment, $this->adminUser->id);

        $response = $this->actingAs($this->devoteeUser)
            ->getJson('/events/'.$this->event->slug.'/donor-details/PLG-2026-0001');

        $response->assertStatus(200);
        $response->assertJson([
            'authorized' => true,
            'code' => 'PLG-2026-0001',
            'donor_name' => 'Arun Kumar',
            'pledged_amount' => 5000.00,
            'verified_paid' => 3000.00,
            'due_amount' => 2000.00,
        ]);
        $this->assertCount(1, $response->json('allocations'));
    }

    /**
     * 17. Admin user can view any donor payment breakdown.
     */
    public function test_admin_user_can_view_donor_payment_details(): void
    {
        Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/events/'.$this->event->slug.'/donor-details/PLG-2026-0001');

        $response->assertStatus(200);
        $response->assertJson([
            'authorized' => true,
            'code' => 'PLG-2026-0001',
        ]);
    }

    /**
     * 18. Submitting a complaint creates a complaint record and strictly NEVER modifies the financial ledger.
     */
    public function test_modal_complaint_submission_creates_complaint_record_without_touching_ledger(): void
    {
        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $initialJournalEntriesCount = JournalEntry::count();

        $response = $this->postJson('/events/'.$this->event->slug.'/complaint', [
            'complainant_name' => 'Arun Kumar',
            'complainant_phone' => '01711000001',
            'complainant_email' => 'arun@temple.test',
            'issue_type' => 'payment_not_updated',
            'pledge_code' => 'PLG-2026-0001',
            'description' => 'I paid ৳5,000 via bKash yesterday TrxID BK999, but it is not updated in the report.',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $trackingNumber = $response->json('tracking_number');
        $this->assertMatchesRegularExpression('/^CMP-\d{4}-\d{4}$/', $trackingNumber);

        $this->assertDatabaseHas('complaints', [
            'tracking_number' => $trackingNumber,
            'category' => 'payment_issue',
            'status' => 'submitted',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'complainant_phone' => '01711000001',
        ]);

        // Verify LEDGER INVARIANT: Ledger is completely untouched
        $this->assertEquals($initialJournalEntriesCount, JournalEntry::count());
        $this->assertEquals(5000.00, $pledge->fresh()->pledged_amount);
        $this->assertEquals(0.00, $pledge->fresh()->verified_paid_amount);
    }

    /**
     * 19. Complaint submission validates required fields and rejects invalid issue types.
     */
    public function test_complaint_submission_validates_required_fields_and_rejects_invalid_type(): void
    {
        $response = $this->postJson('/events/'.$this->event->slug.'/complaint', [
            'complainant_name' => '',
            'complainant_phone' => '',
            'issue_type' => 'invalid_issue_type',
            'description' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['complainant_name', 'complainant_phone', 'issue_type', 'description']);
    }

    /**
     * 20. Cancelled or soft-deleted pledges are strictly excluded from the donor collection report.
     */
    public function test_cancelled_or_soft_deleted_pledges_are_strictly_excluded_from_report(): void
    {
        Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_CANCELLED,
            'created_by' => $this->adminUser->id,
        ]);

        $softDeletedPledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0002',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeB->id,
            'pledge_year' => 2026,
            'pledged_amount' => 7000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);
        $softDeletedPledge->delete();

        $rows = $this->reportService->getDonorCollectionReport($this->event);

        $this->assertCount(0, $rows);
    }

    /**
     * 21. Donor report reconciles 100% with Increment 2 aggregate summary.
     */
    public function test_donor_report_reconciles_100_percent_with_increment_2_aggregate_summary(): void
    {
        $plg1 = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 10000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_PARTIAL,
            'created_by' => $this->adminUser->id,
        ]);

        $plg2 = Pledge::create([
            'pledge_code' => 'PLG-2026-0002',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeB->id,
            'pledge_year' => 2026,
            'pledged_amount' => 8000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_PAID,
            'created_by' => $this->adminUser->id,
        ]);

        // Payment for plg1: 6,000
        $pay1 = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $plg1->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->devoteeA->id,
            'amount' => 6000.00,
            'payment_date' => '2026-09-01',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $plg1->id, 'amount' => 6000.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($pay1, $this->adminUser->id);

        // Payment for plg2: 8,000
        $pay2 = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $plg2->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->devoteeB->id,
            'amount' => 8000.00,
            'payment_date' => '2026-09-02',
            'payment_method' => 'bank_transfer',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $plg2->id, 'amount' => 8000.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($pay2, $this->adminUser->id);

        // Direct voluntary donation: 2,500
        $pay3 = $this->paymentService->submitPayment([
            'collection_type' => 'event_voluntary',
            'event_id' => $this->event->id,
            'amount' => 2500.00,
            'payment_date' => '2026-09-03',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_VOLUNTARY_EVENT, 'event_id' => $this->event->id, 'amount' => 2500.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($pay3, $this->adminUser->id);

        $summary = $this->reportService->getPublicSummary($this->event);
        $rows = $this->reportService->getDonorCollectionReport($this->event);

        $sumPledged = array_sum(array_column($rows, 'pledged_amount'));
        $sumPaid = array_sum(array_column($rows, 'verified_paid'));
        $sumDue = array_sum(array_column($rows, 'due_amount'));

        $this->assertEquals($summary['total_pledged'], $sumPledged);
        $this->assertEquals($summary['total_verified_collections'], $sumPaid);
        $this->assertEquals($summary['total_due'], $sumDue);
        $this->assertEquals(18000.00, $sumPledged);
        $this->assertEquals(16500.00, $sumPaid);
        $this->assertEquals(4000.00, $sumDue);
    }

    /**
     * 22. Service supports searching by keyword and filtering by status category.
     */
    public function test_service_donor_report_filtering_by_status_and_search_query(): void
    {
        Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $plg2 = Pledge::create([
            'pledge_code' => 'PLG-2026-0002',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeB->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_PAID,
            'created_by' => $this->adminUser->id,
        ]);

        $pay = $this->paymentService->submitPayment([
            'collection_type' => 'event_pledge',
            'pledge_id' => $plg2->id,
            'event_id' => $this->event->id,
            'payer_person_id' => $this->devoteeB->id,
            'amount' => 5000.00,
            'payment_date' => '2026-09-01',
            'payment_method' => 'cash',
        ], [
            ['target_type' => PaymentAllocation::TARGET_PLEDGE, 'pledge_id' => $plg2->id, 'amount' => 5000.00],
        ], $this->adminUser->id);
        $this->paymentService->verifyPayment($pay, $this->adminUser->id);

        // Search for Arun
        $resultsSearch = $this->reportService->getDonorCollectionReport($this->event, ['search' => 'Arun']);
        $this->assertCount(1, $resultsSearch);
        $this->assertEquals('Arun Kumar', $resultsSearch[0]['display_name']);

        // Filter by paid status
        $resultsPaid = $this->reportService->getDonorCollectionReport($this->event, ['status' => 'paid']);
        $this->assertCount(1, $resultsPaid);
        $this->assertEquals('Bikash Das', $resultsPaid[0]['display_name']);

        // Filter by due status
        $resultsDue = $this->reportService->getDonorCollectionReport($this->event, ['status' => 'due']);
        $this->assertCount(1, $resultsDue);
        $this->assertEquals('Arun Kumar', $resultsDue[0]['display_name']);
    }

    /**
     * Bilingual parity: Bengali locale displays proper translations without raw keys.
     */
    public function test_bilingual_parity_for_donor_report_and_complaint_labels(): void
    {
        app()->setLocale('bn');

        Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'event_id' => $this->event->id,
            'person_id' => $this->devoteeA->id,
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'pledged_on' => '2026-08-15',
            'status' => Pledge::STATUS_UNPAID,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get('/events/'.$this->event->slug);

        $response->assertStatus(200);
        $response->assertSee('চলমান চাঁদা ও অনুদান বিবরণী');
        $response->assertSee('প্রতিশ্রুত চাঁদা');
        $response->assertSee('পরিশোধিত');
        $response->assertSee('অবস্থা');
        $response->assertSee('কার্যক্রম');
        $response->assertSee('অরুণ কুমার');
    }
}
