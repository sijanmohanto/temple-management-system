<?php

namespace Tests\Feature\PublicPortal;

use App\Models\CollectionPayment;
use App\Models\Complaint;
use App\Models\ComplaintActivity;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Person;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DonorComplaintTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $donorUserA;

    protected Person $donorPersonA;

    protected User $donorUserB;

    protected Person $donorPersonB;

    protected Event $event;

    protected CollectionPayment $paymentA;

    protected CollectionPayment $paymentB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');
        Storage::fake('public');

        $this->superAdmin = User::factory()->create(['status' => 'active']);
        $this->superAdmin->assignRole('super_admin');

        // Donor A
        $this->donorPersonA = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Subir Ghosh',
            'name_bn' => 'সুবীর ঘোষ',
            'phone' => '01811000001',
            'email' => 'subir@example.com',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->donorUserA = User::factory()->create([
            'name' => 'Subir Ghosh',
            'phone' => '01811000001',
            'email' => 'subir@example.com',
            'status' => 'active',
        ]);
        $this->donorUserA->assignRole('devotee');

        // Donor B
        $this->donorPersonB = Person::create([
            'person_code' => Person::generatePersonCode(),
            'name_en' => 'Tapash Paul',
            'name_bn' => 'তাপস পাল',
            'phone' => '01811000002',
            'email' => 'tapash@example.com',
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->donorUserB = User::factory()->create([
            'name' => 'Tapash Paul',
            'phone' => '01811000002',
            'email' => 'tapash@example.com',
            'status' => 'active',
        ]);
        $this->donorUserB->assignRole('devotee');

        $category = EventCategory::firstOrCreate(
            ['slug' => 'puja'],
            ['name_en' => 'Puja', 'name_bn' => 'পূজা', 'status' => 'active']
        );

        $this->event = Event::create([
            'event_category_id' => $category->id,
            'name_en' => 'Durga Puja 2026',
            'name_bn' => 'দুর্গাপূজা ২০২৬',
            'slug' => 'durga-puja-2026',
            'event_code' => 'EVT-DP-2026',
            'event_year' => 2026,
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-20',
            'status' => Event::STATUS_ACTIVE,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->paymentA = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0001',
            'payer_person_id' => $this->donorPersonA->id,
            'collection_type' => 'event_pledge',
            'event_id' => $this->event->id,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRXA001',
            'amount' => 5000.00,
            'status' => CollectionPayment::STATUS_PENDING,
            'is_public_submission' => true,
            'payment_date' => '2026-10-15',
            'payment_year' => 2026,
            'submitted_by' => $this->donorUserA->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->paymentB = CollectionPayment::create([
            'payment_code' => 'PAY-2026-0002',
            'payer_person_id' => $this->donorPersonB->id,
            'collection_type' => 'event_pledge',
            'event_id' => $this->event->id,
            'payment_method' => 'bkash',
            'transaction_reference' => 'TRXB002',
            'amount' => 3000.00,
            'status' => CollectionPayment::STATUS_PENDING,
            'is_public_submission' => true,
            'payment_date' => '2026-10-15',
            'payment_year' => 2026,
            'submitted_by' => $this->donorUserB->id,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_or_denied(): void
    {
        $this->get(route('public.complaints.index'))->assertRedirect(route('login'));
        $this->get(route('public.complaints.create'))->assertRedirect(route('login'));
        $this->post(route('public.complaints.store'), [])->assertStatus(403);
        $this->get(route('public.complaints.show', 'CMP-2026-TEST0001'))->assertRedirect(route('login'));
    }

    public function test_donor_can_view_own_complaints_list(): void
    {
        Complaint::create([
            'tracking_number' => 'CMP-2026-AAAAAAAA',
            'complainant_name' => $this->donorPersonA->name_en,
            'complainant_phone' => $this->donorPersonA->phone,
            'person_id' => $this->donorPersonA->id,
            'category' => Complaint::CATEGORY_PAYMENT_NOT_UPDATED,
            'subject' => 'Payment still pending',
            'description' => 'I paid BDT 5000 yesterday via bKash but status is pending.',
            'status' => Complaint::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($this->donorUserA)->get(route('public.complaints.index'));
        $response->assertOk();
        $response->assertSee('CMP-2026-AAAAAAAA');
        $response->assertSee('Payment still pending');
    }

    public function test_donor_can_submit_valid_complaint_with_correct_reference_format(): void
    {
        $file = UploadedFile::fake()->create('proof.png', 500, 'image/png');

        $response = $this->actingAs($this->donorUserA)->post(route('public.complaints.store'), [
            'complaint_type' => 'payment_not_updated',
            'payment_code' => $this->paymentA->payment_code,
            'event_id' => $this->event->id,
            'description' => 'My payment has not been verified yet even after 48 hours. Please check transaction TRXA001.',
            'attachment' => $file,
        ]);
        $response->assertSessionHasNoErrors();

        $complaint = Complaint::where('person_id', $this->donorPersonA->id)->first();
        $this->assertNotNull($complaint);

        // Verify format CMP-YYYY-XXXXXXXX (8 random alphanumeric uppercase chars)
        $this->assertMatchesRegularExpression('/^CMP-\d{4}-[A-Z0-9]{8}$/', $complaint->tracking_number);
        $this->assertEquals('payment_not_updated', $complaint->category);
        $this->assertEquals($this->paymentA->id, $complaint->collection_payment_id);
        $this->assertEquals(Complaint::STATUS_SUBMITTED, $complaint->status);

        // Verify initial ComplaintActivity
        $activity = ComplaintActivity::where('complaint_id', $complaint->id)->first();
        $this->assertNotNull($activity);
        $this->assertEquals(ComplaintActivity::VISIBILITY_DONOR, $activity->visibility);
        $this->assertEquals(ComplaintActivity::TYPE_SUBMISSION, $activity->activity_type);

        $response->assertRedirect(route('public.complaints.show', $complaint->tracking_number));
        $response->assertSessionHas('success');
    }

    public function test_invalid_complaint_data_is_rejected(): void
    {
        // Description too short (< 5 chars)
        $responseShort = $this->actingAs($this->donorUserA)->post(route('public.complaints.store'), [
            'complaint_type' => 'payment_not_updated',
            'description' => 'Hey',
        ]);
        $responseShort->assertSessionHasErrors('description');

        // Invalid category
        $responseBadCategory = $this->actingAs($this->donorUserA)->post(route('public.complaints.store'), [
            'complaint_type' => 'invalid_category_xyz',
            'description' => 'Valid description here but invalid category.',
        ]);
        $responseBadCategory->assertSessionHasErrors('complaint_type');
    }

    public function test_donor_cannot_attach_another_donors_payment(): void
    {
        // Donor A attempts to submit a complaint attaching Donor B's payment
        $response = $this->actingAs($this->donorUserA)->post(route('public.complaints.store'), [
            'complaint_type' => 'wrong_paid_amount',
            'payment_code' => $this->paymentB->payment_code, // Belongs to Donor B
            'description' => 'Trying to report an issue for another person\'s payment.',
        ]);

        $response->assertSessionHasErrors('payment_code');
        $this->assertEquals(0, Complaint::count());
    }

    public function test_donor_can_view_own_complaint_details_and_timeline(): void
    {
        $complaint = Complaint::create([
            'tracking_number' => 'CMP-2026-BBBBBBBB',
            'complainant_name' => $this->donorPersonA->name_en,
            'complainant_phone' => $this->donorPersonA->phone,
            'person_id' => $this->donorPersonA->id,
            'collection_payment_id' => $this->paymentA->id,
            'category' => Complaint::CATEGORY_WRONG_PAID_AMOUNT,
            'subject' => 'Wrong paid amount recorded',
            'description' => 'I paid BDT 5000 but statement shows 4000.',
            'status' => Complaint::STATUS_SUBMITTED,
        ]);

        ComplaintActivity::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->donorUserA->id,
            'activity_type' => ComplaintActivity::TYPE_SUBMISSION,
            'content' => 'I paid BDT 5000 but statement shows 4000.',
            'visibility' => ComplaintActivity::VISIBILITY_DONOR,
        ]);

        $response = $this->actingAs($this->donorUserA)->get(route('public.complaints.show', $complaint->tracking_number));
        $response->assertOk();
        $response->assertSee('CMP-2026-BBBBBBBB');
        $response->assertSee('I paid BDT 5000 but statement shows 4000.');
    }

    public function test_cross_donor_cannot_view_another_donors_complaint(): void
    {
        $complaintA = Complaint::create([
            'tracking_number' => 'CMP-2026-CCCCCCCC',
            'complainant_name' => $this->donorPersonA->name_en,
            'complainant_phone' => $this->donorPersonA->phone,
            'person_id' => $this->donorPersonA->id,
            'category' => Complaint::CATEGORY_OTHER,
            'subject' => 'General Inquiry',
            'description' => 'Private inquiry from Donor A.',
            'status' => Complaint::STATUS_SUBMITTED,
        ]);

        // Donor B tries to view Donor A's complaint
        $response = $this->actingAs($this->donorUserB)->get(route('public.complaints.show', $complaintA->tracking_number));
        $response->assertStatus(403);
    }

    public function test_admin_replies_are_visible_to_donor_while_internal_notes_are_strictly_hidden(): void
    {
        $complaint = Complaint::create([
            'tracking_number' => 'CMP-2026-DDDDDDDD',
            'complainant_name' => $this->donorPersonA->name_en,
            'complainant_phone' => $this->donorPersonA->phone,
            'person_id' => $this->donorPersonA->id,
            'category' => Complaint::CATEGORY_PAYMENT_NOT_UPDATED,
            'subject' => 'Payment update delay',
            'description' => 'Please update my payment status.',
            'status' => Complaint::STATUS_REPLIED,
            'admin_response' => 'We have verified your transaction and approved the payment.',
            'internal_notes' => 'SECRET_INTERNAL_NOTE: Check whether bank reconciled before approving.',
        ]);

        // Public admin reply activity
        ComplaintActivity::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->superAdmin->id,
            'activity_type' => ComplaintActivity::TYPE_REPLY,
            'content' => 'We have verified your transaction and approved the payment.',
            'visibility' => ComplaintActivity::VISIBILITY_DONOR,
        ]);

        // Secret internal staff note activity
        ComplaintActivity::create([
            'complaint_id' => $complaint->id,
            'actor_id' => $this->superAdmin->id,
            'activity_type' => ComplaintActivity::TYPE_INTERNAL_NOTE,
            'content' => 'SECRET_INTERNAL_NOTE: Check whether bank reconciled before approving.',
            'visibility' => ComplaintActivity::VISIBILITY_INTERNAL,
        ]);

        $response = $this->actingAs($this->donorUserA)->get(route('public.complaints.show', $complaint->tracking_number));
        $response->assertOk();

        // Admin reply MUST be visible
        $response->assertSee('We have verified your transaction and approved the payment.');

        // Internal note MUST NEVER be visible to donor
        $response->assertDontSee('SECRET_INTERNAL_NOTE');
        $response->assertDontSee('Check whether bank reconciled before approving.');
    }

    public function test_anti_duplicate_submission_is_prevented(): void
    {
        $desc = 'Exact duplicate text submitted multiple times rapidly.';

        $this->actingAs($this->donorUserA)->post(route('public.complaints.store'), [
            'complaint_type' => 'other',
            'description' => $desc,
        ])->assertSessionHasNoErrors();

        // Second immediate submission with identical description
        $response2 = $this->actingAs($this->donorUserA)->post(route('public.complaints.store'), [
            'complaint_type' => 'other',
            'description' => $desc,
        ]);

        $response2->assertSessionHasErrors('description');
        $this->assertEquals(1, Complaint::count());
    }

    public function test_attachment_stored_in_private_storage_and_downloadable_by_owner(): void
    {
        $file = UploadedFile::fake()->create('proof_slip.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->donorUserA)->post(route('public.complaints.store'), [
            'complaint_type' => 'other',
            'description' => 'Attached proof slip for verification.',
            'attachment' => $file,
        ]);
        $response->assertSessionHasNoErrors();

        $complaint = Complaint::where('person_id', $this->donorPersonA->id)->latest('id')->first();
        $this->assertNotNull($complaint->attachment_path);

        // Assert file exists on private 'local' disk, NOT on 'public' disk
        Storage::disk('local')->assertExists($complaint->attachment_path);
        Storage::disk('public')->assertMissing($complaint->attachment_path);

        // Owner donor can download attachment
        $downloadRes = $this->actingAs($this->donorUserA)->get(route('public.complaints.attachment', $complaint->tracking_number));
        $downloadRes->assertOk();

        // Admin with permission can also download
        $adminRes = $this->actingAs($this->superAdmin)->get(route('public.complaints.attachment', $complaint->tracking_number));
        $adminRes->assertOk();
    }

    public function test_cross_donor_cannot_download_another_donors_attachment(): void
    {
        $path = 'complaints/private_doc.pdf';
        Storage::disk('local')->put($path, 'Confidential Donor A Content');

        $complaint = Complaint::create([
            'tracking_number' => 'CMP-2026-PRIV0001',
            'complainant_name' => $this->donorPersonA->name_en,
            'complainant_phone' => $this->donorPersonA->phone,
            'person_id' => $this->donorPersonA->id,
            'category' => Complaint::CATEGORY_OTHER,
            'subject' => 'Private inquiry',
            'description' => 'Inquiry with confidential attachment',
            'attachment_path' => $path,
            'status' => Complaint::STATUS_SUBMITTED,
        ]);

        // Donor B tries to download Donor A's attachment -> 403 Forbidden
        $response = $this->actingAs($this->donorUserB)->get(route('public.complaints.attachment', $complaint->tracking_number));
        $response->assertStatus(403);
    }

    public function test_guest_cannot_download_attachment(): void
    {
        $path = 'complaints/guest_blocked.pdf';
        Storage::disk('local')->put($path, 'Protected File Content');

        $complaint = Complaint::create([
            'tracking_number' => 'CMP-2026-PRIV0002',
            'complainant_name' => $this->donorPersonA->name_en,
            'complainant_phone' => $this->donorPersonA->phone,
            'person_id' => $this->donorPersonA->id,
            'category' => Complaint::CATEGORY_OTHER,
            'subject' => 'Test inquiry',
            'description' => 'Test description',
            'attachment_path' => $path,
            'status' => Complaint::STATUS_SUBMITTED,
        ]);

        $response = $this->get(route('public.complaints.attachment', $complaint->tracking_number));
        $response->assertRedirect(route('login'));
    }

    public function test_path_traversal_in_attachment_download_is_blocked(): void
    {
        $complaint = Complaint::create([
            'tracking_number' => 'CMP-2026-TRAV0001',
            'complainant_name' => $this->donorPersonA->name_en,
            'complainant_phone' => $this->donorPersonA->phone,
            'person_id' => $this->donorPersonA->id,
            'category' => Complaint::CATEGORY_OTHER,
            'subject' => 'Traversal test',
            'description' => 'Testing traversal protection',
            'attachment_path' => '../../../../etc/passwd',
            'status' => Complaint::STATUS_SUBMITTED,
        ]);

        $response = $this->actingAs($this->donorUserA)->get(route('public.complaints.attachment', $complaint->tracking_number));
        $response->assertStatus(400);
    }

    public function test_event_payment_mismatch_is_rejected(): void
    {
        $event2 = Event::create([
            'event_category_id' => $this->event->event_category_id,
            'name_en' => 'Kali Puja 2026',
            'slug' => 'kali-puja-2026',
            'event_code' => 'EVT-KP-2026',
            'event_year' => 2026,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-15',
            'status' => Event::STATUS_ACTIVE,
            'is_published' => true,
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->superAdmin->id,
        ]);

        // Payment A belongs to $this->event. Selecting $event2 must be rejected.
        $response = $this->actingAs($this->donorUserA)->post(route('public.complaints.store'), [
            'complaint_type' => 'payment_not_updated',
            'payment_code' => $this->paymentA->payment_code,
            'event_id' => $event2->id,
            'description' => 'Mismatched event complaint should fail.',
        ]);

        $response->assertSessionHasErrors('event_id');
    }

    public function test_draft_or_unpublished_event_is_rejected(): void
    {
        $draftEvent = Event::create([
            'event_category_id' => $this->event->event_category_id,
            'name_en' => 'Draft Event 2026',
            'slug' => 'draft-event-2026',
            'event_code' => 'EVT-DRF-2026',
            'event_year' => 2026,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-05',
            'status' => Event::STATUS_DRAFT,
            'is_published' => false,
            'publication_status' => Event::PUB_STATUS_DRAFT,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->donorUserA)->post(route('public.complaints.store'), [
            'complaint_type' => 'other',
            'event_id' => $draftEvent->id,
            'description' => 'Attempting to report for draft event.',
        ]);

        $response->assertSessionHasErrors('event_id');
    }

    public function test_anti_duplicate_check_is_scoped_to_same_donor_only(): void
    {
        $text = 'Common generic complaint text submitted by different donors.';

        // Donor A submits
        $resA = $this->actingAs($this->donorUserA)->post(route('public.complaints.store'), [
            'complaint_type' => 'other',
            'description' => $text,
        ]);
        $resA->assertSessionHasNoErrors();

        // Donor B submits exact same text immediately -> Must succeed because donor is different
        $resB = $this->actingAs($this->donorUserB)->post(route('public.complaints.store'), [
            'complaint_type' => 'other',
            'description' => $text,
        ]);
        $resB->assertSessionHasNoErrors();
        $this->assertEquals(2, Complaint::where('description', $text)->count());
    }
}
