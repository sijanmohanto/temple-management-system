<?php

namespace Tests\Feature\PublicPortal;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\JournalEntry;
use App\Models\Person;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $adminUser;

    protected User $staffUser;

    protected User $devoteeUser;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // Super Admin
        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->roles()->attach(Role::where('slug', 'super_admin')->firstOrFail());

        // Temple Admin
        $this->adminUser = User::factory()->create(['status' => 'active']);
        $this->adminUser->roles()->attach(Role::where('slug', 'temple_admin')->firstOrFail());

        // Staff user for assignment
        $this->staffUser = User::factory()->create(['name' => 'Handling Staff', 'status' => 'active']);
        $this->staffUser->roles()->attach(Role::where('slug', 'temple_admin')->firstOrFail());

        // Devotee User
        $this->devoteeUser = User::factory()->create(['status' => 'active']);
        $this->devoteeUser->roles()->attach(Role::where('slug', 'devotee')->firstOrFail());

        $category = EventCategory::firstOrCreate(
            ['slug' => 'puja'],
            ['name_en' => 'Puja', 'name_bn' => 'পূজা', 'is_active' => true]
        );

        $this->event = Event::create([
            'event_category_id' => $category->id,
            'name_en' => 'Kali Puja 2026',
            'name_bn' => 'শ্যামাপূজা ২০২৬',
            'event_code' => 'EVT-KP-2026',
            'event_year' => 2026,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-15',
            'status' => Event::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    protected function createComplaint(array $overrides = []): Complaint
    {
        return Complaint::create(array_merge([
            'tracking_number' => Complaint::generateTrackingNumber(),
            'complainant_name' => 'Bimal Sen',
            'complainant_phone' => '01711223344',
            'complainant_email' => 'bimal@example.com',
            'category' => Complaint::CATEGORY_PAYMENT_ISSUE,
            'subject' => 'Money deducted but no confirmation receipt received',
            'description' => 'I made a payment of BDT 1,000 via bKash but did not get SMS receipt.',
            'status' => Complaint::STATUS_SUBMITTED,
            'priority' => Complaint::PRIORITY_HIGH,
            'event_id' => $this->event->id,
        ], $overrides));
    }

    public function test_guest_cannot_access_complaints_admin(): void
    {
        $response = $this->get(route('portal.complaints.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_cannot_access_complaints(): void
    {
        $response = $this->actingAs($this->devoteeUser)->get(route('portal.complaints.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_complaints_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('portal.complaints.index'));
        $response->assertStatus(200);
        $response->assertSee(__('portal.complaints'));
    }

    public function test_datatable_server_side_response_strictly_has_no_row_number_column(): void
    {
        $this->createComplaint();

        $response = $this->actingAs($this->adminUser)->getJson(route('portal.complaints.index'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                '*' => [
                    'tracking_info',
                    'complainant',
                    'category_priority',
                    'related_to',
                    'assigned_to',
                    'status',
                    'submitted_at',
                    'actions',
                ],
            ],
        ]);

        $firstRow = $response->json('data.0');
        $this->assertArrayNotHasKey('#', $firstRow);
        $this->assertArrayNotHasKey('sl', $firstRow);
        $this->assertArrayNotHasKey('id', $firstRow);
        $this->assertStringContainsString('Bimal Sen', $firstRow['complainant']);
    }

    public function test_filter_complaints_by_status_category_priority(): void
    {
        $this->createComplaint([
            'status' => Complaint::STATUS_SUBMITTED,
            'category' => Complaint::CATEGORY_PAYMENT_ISSUE,
            'priority' => Complaint::PRIORITY_HIGH,
        ]);

        $this->createComplaint([
            'status' => Complaint::STATUS_RESOLVED,
            'category' => Complaint::CATEGORY_GENERAL_INQUIRY,
            'priority' => Complaint::PRIORITY_LOW,
        ]);

        // Filter by status submitted
        $response = $this->actingAs($this->adminUser)->getJson(route('portal.complaints.index', [
            'status' => Complaint::STATUS_SUBMITTED,
        ]));
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsFiltered'));

        // Filter by category general_inquiry
        $response = $this->actingAs($this->adminUser)->getJson(route('portal.complaints.index', [
            'category' => Complaint::CATEGORY_GENERAL_INQUIRY,
        ]));
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsFiltered'));
    }

    public function test_authorized_user_can_assign_complaint_to_staff(): void
    {
        $complaint = $this->createComplaint();
        $this->assertNull($complaint->assigned_to);
        $this->assertEquals(Complaint::STATUS_SUBMITTED, $complaint->status);

        $response = $this->actingAs($this->adminUser)->postJson(route('portal.complaints.assign', $complaint), [
            'assigned_to' => $this->staffUser->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $complaint->refresh();
        $this->assertEquals($this->staffUser->id, $complaint->assigned_to);
        $this->assertEquals(Complaint::STATUS_UNDER_REVIEW, $complaint->status);
    }

    public function test_authorized_user_can_respond_to_complaint(): void
    {
        $complaint = $this->createComplaint();

        $response = $this->actingAs($this->adminUser)->postJson(route('portal.complaints.respond', $complaint), [
            'admin_response' => 'We have checked your TrxID and verified the transaction.',
            'update_status' => Complaint::STATUS_UNDER_REVIEW,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $complaint->refresh();
        $this->assertEquals('We have checked your TrxID and verified the transaction.', $complaint->admin_response);
        $this->assertEquals(Complaint::STATUS_UNDER_REVIEW, $complaint->status);
    }

    public function test_authorized_user_can_resolve_complaint_with_mandatory_note(): void
    {
        $complaint = $this->createComplaint();

        $response = $this->actingAs($this->adminUser)->postJson(route('portal.complaints.resolve', $complaint), [
            'admin_response' => 'Payment has been located and linked to devotee record. Receipt issued.',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $complaint->refresh();
        $this->assertEquals(Complaint::STATUS_RESOLVED, $complaint->status);
        $this->assertNotNull($complaint->resolved_at);
        $this->assertEquals($this->adminUser->id, $complaint->resolved_by);
        $this->assertEquals('Payment has been located and linked to devotee record. Receipt issued.', $complaint->admin_response);
    }

    public function test_authorized_user_can_reject_complaint_with_mandatory_reason(): void
    {
        $complaint = $this->createComplaint();

        $response = $this->actingAs($this->adminUser)->postJson(route('portal.complaints.reject', $complaint), [
            'rejection_reason' => 'Duplicate complaint ticket. Original issue resolved in CMP-2026-0001.',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $complaint->refresh();
        $this->assertEquals(Complaint::STATUS_REJECTED, $complaint->status);
        $this->assertNotNull($complaint->resolved_at);
        $this->assertEquals($this->adminUser->id, $complaint->resolved_by);
    }

    public function test_authorized_user_can_close_resolved_complaint(): void
    {
        $complaint = $this->createComplaint([
            'status' => Complaint::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $this->adminUser->id,
            'admin_response' => 'Issue resolved',
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('portal.complaints.close', $complaint));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $complaint->refresh();
        $this->assertEquals(Complaint::STATUS_CLOSED, $complaint->status);
        $this->assertNotNull($complaint->closed_at);
        $this->assertEquals($this->adminUser->id, $complaint->closed_by);
    }

    public function test_authorized_user_can_update_priority_and_add_internal_notes(): void
    {
        $complaint = $this->createComplaint();

        // Update priority
        $response = $this->actingAs($this->adminUser)->postJson(route('portal.complaints.update-priority', $complaint), [
            'priority' => Complaint::PRIORITY_URGENT,
        ]);
        $response->assertStatus(200);
        $complaint->refresh();
        $this->assertEquals(Complaint::PRIORITY_URGENT, $complaint->priority);

        // Add internal note
        $response = $this->actingAs($this->adminUser)->postJson(route('portal.complaints.add-note', $complaint), [
            'note' => 'Spoke with Treasurer; MFS gateway delayed verification by 1 hour.',
        ]);
        $response->assertStatus(200);
        $complaint->refresh();
        $this->assertStringContainsString('Spoke with Treasurer', $complaint->internal_notes);
    }

    public function test_authorized_user_can_link_person_to_complaint(): void
    {
        $person = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Bimal Sen',
            'phone' => '01711223344',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $complaint = $this->createComplaint();
        $this->assertNull($complaint->person_id);

        $response = $this->actingAs($this->adminUser)->postJson(route('portal.complaints.link-person', $complaint), [
            'person_id' => $person->id,
        ]);
        $response->assertStatus(200);

        $complaint->refresh();
        $this->assertEquals($person->id, $complaint->person_id);
    }

    public function test_complaint_lifecycle_does_not_modify_any_financial_ledgers(): void
    {
        $initialJournalsCount = JournalEntry::count();

        $complaint = $this->createComplaint();

        // Action 1: Assign
        $this->actingAs($this->adminUser)->postJson(route('portal.complaints.assign', $complaint), [
            'assigned_to' => $this->staffUser->id,
        ]);

        // Action 2: Respond
        $this->actingAs($this->adminUser)->postJson(route('portal.complaints.respond', $complaint), [
            'admin_response' => 'Under investigation',
        ]);

        // Action 3: Resolve
        $this->actingAs($this->adminUser)->postJson(route('portal.complaints.resolve', $complaint), [
            'admin_response' => 'Resolved without ledger alteration',
        ]);

        // Action 4: Close
        $this->actingAs($this->adminUser)->postJson(route('portal.complaints.close', $complaint));

        $finalJournalsCount = JournalEntry::count();

        // Financial ledger invariant: zero journal entries modified or created by complaints
        $this->assertEquals($initialJournalsCount, $finalJournalsCount);
    }

    public function test_bengali_locale_renders_cleanly_without_missing_keys(): void
    {
        app()->setLocale('bn');

        $complaint = $this->createComplaint([
            'subject' => 'পূজার সময় প্রসাদ বিতরণ সংক্রান্ত অভিযোগ',
            'description' => 'প্রসাদ বিতরণের স্থানে পর্যাপ্ত স্বেচ্ছাসেবক ছিল না।',
            'admin_response' => 'আপনার মতামতের জন্য ধন্যবাদ। আগামী অনুষ্ঠানে স্বেচ্ছাসেবক সংখ্যা বৃদ্ধি করা হবে।',
            'status' => Complaint::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('portal.complaints.show', $complaint));
        $response->assertStatus(200);
        $response->assertSee('পূজার সময় প্রসাদ বিতরণ সংক্রান্ত অভিযোগ');
        $response->assertDontSee('portal.complaints_module.title');
    }
}
