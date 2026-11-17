<?php

namespace Tests\Feature\PublicPortal;

use App\Models\Complaint;
use App\Models\ComplaintActivity;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminComplaintManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $adminUser;

    protected User $staffUser;

    protected User $devoteeUser;

    protected Event $event;

    protected Person $donor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        // Super Admin
        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->assignRole('super_admin');

        // Temple Admin
        $this->adminUser = User::factory()->create(['status' => 'active']);
        $this->adminUser->assignRole('temple_admin');

        // Staff user for assignment
        $this->staffUser = User::factory()->create(['name' => 'Officer Roy', 'status' => 'active']);
        $this->staffUser->assignRole('temple_admin');

        // Devotee User
        $this->devoteeUser = User::factory()->create(['status' => 'active']);
        $this->devoteeUser->assignRole('devotee');

        $this->donor = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Gouranga Das',
            'phone' => '01711223344',
            'email' => 'gouranga@example.com',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $category = EventCategory::firstOrCreate(
            ['slug' => 'puja'],
            ['name_en' => 'Puja', 'name_bn' => 'পূজা', 'status' => 'active']
        );

        $this->event = Event::create([
            'event_category_id' => $category->id,
            'name_en' => 'Kali Puja 2026',
            'name_bn' => 'শ্যামাপূজা ২০২৬',
            'slug' => 'kali-puja-2026',
            'event_code' => 'EVT-KP-2026',
            'event_year' => 2026,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-15',
            'status' => Event::STATUS_ACTIVE,
            'is_published' => true,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    protected function createComplaint(array $overrides = []): Complaint
    {
        return Complaint::create(array_merge([
            'tracking_number' => Complaint::generateTrackingNumber(),
            'complainant_name' => $this->donor->name_en,
            'complainant_phone' => $this->donor->phone,
            'complainant_email' => $this->donor->email,
            'person_id' => $this->donor->id,
            'event_id' => $this->event->id,
            'category' => Complaint::CATEGORY_PAYMENT_NOT_UPDATED,
            'subject' => 'Payment update delay',
            'description' => 'I made a payment of BDT 2000 but the status is still pending.',
            'status' => Complaint::STATUS_SUBMITTED,
            'priority' => Complaint::PRIORITY_MEDIUM,
        ], $overrides));
    }

    public function test_guest_cannot_access_admin_complaints(): void
    {
        $response = $this->get(route('portal.complaints.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_is_denied(): void
    {
        $response = $this->actingAs($this->devoteeUser)->get(route('portal.complaints.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_admin_can_view_complaints_datatable_strictly_without_hash_column(): void
    {
        $complaint = $this->createComplaint();

        $response = $this->actingAs($this->adminUser)->getJson(route('portal.complaints.index'));
        $response->assertOk();
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

        $data = $response->json('data.0');
        // Strictly verify that NO '#' or numeric serial column is exposed
        $this->assertArrayNotHasKey('#', $data);
        $this->assertArrayNotHasKey('id', $data);
        $this->assertArrayNotHasKey('sl', $data);
        $this->assertArrayNotHasKey('serial', $data);
    }

    public function test_admin_can_filter_complaints_by_status_category_and_event(): void
    {
        $submitted = $this->createComplaint([
            'status' => Complaint::STATUS_SUBMITTED,
            'category' => Complaint::CATEGORY_PAYMENT_NOT_UPDATED,
        ]);

        $resolved = $this->createComplaint([
            'status' => Complaint::STATUS_RESOLVED,
            'category' => Complaint::CATEGORY_WRONG_PAID_AMOUNT,
        ]);

        // Filter status = submitted
        $resStatus = $this->actingAs($this->adminUser)->getJson(route('portal.complaints.index', ['status' => 'submitted']));
        $resStatus->assertOk();
        $this->assertEquals(1, $resStatus->json('recordsFiltered'));
        $this->assertStringContainsString($submitted->tracking_number, $resStatus->json('data.0.tracking_info'));

        // Filter category = wrong_paid_amount
        $resCat = $this->actingAs($this->adminUser)->getJson(route('portal.complaints.index', ['category' => 'wrong_paid_amount']));
        $resCat->assertOk();
        $this->assertEquals(1, $resCat->json('recordsFiltered'));
        $this->assertStringContainsString($resolved->tracking_number, $resCat->json('data.0.tracking_info'));
    }

    public function test_admin_can_assign_complaint_to_staff(): void
    {
        $complaint = $this->createComplaint();

        $response = $this->actingAs($this->adminUser)->post(route('portal.complaints.assign', $complaint), [
            'assigned_to' => $this->staffUser->id,
        ]);

        $response->assertRedirect();
        $complaint->refresh();
        $this->assertEquals($this->staffUser->id, $complaint->assigned_to);

        // Verify ComplaintActivity created
        $activity = ComplaintActivity::where('complaint_id', $complaint->id)
            ->where('activity_type', ComplaintActivity::TYPE_ASSIGNMENT)
            ->first();
        $this->assertNotNull($activity);
        $this->assertStringContainsString($this->staffUser->name, $activity->content);
    }

    public function test_admin_can_mark_complaint_under_review(): void
    {
        $complaint = $this->createComplaint(['status' => Complaint::STATUS_SUBMITTED]);

        $response = $this->actingAs($this->adminUser)->post(route('portal.complaints.under-review', $complaint));
        $response->assertRedirect();

        $complaint->refresh();
        $this->assertEquals(Complaint::STATUS_UNDER_REVIEW, $complaint->status);

        $activity = ComplaintActivity::where('complaint_id', $complaint->id)
            ->where('new_status', Complaint::STATUS_UNDER_REVIEW)
            ->first();
        $this->assertNotNull($activity);
        $this->assertEquals(ComplaintActivity::VISIBILITY_DONOR, $activity->visibility);
    }

    public function test_admin_can_submit_reply_to_donor(): void
    {
        $complaint = $this->createComplaint(['status' => Complaint::STATUS_UNDER_REVIEW]);

        $response = $this->actingAs($this->adminUser)->post(route('portal.complaints.respond', $complaint), [
            'admin_response' => 'We are coordinating with our bank to confirm your payment slip.',
        ]);

        $response->assertRedirect();
        $complaint->refresh();
        $this->assertEquals(Complaint::STATUS_REPLIED, $complaint->status);
        $this->assertEquals('We are coordinating with our bank to confirm your payment slip.', $complaint->admin_response);

        // Activity with donor visibility
        $activity = ComplaintActivity::where('complaint_id', $complaint->id)
            ->where('activity_type', ComplaintActivity::TYPE_REPLY)
            ->first();
        $this->assertNotNull($activity);
        $this->assertEquals(ComplaintActivity::VISIBILITY_DONOR, $activity->visibility);
    }

    public function test_admin_can_resolve_complaint_with_mandatory_note(): void
    {
        $complaint = $this->createComplaint();

        // 1. Without resolution note -> fails
        $responseFail = $this->actingAs($this->adminUser)->post(route('portal.complaints.resolve', $complaint), [
            'admin_response' => '',
            'note' => '',
        ]);
        $responseFail->assertSessionHasErrors('note');

        // 2. With resolution note -> succeeds
        $responseSuccess = $this->actingAs($this->adminUser)->post(route('portal.complaints.resolve', $complaint), [
            'admin_response' => 'Payment has been located and officially verified with receipt issued.',
        ]);
        $responseSuccess->assertRedirect();

        $complaint->refresh();
        $this->assertEquals(Complaint::STATUS_RESOLVED, $complaint->status);
        $this->assertNotNull($complaint->resolved_at);
        $this->assertEquals($this->adminUser->id, $complaint->resolved_by);

        $activity = ComplaintActivity::where('complaint_id', $complaint->id)
            ->where('activity_type', ComplaintActivity::TYPE_RESOLUTION)
            ->first();
        $this->assertNotNull($activity);
        $this->assertEquals(ComplaintActivity::VISIBILITY_DONOR, $activity->visibility);
    }

    public function test_admin_can_reject_complaint_with_mandatory_reason(): void
    {
        $complaint = $this->createComplaint();

        // 1. Without reason -> fails
        $responseFail = $this->actingAs($this->adminUser)->post(route('portal.complaints.reject', $complaint), [
            'rejection_reason' => '',
        ]);
        $responseFail->assertSessionHasErrors('rejection_reason');

        // 2. With reason -> succeeds
        $responseSuccess = $this->actingAs($this->adminUser)->post(route('portal.complaints.reject', $complaint), [
            'rejection_reason' => 'Transaction ID does not match any incoming funds on our bank statement.',
        ]);
        $responseSuccess->assertRedirect();

        $complaint->refresh();
        $this->assertEquals(Complaint::STATUS_REJECTED, $complaint->status);

        $activity = ComplaintActivity::where('complaint_id', $complaint->id)
            ->where('activity_type', ComplaintActivity::TYPE_REJECTION)
            ->first();
        $this->assertNotNull($activity);
        $this->assertEquals(ComplaintActivity::VISIBILITY_DONOR, $activity->visibility);
    }

    public function test_terminal_status_immutability(): void
    {
        $resolved = $this->createComplaint([
            'status' => Complaint::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => $this->adminUser->id,
        ]);

        // Attempt to mark under review
        $resReview = $this->actingAs($this->adminUser)->post(route('portal.complaints.under-review', $resolved));
        $resReview->assertSessionHas('error');
        $resolved->refresh();
        $this->assertEquals(Complaint::STATUS_RESOLVED, $resolved->status);

        // Attempt to respond / change status
        $resReply = $this->actingAs($this->adminUser)->post(route('portal.complaints.respond', $resolved), [
            'admin_response' => 'Attempting to change status of resolved complaint',
        ]);
        $resReply->assertSessionHasErrors('status');
        $resolved->refresh();
        $this->assertEquals(Complaint::STATUS_RESOLVED, $resolved->status);
    }

    public function test_admin_can_add_private_internal_notes_with_isolated_visibility(): void
    {
        $complaint = $this->createComplaint();

        $response = $this->actingAs($this->adminUser)->post(route('portal.complaints.add-note', $complaint), [
            'internal_notes' => 'Internal discussion: officer checking physical bank register.',
        ]);

        $response->assertRedirect();
        $complaint->refresh();
        $this->assertStringContainsString('Internal discussion: officer checking physical bank register.', $complaint->internal_notes);

        $activity = ComplaintActivity::where('complaint_id', $complaint->id)
            ->where('activity_type', ComplaintActivity::TYPE_INTERNAL_NOTE)
            ->first();
        $this->assertNotNull($activity);
        $this->assertEquals(ComplaintActivity::VISIBILITY_INTERNAL, $activity->visibility);
    }

    public function test_assignment_rejects_ordinary_devotee(): void
    {
        $complaint = $this->createComplaint();

        $response = $this->actingAs($this->adminUser)->post(route('portal.complaints.assign', $complaint), [
            'assigned_to' => $this->devoteeUser->id,
        ]);

        $response->assertSessionHas('error');
        $complaint->refresh();
        $this->assertNull($complaint->assigned_to);
    }

    public function test_assignment_records_both_previous_and_new_assignee_in_activity_history(): void
    {
        $complaint = $this->createComplaint();

        $staffUserB = User::factory()->create(['name' => 'Officer Mukherjee', 'status' => 'active']);
        $staffUserB->assignRole('temple_admin');

        // Initial assignment to Officer Roy
        $this->actingAs($this->adminUser)->post(route('portal.complaints.assign', $complaint), [
            'assigned_to' => $this->staffUser->id,
        ]);

        // Reassignment to Officer Mukherjee
        $this->actingAs($this->adminUser)->post(route('portal.complaints.assign', $complaint), [
            'assigned_to' => $staffUserB->id,
        ]);

        $complaint->refresh();
        $this->assertEquals($staffUserB->id, $complaint->assigned_to);

        $latestAssignmentActivity = ComplaintActivity::where('complaint_id', $complaint->id)
            ->where('activity_type', ComplaintActivity::TYPE_ASSIGNMENT)
            ->latest('id')
            ->first();

        $this->assertNotNull($latestAssignmentActivity);
        $this->assertStringContainsString('Officer Roy', $latestAssignmentActivity->content);
        $this->assertStringContainsString('Officer Mukherjee', $latestAssignmentActivity->content);
    }

    public function test_admin_with_permission_can_download_attachment_and_unauthorized_user_is_denied(): void
    {
        Storage::fake('local');
        $path = 'complaints/admin_proof.pdf';
        Storage::disk('local')->put($path, 'Admin Proof Content');

        $complaint = $this->createComplaint(['attachment_path' => $path]);

        // Authorized admin can download
        $adminRes = $this->actingAs($this->adminUser)->get(route('portal.complaints.attachment', $complaint));
        $adminRes->assertOk();

        // Ordinary devotee without permission is denied
        $devoteeRes = $this->actingAs($this->devoteeUser)->get(route('portal.complaints.attachment', $complaint));
        $devoteeRes->assertStatus(403);
    }

    public function test_foreign_key_restricts_hard_delete_when_activity_history_exists(): void
    {
        $complaint = $this->createComplaint();

        ComplaintActivity::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->adminUser->id,
            'activity_type' => ComplaintActivity::TYPE_SUBMISSION,
            'content' => 'Initial submission',
            'visibility' => ComplaintActivity::VISIBILITY_DONOR,
        ]);

        // Soft deletion succeeds without error
        $complaint->delete();
        $this->assertSoftDeleted('complaints', ['id' => $complaint->id]);
        $this->assertDatabaseHas('complaint_activities', ['complaint_id' => $complaint->id]);

        // Direct SQL hard delete / forceDelete fails due to restrictOnDelete foreign key constraint
        $this->expectException(QueryException::class);
        $complaint->forceDelete();
    }
}
