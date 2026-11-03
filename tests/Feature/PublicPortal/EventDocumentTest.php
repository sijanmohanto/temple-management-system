<?php

namespace Tests\Feature\PublicPortal;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventDocument;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $adminUser;

    protected User $devoteeUser;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('local');

        // Super Admin
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->roles()->attach(Role::where('slug', 'super_admin')->firstOrFail());

        // Admin User (has public portal and document permissions)
        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach(Role::where('slug', 'temple_admin')->firstOrFail());

        // Devotee User (no staff/admin permissions)
        $this->devoteeUser = User::factory()->create();
        $this->devoteeUser->roles()->attach(Role::where('slug', 'devotee')->firstOrFail());

        // Test event
        $category = EventCategory::firstOrCreate(
            ['slug' => 'puja'],
            ['name_en' => 'Puja', 'name_bn' => 'পূজা', 'is_active' => true]
        );

        $this->event = Event::create([
            'event_category_id' => $category->id,
            'name_en' => 'Durga Puja 2026',
            'name_bn' => 'দুর্গাপূজা ২০২৬',
            'event_code' => 'EVT-DP-2026',
            'event_year' => 2026,
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-20',
            'status' => Event::STATUS_ACTIVE,
            'slug' => 'durga-puja-2026',
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_guest_cannot_access_event_documents_admin_routes(): void
    {
        $response = $this->get(route('portal.documents.index'));
        $response->assertRedirect(route('login'));

        $response = $this->get(route('portal.documents.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_devotee_without_permission_cannot_access_event_documents_admin(): void
    {
        $response = $this->actingAs($this->devoteeUser)->get(route('portal.documents.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($this->devoteeUser)->get(route('portal.documents.create'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_event_documents_index_and_create_form(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('portal.documents.index'));
        $response->assertStatus(200);
        $response->assertSee(__('portal.event_documents'));

        $response = $this->actingAs($this->adminUser)->get(route('portal.documents.create'));
        $response->assertStatus(200);
        $response->assertSee(__('portal.documents.upload_document'));
    }

    public function test_datatable_server_side_response_strictly_has_no_row_number_column(): void
    {
        $file = UploadedFile::fake()->create('audit.pdf', 500, 'application/pdf');
        $storedPath = $file->store('private/event_documents', 'local');

        EventDocument::create([
            'event_id' => $this->event->id,
            'title_en' => 'Financial Audit Report 2026',
            'title_bn' => 'আর্থিক নিরীক্ষা প্রতিবেদন ২০২৬',
            'document_type' => EventDocument::TYPE_AUDIT_REPORT,
            'file_path' => $storedPath,
            'file_name' => 'audit.pdf',
            'file_size' => 500 * 1024,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'published_at' => now(),
            'published_by' => $this->adminUser->id,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('portal.documents.index'));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'draw',
            'recordsTotal',
            'recordsFiltered',
            'data' => [
                '*' => [
                    'document_title',
                    'event_name',
                    'document_type',
                    'file_info',
                    'publication_status',
                    'published_at',
                    'actions',
                ],
            ],
        ]);

        $firstRow = $response->json('data.0');
        $this->assertArrayNotHasKey('#', $firstRow);
        $this->assertArrayNotHasKey('sl', $firstRow);
        $this->assertArrayNotHasKey('id', $firstRow);
        $this->assertStringContainsString('Financial Audit Report 2026', $firstRow['document_title']);
    }

    public function test_filter_by_event_and_type_and_status(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $storedPath = $file->store('private/event_documents', 'local');

        EventDocument::create([
            'event_id' => $this->event->id,
            'title_en' => 'Audit Report',
            'document_type' => EventDocument::TYPE_AUDIT_REPORT,
            'file_path' => $storedPath,
            'file_name' => 'doc.pdf',
            'file_size' => 102400,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'uploaded_by' => $this->adminUser->id,
        ]);

        EventDocument::create([
            'event_id' => $this->event->id,
            'title_en' => 'Draft Supporting Doc',
            'document_type' => EventDocument::TYPE_SUPPORTING_DOCUMENT,
            'file_path' => $storedPath,
            'file_name' => 'doc.pdf',
            'file_size' => 102400,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_DRAFT,
            'uploaded_by' => $this->adminUser->id,
        ]);

        // Filter by audit_report
        $response = $this->actingAs($this->adminUser)->getJson(route('portal.documents.index', [
            'document_type' => EventDocument::TYPE_AUDIT_REPORT,
        ]));
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsFiltered'));

        // Filter by draft status
        $response = $this->actingAs($this->adminUser)->getJson(route('portal.documents.index', [
            'publication_status' => EventDocument::PUB_STATUS_DRAFT,
        ]));
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('recordsFiltered'));
    }

    public function test_authorized_user_can_create_document_with_file_upload(): void
    {
        $file = UploadedFile::fake()->create('resolution.pdf', 1024, 'application/pdf');

        $payload = [
            'event_id' => $this->event->id,
            'title_en' => 'Committee Resolution 2026',
            'title_bn' => 'কমিটি রেজোলিউশন ২০২৬',
            'document_type' => EventDocument::TYPE_SUPPORTING_DOCUMENT,
            'file' => $file,
            'description_en' => 'Resolution minutes for event budget approval',
            'description_bn' => 'বাজেট অনুমোদনের সিদ্ধান্তের কার্যবিবরণী',
            'publish_now' => '1',
        ];

        $response = $this->actingAs($this->adminUser)->post(route('portal.documents.store'), $payload);

        $document = EventDocument::where('title_en', 'Committee Resolution 2026')->first();
        $this->assertNotNull($document);
        $response->assertRedirect(route('portal.documents.show', $document));

        $this->assertEquals($this->event->id, $document->event_id);
        $this->assertEquals(EventDocument::PUB_STATUS_PUBLISHED, $document->publication_status);
        $this->assertNotNull($document->published_at);
        $this->assertEquals($this->adminUser->id, $document->published_by);
        $this->assertEquals($this->adminUser->id, $document->uploaded_by);
        $this->assertTrue(Storage::disk('local')->exists($document->file_path));
    }

    public function test_document_file_validation_rejects_disallowed_types_and_oversized_files(): void
    {
        // Disallowed file extension / mime (e.g. php script)
        $invalidFile = UploadedFile::fake()->create('malicious.php', 100, 'text/x-php');

        $response = $this->actingAs($this->adminUser)->post(route('portal.documents.store'), [
            'event_id' => $this->event->id,
            'title_en' => 'Malicious File',
            'document_type' => EventDocument::TYPE_OTHER,
            'file' => $invalidFile,
        ]);
        $response->assertSessionHasErrors('file');

        // Oversized file (> 10MB = 10240 KB)
        $oversizedFile = UploadedFile::fake()->create('huge.pdf', 11000, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->post(route('portal.documents.store'), [
            'event_id' => $this->event->id,
            'title_en' => 'Huge File',
            'document_type' => EventDocument::TYPE_OTHER,
            'file' => $oversizedFile,
        ]);
        $response->assertSessionHasErrors('file');
    }

    public function test_authorized_user_can_edit_document_metadata_without_replacing_file(): void
    {
        $file = UploadedFile::fake()->create('statement.pdf', 200, 'application/pdf');
        $storedPath = $file->store('private/event_documents', 'local');

        $document = EventDocument::create([
            'event_id' => $this->event->id,
            'title_en' => 'Old Title',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'file_path' => $storedPath,
            'file_name' => 'statement.pdf',
            'file_size' => 200 * 1024,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_DRAFT,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('portal.documents.update', $document), [
            'event_id' => $this->event->id,
            'title_en' => 'Updated Financial Summary',
            'title_bn' => 'হালনাগাদকৃত আর্থিক সারসংক্ষেপ',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'description_en' => 'Updated description text',
        ]);

        $response->assertRedirect(route('portal.documents.show', $document));
        $document->refresh();

        $this->assertEquals('Updated Financial Summary', $document->title_en);
        $this->assertEquals('হালনাগাদকৃত আর্থিক সারসংক্ষেপ', $document->title_bn);
        $this->assertEquals($storedPath, $document->file_path);
    }

    public function test_authorized_user_can_replace_document_file_during_edit(): void
    {
        $oldFile = UploadedFile::fake()->create('old_report.pdf', 200, 'application/pdf');
        $oldPath = $oldFile->store('private/event_documents', 'local');

        $document = EventDocument::create([
            'event_id' => $this->event->id,
            'title_en' => 'Audit Report',
            'document_type' => EventDocument::TYPE_AUDIT_REPORT,
            'file_path' => $oldPath,
            'file_name' => 'old_report.pdf',
            'file_size' => 200 * 1024,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_DRAFT,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $newFile = UploadedFile::fake()->create('new_revised_report.pdf', 350, 'application/pdf');

        $response = $this->actingAs($this->adminUser)->put(route('portal.documents.update', $document), [
            'event_id' => $this->event->id,
            'title_en' => 'Audit Report (Revised)',
            'document_type' => EventDocument::TYPE_AUDIT_REPORT,
            'file' => $newFile,
        ]);

        $response->assertRedirect(route('portal.documents.show', $document));
        $document->refresh();

        $this->assertEquals('new_revised_report.pdf', $document->file_name);
        $this->assertNotEquals($oldPath, $document->file_path);
        $this->assertFalse(Storage::disk('local')->exists($oldPath));
        $this->assertTrue(Storage::disk('local')->exists($document->file_path));
    }

    public function test_authorized_user_can_download_and_preview_document(): void
    {
        $file = UploadedFile::fake()->create('statement.pdf', 150, 'application/pdf');
        $storedPath = $file->store('private/event_documents', 'local');

        $document = EventDocument::create([
            'event_id' => $this->event->id,
            'title_en' => 'Account Statement',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'file_path' => $storedPath,
            'file_name' => 'statement.pdf',
            'file_size' => 150 * 1024,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'uploaded_by' => $this->adminUser->id,
        ]);

        // Download attachment response
        $response = $this->actingAs($this->adminUser)->get(route('portal.documents.download', $document));
        $response->assertStatus(200);

        // Preview inline response
        $previewResponse = $this->actingAs($this->adminUser)->get(route('portal.documents.download', [
            'document' => $document,
            'preview' => 1,
        ]));
        $previewResponse->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $previewResponse->headers->get('content-type'));
    }

    public function test_authorized_user_can_publish_and_unpublish_document(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $storedPath = $file->store('private/event_documents', 'local');

        $document = EventDocument::create([
            'event_id' => $this->event->id,
            'title_en' => 'Annual Statement',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'file_path' => $storedPath,
            'file_name' => 'doc.pdf',
            'file_size' => 102400,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_DRAFT,
            'uploaded_by' => $this->adminUser->id,
        ]);

        // Publish
        $response = $this->actingAs($this->adminUser)->post(route('portal.documents.publish', $document));
        $document->refresh();
        $this->assertEquals(EventDocument::PUB_STATUS_PUBLISHED, $document->publication_status);
        $this->assertNotNull($document->published_at);
        $this->assertEquals($this->adminUser->id, $document->published_by);

        // Unpublish
        $response = $this->actingAs($this->adminUser)->post(route('portal.documents.unpublish', $document));
        $document->refresh();
        $this->assertEquals(EventDocument::PUB_STATUS_UNPUBLISHED, $document->publication_status);
        $this->assertNotNull($document->unpublished_at);
        $this->assertEquals($this->adminUser->id, $document->unpublished_by);
    }

    public function test_user_without_publish_permission_cannot_publish_document(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $storedPath = $file->store('private/event_documents', 'local');

        $document = EventDocument::create([
            'event_id' => $this->event->id,
            'title_en' => 'Annual Statement',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'file_path' => $storedPath,
            'file_name' => 'doc.pdf',
            'file_size' => 102400,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_DRAFT,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->devoteeUser)->post(route('portal.documents.publish', $document));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_soft_delete_document(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');
        $storedPath = $file->store('private/event_documents', 'local');

        $document = EventDocument::create([
            'event_id' => $this->event->id,
            'title_en' => 'To be deleted',
            'document_type' => EventDocument::TYPE_OTHER,
            'file_path' => $storedPath,
            'file_name' => 'doc.pdf',
            'file_size' => 102400,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_DRAFT,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('portal.documents.destroy', $document));
        $response->assertRedirect(route('portal.documents.index'));

        $this->assertSoftDeleted('event_documents', ['id' => $document->id]);
    }

    public function test_bengali_locale_renders_cleanly_without_missing_keys(): void
    {
        app()->setLocale('bn');

        $file = UploadedFile::fake()->create('audit_bn.pdf', 100, 'application/pdf');
        $storedPath = $file->store('private/event_documents', 'local');

        $document = EventDocument::create([
            'event_id' => $this->event->id,
            'title_en' => 'Audit Report 2026',
            'title_bn' => 'নিরীক্ষা প্রতিবেদন ২০২৬',
            'document_type' => EventDocument::TYPE_AUDIT_REPORT,
            'file_path' => $storedPath,
            'file_name' => 'audit_bn.pdf',
            'file_size' => 102400,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('portal.documents.show', $document));
        $response->assertStatus(200);
        $response->assertSee('নিরীক্ষা প্রতিবেদন ২০২৬');
        $response->assertDontSee('portal.documents.title');
    }

    public function test_event_selection_shows_event_name_and_category_in_create_and_edit_form(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('portal.documents.create'));
        $response->assertStatus(200);
        $response->assertSee('Durga Puja 2026');
        $response->assertSee('Puja');
        $response->assertSee('2026');

        $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');
        $storedPath = $file->store('private/event_documents', 'local');
        $document = EventDocument::create([
            'event_id' => $this->event->id,
            'title_en' => 'Financial Report 2026',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'file_path' => $storedPath,
            'file_name' => 'report.pdf',
            'file_size' => 102400,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $editResponse = $this->actingAs($this->adminUser)->get(route('portal.documents.edit', $document));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Durga Puja 2026');
        $editResponse->assertSee('Puja');
    }

    public function test_admin_can_upload_multiple_reports_for_one_event(): void
    {
        $file1 = UploadedFile::fake()->create('income_expense.pdf', 200, 'application/pdf');
        $file2 = UploadedFile::fake()->create('audit_statement.pdf', 300, 'application/pdf');

        $response1 = $this->actingAs($this->adminUser)->post(route('portal.documents.store'), [
            'event_id' => $this->event->id,
            'title_en' => 'Complete Income & Expense Financial Statement',
            'title_bn' => 'পূর্ণাঙ্গ আয়-ব্যয়ের হিসাব',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'description_en' => 'Total income, total expenses, and balance',
            'publication_status' => 'published',
            'file' => $file1,
        ]);
        $response1->assertRedirect();

        $response2 = $this->actingAs($this->adminUser)->post(route('portal.documents.store'), [
            'event_id' => $this->event->id,
            'title_en' => 'Statutory Audit Report',
            'title_bn' => 'সংবিধিবদ্ধ নিরীক্ষা প্রতিবেদন',
            'document_type' => EventDocument::TYPE_AUDIT_REPORT,
            'description_en' => 'Audited report of accounts',
            'publication_status' => 'unpublished',
            'file' => $file2,
        ]);
        $response2->assertRedirect();

        $this->assertEquals(2, EventDocument::where('event_id', $this->event->id)->count());

        $doc1 = EventDocument::where('title_en', 'Complete Income & Expense Financial Statement')->first();
        $this->assertTrue($doc1->isPublished());

        $doc2 = EventDocument::where('title_en', 'Statutory Audit Report')->first();
        $this->assertTrue($doc2->isDraft() || $doc2->isUnpublished());
    }
}
