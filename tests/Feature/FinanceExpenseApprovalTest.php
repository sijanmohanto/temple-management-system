<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use App\Services\ExpenseService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceExpenseApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $templeAdmin;

    protected User $devotee;

    protected FinancialAccount $expenseAccount;

    protected FinancialAccount $paymentAccount;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $this->superAdmin = User::factory()->create(['name' => 'Super Admin', 'email' => 'super@example.com']);
        $this->superAdmin->assignRole('super_admin');

        $this->treasurer = User::factory()->create(['name' => 'Treasurer', 'email' => 'treasurer@example.com']);
        $this->treasurer->assignRole('treasurer');

        $this->templeAdmin = User::factory()->create(['name' => 'Temple Admin', 'email' => 'admin@example.com']);
        $this->templeAdmin->assignRole('temple_admin');

        $this->devotee = User::factory()->create(['name' => 'Devotee', 'email' => 'devotee@example.com']);
        $this->devotee->assignRole('devotee');

        $this->expenseAccount = FinancialAccount::where('account_type', FinancialAccount::TYPE_EXPENSE)->firstOrFail();
        $this->paymentAccount = FinancialAccount::where('account_type', FinancialAccount::TYPE_ASSET)->firstOrFail();

        $category = EventCategory::first();
        $this->event = Event::create([
            'event_code' => 'EVT-2026-EXP',
            'name_en' => 'Durga Puja 2026',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'venue' => 'Main Temple',
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-20',
            'status' => Event::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    protected function createPendingExpense(array $overrides = []): Expense
    {
        $service = app(ExpenseService::class);

        return $service->createExpense(array_merge([
            'title_en' => 'Puja Flowers and Fruits',
            'title_bn' => 'পূজার ফুল ও ফল',
            'financial_account_id' => $this->expenseAccount->id,
            'payment_account_id' => $this->paymentAccount->id,
            'expense_date' => '2026-10-20',
            'amount' => 1500.00,
            'payment_method' => 'cash',
            'vendor_name' => 'Gour Flower Stall',
            'vendor_phone' => '01711223344',
            'invoice_number' => 'INV-101',
            'event_id' => $this->event->id,
            'notes' => 'Fresh lotus flowers for puja',
        ], $overrides), $this->superAdmin->id);
    }

    public function test_super_admin_can_view_expense_listing_and_action_buttons(): void
    {
        $expense = $this->createPendingExpense();

        // 1. HTML index page renders with DataTable container and modals
        $response = $this->actingAs($this->superAdmin)->get(route('finance.expenses.index'));
        $response->assertStatus(200);
        $response->assertSee('id="expensesTable"', false);
        $response->assertSee('id="listApproveModal"', false);
        $response->assertSee('id="listRejectModal"', false);

        // 2. AJAX DataTable payload includes full action metadata
        $ajaxResponse = $this->actingAs($this->superAdmin)->getJson(route('finance.expenses.index'), [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $ajaxResponse->assertStatus(200);
        $json = $ajaxResponse->json();

        $this->assertNotEmpty($json['data']);
        $row = collect($json['data'])->firstWhere('id', $expense->id);
        $this->assertNotNull($row);
        $this->assertTrue($row['is_pending']);
        $this->assertTrue($row['can_approve']);
        $this->assertTrue($row['can_manage']);
        $this->assertEquals(route('finance.expenses.show', $expense), $row['view_url']);
        $this->assertEquals(route('finance.expenses.edit', $expense), $row['edit_url']);
        $this->assertEquals(route('finance.expenses.approve', $expense), $row['approve_url']);
        $this->assertEquals(route('finance.expenses.reject', $expense), $row['reject_url']);

        // 3. Details page renders with Approve, Reject, and Edit buttons
        $showResponse = $this->actingAs($this->superAdmin)->get(route('finance.expenses.show', $expense));
        $showResponse->assertStatus(200);
        $showResponse->assertSee(__('finance.approve_expense'));
        $showResponse->assertSee(__('finance.reject_expense'));
        $showResponse->assertSee(__('common.edit'));
        $showResponse->assertSee('id="approveExpenseModal"', false);
        $showResponse->assertSee('id="rejectExpenseModal"', false);
    }

    public function test_super_admin_can_approve_pending_expense_and_posts_balanced_journal(): void
    {
        $expense = $this->createPendingExpense(['amount' => 2500.00]);

        $this->assertEquals(Expense::STATUS_PENDING, $expense->status);
        $this->assertNull($expense->journal_entry_id);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('finance.expenses.approve', $expense), [
                'note' => 'Approved by Super Admin for Durga Puja rituals',
            ]);

        $response->assertRedirect(route('finance.expenses.show', $expense));
        $response->assertSessionHas('success', __('finance.expense_approved_successfully'));

        $expense->refresh();
        $this->assertEquals(Expense::STATUS_APPROVED, $expense->status);
        $this->assertEquals($this->superAdmin->id, $expense->approved_by);
        $this->assertNotNull($expense->approved_at);
        $this->assertNotNull($expense->journal_entry_id);
        $this->assertStringContainsString('Approval note: Approved by Super Admin', $expense->notes);

        // Verify balanced journal voucher
        $journal = $expense->journalEntry;
        $this->assertNotNull($journal);
        $this->assertEquals('2500.00', (string) $journal->total_debit);
        $this->assertEquals('2500.00', (string) $journal->total_credit);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $journal->status);

        // Lines: Debit Expense Account, Credit Payment Account
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'financial_account_id' => $this->expenseAccount->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 2500.00,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'financial_account_id' => $this->paymentAccount->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 2500.00,
        ]);
    }

    public function test_super_admin_can_edit_eligible_pending_expense(): void
    {
        $expense = $this->createPendingExpense(['amount' => 1000.00, 'title_en' => 'Initial Title']);

        // 1. Can view edit form
        $editFormResponse = $this->actingAs($this->superAdmin)->get(route('finance.expenses.edit', $expense));
        $editFormResponse->assertStatus(200);
        $editFormResponse->assertSee('Initial Title');

        // 2. Can update pending expense
        $updateResponse = $this->actingAs($this->superAdmin)
            ->put(route('finance.expenses.update', $expense), [
                'title_en' => 'Updated Puja Flowers',
                'title_bn' => 'সংশোধিত পূজার ফুল',
                'expense_account_id' => $this->expenseAccount->id,
                'payment_account_id' => $this->paymentAccount->id,
                'expense_date' => '2026-10-21',
                'amount' => 1800.00,
                'payment_method' => 'bkash',
                'vendor_name' => 'Updated Vendor Store',
                'vendor_phone' => '01811223344',
                'invoice_number' => 'INV-202',
                'event_id' => $this->event->id,
                'notes' => 'Updated flowers quantity',
            ]);

        $updateResponse->assertRedirect(route('finance.expenses.show', $expense));
        $updateResponse->assertSessionHas('success', __('finance.expense_updated_successfully'));

        $expense->refresh();
        $this->assertEquals('Updated Puja Flowers', $expense->title_en);
        $this->assertEquals('1800.00', (string) $expense->amount);
        $this->assertEquals('bkash', $expense->payment_method);
        $this->assertEquals('Updated Vendor Store', $expense->vendor_name);
        $this->assertEquals(Expense::STATUS_PENDING, $expense->status);
    }

    public function test_super_admin_can_reject_pending_expense_with_mandatory_reason(): void
    {
        $expense = $this->createPendingExpense();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('finance.expenses.reject', $expense), [
                'reason' => 'Duplicate requisition submitted by temple volunteer',
            ]);

        $response->assertRedirect(route('finance.expenses.show', $expense));
        $response->assertSessionHas('success', __('finance.expense_rejected_successfully'));

        $expense->refresh();
        $this->assertEquals(Expense::STATUS_REJECTED, $expense->status);
        $this->assertEquals($this->superAdmin->id, $expense->rejected_by);
        $this->assertNotNull($expense->rejected_at);
        $this->assertEquals('Duplicate requisition submitted by temple volunteer', $expense->rejection_reason);

        // No ledger entry created
        $this->assertNull($expense->journal_entry_id);
    }

    public function test_rejection_fails_when_reason_is_missing(): void
    {
        $expense = $this->createPendingExpense();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('finance.expenses.reject', $expense), [
                'reason' => '',
            ]);

        $response->assertSessionHasErrors('reason');

        $expense->refresh();
        $this->assertEquals(Expense::STATUS_PENDING, $expense->status);
    }

    public function test_unauthorized_users_cannot_approve_edit_or_reject_expenses(): void
    {
        $expense = $this->createPendingExpense();

        // Devotee cannot approve
        $this->actingAs($this->devotee)
            ->post(route('finance.expenses.approve', $expense), ['note' => 'Unauthorized'])
            ->assertStatus(403);

        // Devotee cannot reject
        $this->actingAs($this->devotee)
            ->post(route('finance.expenses.reject', $expense), ['reason' => 'Unauthorized rejection'])
            ->assertStatus(403);

        // Devotee cannot access edit form
        $this->actingAs($this->devotee)
            ->get(route('finance.expenses.edit', $expense))
            ->assertStatus(403);

        // Devotee cannot submit update
        $this->actingAs($this->devotee)
            ->put(route('finance.expenses.update', $expense), [
                'title_en' => 'Hacked title',
                'amount' => 9999.00,
            ])
            ->assertStatus(403);

        $expense->refresh();
        $this->assertEquals(Expense::STATUS_PENDING, $expense->status);
    }

    public function test_approved_expenses_cannot_be_approved_repeatedly(): void
    {
        $expense = $this->createPendingExpense();

        // First approval
        $this->actingAs($this->superAdmin)
            ->post(route('finance.expenses.approve', $expense))
            ->assertRedirect(route('finance.expenses.show', $expense));

        $expense->refresh();
        $this->assertEquals(Expense::STATUS_APPROVED, $expense->status);
        $journalId = $expense->journal_entry_id;

        // Second approval attempt must be blocked
        $response = $this->actingAs($this->superAdmin)
            ->post(route('finance.expenses.approve', $expense));

        $response->assertSessionHas('error');
        $this->assertEquals(1, JournalEntry::where('source_type', Expense::class)->where('source_id', $expense->id)->count());
        $this->assertEquals($journalId, $expense->fresh()->journal_entry_id);
    }

    public function test_approved_financial_history_cannot_be_silently_edited(): void
    {
        $expense = $this->createPendingExpense(['amount' => 2000.00]);

        // Approve it first
        $this->actingAs($this->superAdmin)->post(route('finance.expenses.approve', $expense));
        $expense->refresh();
        $this->assertEquals(Expense::STATUS_APPROVED, $expense->status);

        // Attempting to view edit page redirects with error
        $editPageResponse = $this->actingAs($this->superAdmin)->get(route('finance.expenses.edit', $expense));
        $editPageResponse->assertRedirect(route('finance.expenses.show', $expense));
        $editPageResponse->assertSessionHas('error');

        // Attempting to put update fails
        $updateResponse = $this->actingAs($this->superAdmin)
            ->put(route('finance.expenses.update', $expense), [
                'title_en' => 'Tampered Title',
                'expense_account_id' => $this->expenseAccount->id,
                'payment_account_id' => $this->paymentAccount->id,
                'expense_date' => '2026-10-22',
                'amount' => 500.00,
                'payment_method' => 'cash',
            ]);

        $updateResponse->assertRedirect(route('finance.expenses.show', $expense));
        $updateResponse->assertSessionHas('error');

        $expense->refresh();
        $this->assertEquals('2000.00', (string) $expense->amount);
    }

    public function test_pending_and_rejected_expenses_do_not_post_to_general_ledger(): void
    {
        $pending = $this->createPendingExpense(['amount' => 700.00]);
        $rejected = $this->createPendingExpense(['amount' => 900.00]);

        $this->actingAs($this->superAdmin)->post(route('finance.expenses.reject', $rejected), [
            'reason' => 'Invalid voucher memo',
        ]);

        $this->assertDatabaseMissing('journal_entries', [
            'source_type' => Expense::class,
            'source_id' => $pending->id,
        ]);

        $this->assertDatabaseMissing('journal_entries', [
            'source_type' => Expense::class,
            'source_id' => $rejected->id,
        ]);
    }
}
