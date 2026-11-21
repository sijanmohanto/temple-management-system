<?php

namespace Tests\Feature\PublicPortal;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventDocument;
use App\Models\EventSettlement;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PreviousEventArchiveTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected EventCategory $pujaCategory;

    protected EventCategory $culturalCategory;

    protected Event $closedEvent;

    protected Event $runningEvent;

    protected Event $unpublishedEvent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);
        Storage::fake('local');

        $this->adminUser = User::factory()->create();
        $this->adminUser->roles()->attach(Role::where('slug', 'temple_admin')->firstOrFail());

        $this->pujaCategory = EventCategory::firstOrCreate(
            ['slug' => 'puja'],
            ['name_en' => 'Puja & Worship', 'name_bn' => 'পূজা ও অর্চনা', 'status' => 'active']
        );

        $this->culturalCategory = EventCategory::firstOrCreate(
            ['slug' => 'cultural'],
            ['name_en' => 'Cultural Festival', 'name_bn' => 'সাংস্কৃতিক উৎসব', 'status' => 'active']
        );

        // Historical Closed Event (Published)
        $this->closedEvent = Event::create([
            'event_category_id' => $this->pujaCategory->id,
            'name_en' => 'Durga Puja 2025',
            'name_bn' => 'শারদীয় দুর্গোৎসব ২০২৫',
            'event_code' => 'EVT-DP-2025',
            'event_year' => 2025,
            'start_date' => '2025-10-01',
            'end_date' => '2025-10-05',
            'status' => Event::STATUS_CLOSED,
            'slug' => 'durga-puja-2025',
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'published_at' => now()->subYear(),
            'created_by' => $this->adminUser->id,
        ]);

        // Running Event (Published)
        $this->runningEvent = Event::create([
            'event_category_id' => $this->culturalCategory->id,
            'name_en' => 'Navabarsha 2026',
            'name_bn' => 'নববর্ষ উৎসব ২০২৬',
            'event_code' => 'EVT-NB-2026',
            'event_year' => 2026,
            'start_date' => '2026-04-14',
            'end_date' => '2026-04-15',
            'status' => Event::STATUS_ACTIVE,
            'slug' => 'navabarsha-2026',
            'publication_status' => Event::PUB_STATUS_PUBLISHED,
            'published_at' => now(),
            'created_by' => $this->adminUser->id,
        ]);

        // Unpublished / Draft Event
        $this->unpublishedEvent = Event::create([
            'event_category_id' => $this->pujaCategory->id,
            'name_en' => 'Secret Ceremony 2025',
            'name_bn' => 'গোপন অনুষ্ঠান ২০২৫',
            'event_code' => 'EVT-SEC-2025',
            'event_year' => 2025,
            'start_date' => '2025-12-01',
            'end_date' => '2025-12-02',
            'status' => Event::STATUS_CLOSED,
            'slug' => 'secret-ceremony-2025',
            'publication_status' => Event::PUB_STATUS_UNPUBLISHED,
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_public_archive_index_is_accessible_to_guests(): void
    {
        $response = $this->get(route('public.previous-events.index'));

        $response->assertStatus(200);
        $response->assertSee('Durga Puja 2025');
        $response->assertSee('Navabarsha 2026');
        $response->assertDontSee('Secret Ceremony 2025');
    }

    public function test_public_archive_shows_published_documents_count(): void
    {
        $file1 = UploadedFile::fake()->create('report1.pdf', 100, 'application/pdf');
        $path1 = $file1->store('private/event_documents', 'local');

        $file2 = UploadedFile::fake()->create('report2.pdf', 100, 'application/pdf');
        $path2 = $file2->store('private/event_documents', 'local');

        // Published Doc
        EventDocument::create([
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Audited Report 2025',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'report_category' => EventDocument::CATEGORY_COMPLETE_FINANCIAL_STATEMENT,
            'financial_year' => 2025,
            'file_path' => $path1,
            'file_name' => 'report1.pdf',
            'file_size' => 102400,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'published_at' => now(),
            'uploaded_by' => $this->adminUser->id,
        ]);

        // Unpublished Doc
        EventDocument::create([
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Draft Internal Notes',
            'document_type' => EventDocument::TYPE_SUPPORTING_DOCUMENT,
            'report_category' => EventDocument::CATEGORY_OTHER,
            'financial_year' => 2025,
            'file_path' => $path2,
            'file_name' => 'report2.pdf',
            'file_size' => 102400,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_UNPUBLISHED,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->get(route('public.previous-events.index'));
        $response->assertStatus(200);
        $response->assertSee('1 Official Report');
    }

    public function test_public_archive_filters_by_category_and_year(): void
    {
        // Category filter
        $catResponse = $this->get(route('public.previous-events.index', [
            'category_id' => $this->pujaCategory->id,
        ]));
        $catResponse->assertStatus(200);
        $catResponse->assertSee('Durga Puja 2025');
        $catResponse->assertDontSee('Navabarsha 2026');

        // Year filter
        $yearResponse = $this->get(route('public.previous-events.index', [
            'year' => 2026,
        ]));
        $yearResponse->assertStatus(200);
        $yearResponse->assertSee('Navabarsha 2026');
        $yearResponse->assertDontSee('Durga Puja 2025');

        // Search filter
        $searchResponse = $this->get(route('public.previous-events.index', [
            'search' => 'EVT-DP-2025',
        ]));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Durga Puja 2025');
        $searchResponse->assertDontSee('Navabarsha 2026');
    }

    public function test_public_show_displays_event_details_and_tabs(): void
    {
        $response = $this->get(route('public.previous-events.show', $this->closedEvent->slug));

        $response->assertStatus(200);
        $response->assertSee('Durga Puja 2025');
        $response->assertSee(__('portal.public.system_financial_summary'));
        $response->assertSee(__('portal.public.audited_statements_tab'));
    }

    public function test_public_show_displays_double_entry_ledger_income_and_expense_breakdown(): void
    {
        $cashAccount = FinancialAccount::where('account_code', FinancialAccount::CODE_CASH)->firstOrFail();
        $incomeAccount = FinancialAccount::where('account_type', FinancialAccount::TYPE_INCOME)->firstOrFail();
        $expenseAccount = FinancialAccount::where('account_type', FinancialAccount::TYPE_EXPENSE)->firstOrFail();

        // 1. Post Income Journal Entry (Cash Debit 50,000 / Income Credit 50,000)
        $incomeEntry = JournalEntry::create([
            'journal_number' => 'JV-2025-001',
            'entry_date' => '2025-10-02',
            'description' => 'Durga Puja 2025 Devotee Collections',
            'status' => JournalEntry::STATUS_POSTED,
            'event_id' => $this->closedEvent->id,
            'created_by' => $this->adminUser->id,
            'posted_by' => $this->adminUser->id,
            'posted_at' => now(),
        ]);

        JournalLine::create([
            'journal_entry_id' => $incomeEntry->id,
            'financial_account_id' => $cashAccount->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 50000.00,
            'event_id' => $this->closedEvent->id,
        ]);

        JournalLine::create([
            'journal_entry_id' => $incomeEntry->id,
            'financial_account_id' => $incomeAccount->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 50000.00,
            'event_id' => $this->closedEvent->id,
        ]);

        // 2. Post Expense Journal Entry (Expense Debit 20,000 / Cash Credit 20,000)
        $expenseEntry = JournalEntry::create([
            'journal_number' => 'JV-2025-002',
            'entry_date' => '2025-10-03',
            'description' => 'Durga Puja 2025 Lighting and Pandal Setup',
            'status' => JournalEntry::STATUS_POSTED,
            'event_id' => $this->closedEvent->id,
            'created_by' => $this->adminUser->id,
            'posted_by' => $this->adminUser->id,
            'posted_at' => now(),
        ]);

        JournalLine::create([
            'journal_entry_id' => $expenseEntry->id,
            'financial_account_id' => $expenseAccount->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 20000.00,
            'event_id' => $this->closedEvent->id,
        ]);

        JournalLine::create([
            'journal_entry_id' => $expenseEntry->id,
            'financial_account_id' => $cashAccount->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 20000.00,
            'event_id' => $this->closedEvent->id,
        ]);

        $response = $this->get(route('public.previous-events.show', $this->closedEvent->slug));
        $response->assertStatus(200);

        // Check metrics cards
        $response->assertSee('50,000.00'); // Total Income
        $response->assertSee('20,000.00'); // Total Expense
        $response->assertSee('30,000.00'); // Net Surplus (50,000 - 20,000)
        $response->assertSee($incomeAccount->name_en);
        $response->assertSee($expenseAccount->name_en);
    }

    public function test_opening_balances_and_fund_transfers_are_excluded_from_event_income_and_expenses(): void
    {
        $cashAccount = FinancialAccount::where('account_code', FinancialAccount::CODE_CASH)->firstOrFail();
        $bankAccount = FinancialAccount::where('account_code', FinancialAccount::CODE_BANK)->firstOrFail();
        $openingBalanceEquity = FinancialAccount::where('account_code', FinancialAccount::CODE_OPENING_EQUITY)->firstOrFail();
        $incomeAccount = FinancialAccount::where('account_type', FinancialAccount::TYPE_INCOME)->firstOrFail();

        // 1. Opening Balance Entry (Cash Debit 100,000 / Opening Balance Equity Credit 100,000)
        $openingEntry = JournalEntry::create([
            'journal_number' => 'JV-OP-001',
            'entry_date' => '2025-01-01',
            'description' => 'Opening Balance Setup',
            'status' => JournalEntry::STATUS_POSTED,
            'event_id' => $this->closedEvent->id,
            'created_by' => $this->adminUser->id,
            'posted_by' => $this->adminUser->id,
            'posted_at' => now(),
        ]);
        JournalLine::create([
            'journal_entry_id' => $openingEntry->id,
            'financial_account_id' => $cashAccount->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 100000.00,
            'event_id' => $this->closedEvent->id,
        ]);
        JournalLine::create([
            'journal_entry_id' => $openingEntry->id,
            'financial_account_id' => $openingBalanceEquity->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 100000.00,
            'event_id' => $this->closedEvent->id,
        ]);

        // 2. Fund Transfer between Liquid Asset Accounts (Bank Debit 30,000 / Cash Credit 30,000)
        $transferEntry = JournalEntry::create([
            'journal_number' => 'JV-TRF-001',
            'entry_date' => '2025-05-01',
            'description' => 'Liquid Asset Transfer',
            'status' => JournalEntry::STATUS_POSTED,
            'event_id' => $this->closedEvent->id,
            'created_by' => $this->adminUser->id,
            'posted_by' => $this->adminUser->id,
            'posted_at' => now(),
        ]);
        JournalLine::create([
            'journal_entry_id' => $transferEntry->id,
            'financial_account_id' => $bankAccount->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 30000.00,
            'event_id' => $this->closedEvent->id,
        ]);
        JournalLine::create([
            'journal_entry_id' => $transferEntry->id,
            'financial_account_id' => $cashAccount->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 30000.00,
            'event_id' => $this->closedEvent->id,
        ]);

        // 3. Legitimate Event Income (Cash Debit 15,000 / Income Credit 15,000)
        $incomeEntry = JournalEntry::create([
            'journal_number' => 'JV-INC-001',
            'entry_date' => '2025-10-02',
            'description' => 'Event Donation Income',
            'status' => JournalEntry::STATUS_POSTED,
            'event_id' => $this->closedEvent->id,
            'created_by' => $this->adminUser->id,
            'posted_by' => $this->adminUser->id,
            'posted_at' => now(),
        ]);
        JournalLine::create([
            'journal_entry_id' => $incomeEntry->id,
            'financial_account_id' => $cashAccount->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 15000.00,
            'event_id' => $this->closedEvent->id,
        ]);
        JournalLine::create([
            'journal_entry_id' => $incomeEntry->id,
            'financial_account_id' => $incomeAccount->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 15000.00,
            'event_id' => $this->closedEvent->id,
        ]);

        $response = $this->get(route('public.previous-events.show', $this->closedEvent->slug));
        $response->assertStatus(200);

        // Income must strictly be 15,000 (opening balance 100k and transfer 30k excluded)
        $response->assertSee('15,000.00');
        $response->assertDontSee('115,000.00');
        $response->assertDontSee('145,000.00');
    }

    public function test_reversed_and_unposted_journal_entries_are_excluded_from_public_totals(): void
    {
        $cashAccount = FinancialAccount::where('account_code', FinancialAccount::CODE_CASH)->firstOrFail();
        $incomeAccount = FinancialAccount::where('account_type', FinancialAccount::TYPE_INCOME)->firstOrFail();

        // 1. Posted Income (10,000)
        $postedEntry = JournalEntry::create([
            'journal_number' => 'JV-POST-001',
            'entry_date' => '2025-10-01',
            'description' => 'Verified Posted Collection',
            'status' => JournalEntry::STATUS_POSTED,
            'event_id' => $this->closedEvent->id,
            'created_by' => $this->adminUser->id,
            'posted_by' => $this->adminUser->id,
            'posted_at' => now(),
        ]);
        JournalLine::create([
            'journal_entry_id' => $postedEntry->id,
            'financial_account_id' => $cashAccount->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 10000.00,
            'event_id' => $this->closedEvent->id,
        ]);
        JournalLine::create([
            'journal_entry_id' => $postedEntry->id,
            'financial_account_id' => $incomeAccount->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 10000.00,
            'event_id' => $this->closedEvent->id,
        ]);

        // 2. Draft / Unposted Income (25,000)
        $draftEntry = JournalEntry::create([
            'journal_number' => 'JV-DRAFT-001',
            'entry_date' => '2025-10-02',
            'description' => 'Draft Unposted Voucher',
            'status' => JournalEntry::STATUS_DRAFT,
            'event_id' => $this->closedEvent->id,
            'created_by' => $this->adminUser->id,
        ]);
        JournalLine::create([
            'journal_entry_id' => $draftEntry->id,
            'financial_account_id' => $cashAccount->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 25000.00,
            'event_id' => $this->closedEvent->id,
        ]);
        JournalLine::create([
            'journal_entry_id' => $draftEntry->id,
            'financial_account_id' => $incomeAccount->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 25000.00,
            'event_id' => $this->closedEvent->id,
        ]);

        $response = $this->get(route('public.previous-events.show', $this->closedEvent->slug));
        $response->assertStatus(200);

        // Only the posted 10,000 must appear; the draft 25,000 must be excluded
        $response->assertSee('10,000.00');
        $response->assertDontSee('35,000.00');
    }

    public function test_public_report_generation_does_not_modify_journal_entries_or_financial_records(): void
    {
        $cashAccount = FinancialAccount::where('account_code', FinancialAccount::CODE_CASH)->firstOrFail();
        $incomeAccount = FinancialAccount::where('account_type', FinancialAccount::TYPE_INCOME)->firstOrFail();

        $entry = JournalEntry::create([
            'journal_number' => 'JV-READONLY-001',
            'entry_date' => '2025-10-01',
            'description' => 'Existing Ledger Record',
            'status' => JournalEntry::STATUS_POSTED,
            'event_id' => $this->closedEvent->id,
            'created_by' => $this->adminUser->id,
            'posted_by' => $this->adminUser->id,
            'posted_at' => now(),
        ]);
        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'financial_account_id' => $cashAccount->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 5000.00,
            'event_id' => $this->closedEvent->id,
        ]);
        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'financial_account_id' => $incomeAccount->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 5000.00,
            'event_id' => $this->closedEvent->id,
        ]);

        $entryCountBefore = JournalEntry::count();
        $lineCountBefore = JournalLine::count();

        // View public financial summary
        $response = $this->get(route('public.previous-events.show', $this->closedEvent->slug));
        $response->assertStatus(200);

        $this->assertEquals($entryCountBefore, JournalEntry::count());
        $this->assertEquals($lineCountBefore, JournalLine::count());
    }

    public function test_public_show_displays_fund_settlement_when_completed(): void
    {
        $destAccount = FinancialAccount::where('account_code', FinancialAccount::CODE_GENERAL_FUND)->firstOrFail();

        EventSettlement::create([
            'event_id' => $this->closedEvent->id,
            'settlement_number' => 'SETTLE-2025-001',
            'settlement_date' => '2025-10-10',
            'total_collections' => 50000.00,
            'total_expenses' => 20000.00,
            'surplus_amount' => 30000.00,
            'destination_account_id' => $destAccount->id,
            'notes' => 'Settled remaining balance to temple general fund',
            'status' => 'completed',
            'settled_by' => $this->adminUser->id,
            'created_by' => $this->adminUser->id,
        ]);

        $response = $this->get(route('public.previous-events.show', $this->closedEvent->slug));
        $response->assertStatus(200);
        $response->assertSee('SETTLE-2025-001');
        $response->assertSee(__('portal.public.settled_badge'));
    }

    public function test_public_show_displays_unrecorded_ledger_state_when_no_records_exist(): void
    {
        $response = $this->get(route('public.previous-events.show', $this->closedEvent->slug));
        $response->assertStatus(200);
        $response->assertSee(__('portal.public.no_ledger_records_title'));
    }

    public function test_public_document_preview_stream_for_pdf(): void
    {
        $file = UploadedFile::fake()->create('statutory_audit.pdf', 150, 'application/pdf');
        $path = $file->store('private/event_documents', 'local');

        $doc = EventDocument::create([
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Statutory Audit Statement 2025',
            'document_type' => EventDocument::TYPE_AUDIT_REPORT,
            'report_category' => EventDocument::CATEGORY_COMPLETE_FINANCIAL_STATEMENT,
            'financial_year' => 2025,
            'file_path' => $path,
            'file_name' => 'statutory_audit.pdf',
            'file_size' => 153600,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'published_at' => now(),
            'uploaded_by' => $this->adminUser->id,
        ]);

        $previewResponse = $this->get(route('public.previous-events.document.preview', [
            'slug' => $this->closedEvent->slug,
            'uuid' => $doc->uuid,
        ]));

        $previewResponse->assertStatus(200);
        $previewResponse->assertHeader('Content-Type', 'application/pdf');
        $previewResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $previewResponse->assertSeeText($file->getContent());
    }

    public function test_public_document_download_for_excel(): void
    {
        $file = UploadedFile::fake()->create('accounts_ledger.xlsx', 120, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $path = $file->store('private/event_documents', 'local');

        $doc = EventDocument::create([
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Detailed Expense Breakdown Spreadsheet',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'report_category' => EventDocument::CATEGORY_EXPENSE_BREAKDOWN,
            'financial_year' => 2025,
            'file_path' => $path,
            'file_name' => 'accounts_ledger.xlsx',
            'file_size' => 122880,
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'published_at' => now(),
            'uploaded_by' => $this->adminUser->id,
        ]);

        $downloadResponse = $this->get(route('public.previous-events.document.download', [
            'slug' => $this->closedEvent->slug,
            'uuid' => $doc->uuid,
        ]));

        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('Content-Disposition', 'attachment; filename=accounts_ledger.xlsx');
        $downloadResponse->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_public_document_preview_or_download_denied_for_unpublished_document(): void
    {
        $file = UploadedFile::fake()->create('confidential_notes.pdf', 50, 'application/pdf');
        $path = $file->store('private/event_documents', 'local');

        $doc = EventDocument::create([
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Confidential Notes',
            'document_type' => EventDocument::TYPE_OTHER,
            'report_category' => EventDocument::CATEGORY_OTHER,
            'financial_year' => 2025,
            'file_path' => $path,
            'file_name' => 'confidential_notes.pdf',
            'file_size' => 51200,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_UNPUBLISHED,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $previewResponse = $this->get(route('public.previous-events.document.preview', [
            'slug' => $this->closedEvent->slug,
            'uuid' => $doc->uuid,
        ]));
        $previewResponse->assertStatus(404);

        $downloadResponse = $this->get(route('public.previous-events.document.download', [
            'slug' => $this->closedEvent->slug,
            'uuid' => $doc->uuid,
        ]));
        $downloadResponse->assertStatus(404);
    }

    public function test_public_document_denied_if_event_is_unpublished(): void
    {
        $file = UploadedFile::fake()->create('valid_report.pdf', 50, 'application/pdf');
        $path = $file->store('private/event_documents', 'local');

        $doc = EventDocument::create([
            'event_id' => $this->unpublishedEvent->id,
            'title_en' => 'Valid Report on Unpublished Event',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'report_category' => EventDocument::CATEGORY_COMPLETE_FINANCIAL_STATEMENT,
            'financial_year' => 2025,
            'file_path' => $path,
            'file_name' => 'valid_report.pdf',
            'file_size' => 51200,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->get(route('public.previous-events.document.preview', [
            'slug' => $this->unpublishedEvent->slug,
            'uuid' => $doc->uuid,
        ]));
        $response->assertStatus(404);
    }

    public function test_public_document_preview_blocked_against_path_traversal(): void
    {
        $file = UploadedFile::fake()->create('valid.pdf', 50, 'application/pdf');
        $path = $file->store('private/event_documents', 'local');

        $doc = EventDocument::create([
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Hacked Path Document',
            'document_type' => EventDocument::TYPE_OTHER,
            'file_path' => 'private/event_documents/../../.env',
            'file_name' => 'valid.pdf',
            'file_size' => 51200,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->get(route('public.previous-events.document.preview', [
            'slug' => $this->closedEvent->slug,
            'uuid' => $doc->uuid,
        ]));
        $response->assertStatus(404);
    }

    public function test_admin_can_upload_document_with_report_category_and_financial_year(): void
    {
        $file = UploadedFile::fake()->create('official_financial_statement.xlsx', 250, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $response = $this->actingAs($this->adminUser)->post(route('portal.documents.store'), [
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Durga Puja 2025 Complete Statement',
            'title_bn' => 'দুর্গাপূজা ২০২৫ পূর্ণাঙ্গ বিবরণী',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'report_category' => EventDocument::CATEGORY_COMPLETE_FINANCIAL_STATEMENT,
            'financial_year' => 2025,
            'description_en' => 'Audited balance sheet and summary',
            'publication_status' => 'published',
            'file' => $file,
        ]);

        $createdDoc = EventDocument::where('title_en', 'Durga Puja 2025 Complete Statement')->first();
        $this->assertNotNull($createdDoc);
        $response->assertRedirect(route('portal.documents.show', $createdDoc));

        $this->assertDatabaseHas('event_documents', [
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Durga Puja 2025 Complete Statement',
            'report_category' => EventDocument::CATEGORY_COMPLETE_FINANCIAL_STATEMENT,
            'financial_year' => 2025,
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
        ]);

        $this->assertNotNull($createdDoc->uuid);
    }

    public function test_admin_can_update_document_report_category_and_financial_year(): void
    {
        $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');
        $path = $file->store('private/event_documents', 'local');

        $doc = EventDocument::create([
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Initial Report Title',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'report_category' => EventDocument::CATEGORY_OTHER,
            'financial_year' => 2024,
            'file_path' => $path,
            'file_name' => 'report.pdf',
            'file_size' => 102400,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'uploaded_by' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->put(route('portal.documents.update', $doc), [
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Updated Financial Statement',
            'title_bn' => 'হালনাগাদকৃত আর্থিক বিবরণী',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'report_category' => EventDocument::CATEGORY_INCOME_REPORT,
            'financial_year' => 2025,
            'publication_status' => 'published',
        ]);

        $response->assertRedirect(route('portal.documents.show', $doc));

        $doc->refresh();
        $this->assertEquals('Updated Financial Statement', $doc->title_en);
        $this->assertEquals(EventDocument::CATEGORY_INCOME_REPORT, $doc->report_category);
        $this->assertEquals(2025, $doc->financial_year);
    }

    public function test_legacy_event_accounts_route_redirects_to_previous_events(): void
    {
        $response = $this->get('/event-accounts');

        $response->assertRedirect(route('public.previous-events.index'));
    }

    public function test_e2e_scenario_1_existing_historical_event_upload_publish_and_preview_pdf(): void
    {
        // 1. Admin opens create form
        $createResponse = $this->actingAs($this->adminUser)->get(route('portal.documents.create'));
        $createResponse->assertStatus(200);

        // 2. Upload and publish final closing report for existing event
        $pdfContent = '%PDF-1.4 Official Final Closing Statement 2025';
        $file = UploadedFile::fake()->createWithContent('final_closing_report_2025.pdf', $pdfContent);

        $uploadResponse = $this->actingAs($this->adminUser)->post(route('portal.documents.store'), [
            'event_mode' => 'existing',
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Official Final Closing Statement 2025',
            'title_bn' => 'অফিশিয়াল চূড়ান্ত সমাপনী হিসাব ২০২৫',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'report_category' => EventDocument::CATEGORY_COMPLETE_FINANCIAL_STATEMENT,
            'financial_year' => 2025,
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'file' => $file,
        ]);

        $doc = EventDocument::where('title_en', 'Official Final Closing Statement 2025')->first();
        $this->assertNotNull($doc);
        $this->assertTrue($doc->isPublished());
        $uploadResponse->assertRedirect(route('portal.documents.show', $doc));

        // 3. Public visitor opens /previous-events
        $archiveResponse = $this->get(route('public.previous-events.index'));
        $archiveResponse->assertStatus(200);
        $archiveResponse->assertSee($this->closedEvent->name);

        // 4. Public visitor opens event show page
        $showResponse = $this->get(route('public.previous-events.show', $this->closedEvent->slug));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Official Final Closing Statement 2025');

        // 5. Public visitor previews the PDF
        $previewResponse = $this->get(route('public.previous-events.document.preview', [
            'slug' => $this->closedEvent->slug,
            'uuid' => $doc->uuid,
        ]));
        $previewResponse->assertStatus(200);
        $previewResponse->assertHeader('Content-Type', 'application/pdf');
        $previewResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('Official Final Closing Statement 2025', $previewResponse->getFile()->getContent());
    }

    public function test_e2e_scenario_2_historical_event_not_in_database_upload_publish_and_public_archive(): void
    {
        // 1. Admin adds a historical event that does not exist in the database
        $pdfContent = '%PDF-1.4 Historical Kali Puja 2017 Statement';
        $file = UploadedFile::fake()->createWithContent('kali_puja_2017_statement.pdf', $pdfContent);

        $uploadResponse = $this->actingAs($this->adminUser)->post(route('portal.documents.store'), [
            'event_mode' => 'new',
            'new_event_name_en' => 'Kali Puja 2017',
            'new_event_name_bn' => 'কালীপূজা ২০১৭',
            'new_event_category_id' => $this->pujaCategory->id,
            'new_event_year' => 2017,
            'new_event_venue' => 'Old Temple Premises',
            'title_en' => 'Official Audited Financial Statement 2017',
            'title_bn' => 'অফিশিয়াল নিরীক্ষিত হিসাব বিবরণী ২০১৭',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'report_category' => EventDocument::CATEGORY_COMPLETE_FINANCIAL_STATEMENT,
            'financial_year' => 2017,
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'file' => $file,
        ]);

        // Verify the new event was created and published
        $newEvent = Event::where('name_en', 'Kali Puja 2017')->first();
        $this->assertNotNull($newEvent);
        $this->assertEquals(2017, $newEvent->event_year);
        $this->assertEquals(Event::STATUS_CLOSED, $newEvent->status);
        $this->assertTrue($newEvent->isPublished());

        $doc = EventDocument::where('title_en', 'Official Audited Financial Statement 2017')->first();
        $this->assertNotNull($doc);
        $this->assertEquals($newEvent->id, $doc->event_id);
        $this->assertTrue($doc->isPublished());
        $uploadResponse->assertRedirect(route('portal.documents.show', $doc));

        // 2. Public visitor opens Previous Events archive
        $archiveResponse = $this->get(route('public.previous-events.index'));
        $archiveResponse->assertStatus(200);
        $archiveResponse->assertSee('Kali Puja 2017');
        $archiveResponse->assertSee('2017');

        // 3. Public visitor opens the newly created historical event details
        $showResponse = $this->get(route('public.previous-events.show', $newEvent->slug));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Kali Puja 2017');
        $showResponse->assertSee('Official Audited Financial Statement 2017');

        // 4. Public preview works without database financial transactions
        $previewResponse = $this->get(route('public.previous-events.document.preview', [
            'slug' => $newEvent->slug,
            'uuid' => $doc->uuid,
        ]));
        $previewResponse->assertStatus(200);
        $previewResponse->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_e2e_scenario_3_excel_report_upload_publish_and_download(): void
    {
        $excelData = "Account,Category,Debit,Credit\n4010,Donations,0,85000\n5010,Puja,45000,0";
        $file = UploadedFile::fake()->createWithContent('annual_closing_accounts.xlsx', $excelData);

        $uploadResponse = $this->actingAs($this->adminUser)->post(route('portal.documents.store'), [
            'event_mode' => 'existing',
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Complete Closing Accounts Spreadsheet',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'report_category' => EventDocument::CATEGORY_EXPENSE_BREAKDOWN,
            'financial_year' => 2025,
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'file' => $file,
        ]);

        $doc = EventDocument::where('title_en', 'Complete Closing Accounts Spreadsheet')->first();
        $this->assertNotNull($doc);
        $uploadResponse->assertRedirect(route('portal.documents.show', $doc));

        // Public visitor downloads the file
        $downloadResponse = $this->get(route('public.previous-events.document.download', [
            'slug' => $this->closedEvent->slug,
            'uuid' => $doc->uuid,
        ]));

        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('Content-Disposition', 'attachment; filename=annual_closing_accounts.xlsx');
        $downloadResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertEquals($excelData, $downloadResponse->streamedContent());
    }

    public function test_e2e_scenario_4_unpublish_security_blocks_direct_public_access(): void
    {
        $file = UploadedFile::fake()->create('report_to_unpublish.pdf', 100, 'application/pdf');
        $path = $file->store('private/event_documents', 'local');

        $doc = EventDocument::create([
            'event_id' => $this->closedEvent->id,
            'title_en' => 'Report with Controlled Lifecycle',
            'document_type' => EventDocument::TYPE_FINANCIAL_REPORT,
            'report_category' => EventDocument::CATEGORY_COMPLETE_FINANCIAL_STATEMENT,
            'financial_year' => 2025,
            'file_path' => $path,
            'file_name' => 'report_to_unpublish.pdf',
            'file_size' => 102400,
            'mime_type' => 'application/pdf',
            'publication_status' => EventDocument::PUB_STATUS_PUBLISHED,
            'published_at' => now(),
            'uploaded_by' => $this->adminUser->id,
        ]);

        // 1. Initially accessible
        $previewUrl = route('public.previous-events.document.preview', ['slug' => $this->closedEvent->slug, 'uuid' => $doc->uuid]);
        $downloadUrl = route('public.previous-events.document.download', ['slug' => $this->closedEvent->slug, 'uuid' => $doc->uuid]);

        $this->get($previewUrl)->assertStatus(200);
        $this->get($downloadUrl)->assertStatus(200);

        // 2. Admin unpublishes the document
        $unpublishResponse = $this->actingAs($this->adminUser)->post(route('portal.documents.unpublish', $doc));
        $unpublishResponse->assertRedirect();

        $doc->refresh();
        $this->assertTrue($doc->isUnpublished());

        // 3. Immediate public access block
        $this->get($previewUrl)->assertStatus(404);
        $this->get($downloadUrl)->assertStatus(404);

        // 4. Admin publishes the document again
        $publishResponse = $this->actingAs($this->adminUser)->post(route('portal.documents.publish', $doc));
        $publishResponse->assertRedirect();

        $doc->refresh();
        $this->assertTrue($doc->isPublished());

        // 5. Accessible again
        $this->get($previewUrl)->assertStatus(200);
        $this->get($downloadUrl)->assertStatus(200);
    }
}
