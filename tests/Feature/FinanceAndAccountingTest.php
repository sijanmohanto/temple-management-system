<?php

namespace Tests\Feature;

use App\Models\CollectionPayment;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\FundTransfer;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\PaymentAllocation;
use App\Models\Person;
use App\Models\Pledge;
use App\Models\User;
use App\Services\CollectionPaymentService;
use App\Services\ExpenseService;
use App\Services\FinancePostingService;
use App\Services\FinancialReportService;
use App\Services\FundTransferService;
use App\Services\JournalEntryService;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\EventCategorySeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinanceAndAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $treasurer;

    protected User $templeAdmin;

    protected User $committeeMember;

    protected User $devotee;

    protected Event $event;

    protected Person $person;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(EventCategorySeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        // Users & Roles
        $this->superAdmin = User::factory()->create(['name' => 'Super Admin', 'email' => 'super@example.com']);
        $this->superAdmin->assignRole('super_admin');

        $this->treasurer = User::factory()->create(['name' => 'Treasurer', 'email' => 'treasurer@example.com']);
        $this->treasurer->assignRole('treasurer');

        $this->templeAdmin = User::factory()->create(['name' => 'Temple Admin', 'email' => 'admin@example.com']);
        $this->templeAdmin->assignRole('temple_admin');

        $this->committeeMember = User::factory()->create(['name' => 'Committee Member', 'email' => 'member@example.com']);
        $this->committeeMember->assignRole('committee_member');

        $this->devotee = User::factory()->create(['name' => 'Devotee', 'email' => 'devotee@example.com']);
        $this->devotee->assignRole('devotee');

        // Sample Event
        $category = EventCategory::first();
        $this->event = Event::create([
            'event_code' => 'EVT-2026-TEST',
            'name_en' => 'Durga Puja 2026',
            'name_bn' => 'শারদীয় দুর্গোৎসব ২০২৬',
            'event_category_id' => $category->id,
            'event_year' => 2026,
            'venue' => 'Main Temple',
            'start_date' => '2026-10-15',
            'end_date' => '2026-10-20',
            'status' => Event::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        // Sample Person
        $this->person = Person::create([
            'person_code' => 'TMP-2026-0001',
            'name_en' => 'Joy Das',
            'phone' => '01711999888',
            'gender' => 'male',
            'status' => 'active',
        ]);
    }

    public function test_chart_of_accounts_authorization_and_validation(): void
    {
        // Devotee cannot access accounts
        $this->actingAs($this->devotee)->get(route('finance.accounts.index'))
            ->assertStatus(403);

        // Treasurer can view
        $this->actingAs($this->treasurer)->get(route('finance.accounts.index'))
            ->assertStatus(200);

        // Treasurer can create custom non-system account
        $response = $this->actingAs($this->treasurer)->post(route('finance.accounts.store'), [
            'code' => '5095',
            'name_en' => 'Sound System & Acoustics',
            'name_bn' => 'সাউন্ড সিস্টেম',
            'account_type' => FinancialAccount::TYPE_EXPENSE,
            'normal_balance' => FinancialAccount::BALANCE_DEBIT,
        ]);

        $response->assertRedirect(route('finance.accounts.index'));
        $this->assertDatabaseHas('financial_accounts', ['account_code' => '5095', 'account_name_en' => 'Sound System & Acoustics']);

        // Duplicate code validation
        $dupResponse = $this->actingAs($this->treasurer)->post(route('finance.accounts.store'), [
            'code' => '5095',
            'name_en' => 'Another Account',
            'account_type' => FinancialAccount::TYPE_EXPENSE,
            'normal_balance' => FinancialAccount::BALANCE_DEBIT,
        ]);
        $dupResponse->assertSessionHasErrors('code');
    }

    public function test_journal_balance_enforcement_and_rejection_of_unbalanced_entries(): void
    {
        $journalService = app(JournalEntryService::class);
        $cashAcc = FinancialAccount::findByCode(FinancialAccount::CODE_CASH);
        $incomeAcc = FinancialAccount::findByCode(FinancialAccount::CODE_INCOME_GENERAL);

        // Attempting to create an unbalanced entry (Debit: 1000, Credit: 800)
        $this->expectException(ValidationException::class);

        $journalService->createEntry([
            'entry_date' => '2026-10-20',
            'source_type' => JournalEntry::SOURCE_MANUAL,
            'description' => 'Unbalanced test voucher',
            'lines' => [
                ['account_id' => $cashAcc->id, 'debit_amount' => 1000.00, 'credit_amount' => 0.00],
                ['account_id' => $incomeAcc->id, 'debit_amount' => 0.00, 'credit_amount' => 800.00],
            ],
        ]);
    }

    public function test_posting_verified_cash_payment_to_correct_ledger_accounts(): void
    {
        $paymentService = app(CollectionPaymentService::class);

        $pledge = Pledge::create([
            'pledge_code' => 'PLG-2026-0001',
            'person_id' => $this->person->id,
            'event_id' => $this->event->id,
            'pledged_on' => '2026-10-10',
            'pledge_year' => 2026,
            'pledged_amount' => 5000.00,
            'status' => Pledge::STATUS_ACTIVE,
            'created_by' => $this->treasurer->id,
        ]);

        $payment = $paymentService->submitPayment([
            'payer_person_id' => $this->person->id,
            'collection_type' => 'pledge',
            'event_id' => $this->event->id,
            'payment_date' => '2026-10-12',
            'amount' => 5000.00,
            'payment_method' => 'cash',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_PLEDGE,
                'pledge_id' => $pledge->id,
                'amount' => 5000.00,
            ],
        ], $this->templeAdmin->id);

        $this->assertNull($payment->finance_posted_at);

        // Verify payment
        $paymentService->verifyPayment($payment, $this->treasurer->id);
        $payment->refresh();

        $this->assertTrue($payment->isVerified());
        $this->assertNotNull($payment->finance_posted_at);
        $this->assertNotNull($payment->finance_post_reference);

        // Check journal entry
        $journal = JournalEntry::where('source_type', CollectionPayment::class)
            ->where('source_id', $payment->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $journal->status);
        $this->assertEquals(5000.00, $journal->total_debit);
        $this->assertEquals(5000.00, $journal->total_credit);

        // Check lines: Debit Cash 1010, Credit Event Income 4020
        $cashAcc = FinancialAccount::findByCode(FinancialAccount::CODE_CASH);
        $incomeAcc = FinancialAccount::findByCode(FinancialAccount::CODE_INCOME_EVENT);

        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'financial_account_id' => $cashAcc->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 5000.00,
        ]);

        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'financial_account_id' => $incomeAcc->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 5000.00,
            'event_id' => $this->event->id,
        ]);
    }

    public function test_posting_verified_bank_payment_to_correct_ledger_accounts(): void
    {
        $paymentService = app(CollectionPaymentService::class);

        $payment = $paymentService->submitPayment([
            'payer_person_id' => $this->person->id,
            'collection_type' => 'voluntary',
            'payment_date' => '2026-10-14',
            'amount' => 10000.00,
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'TXN-BANK-9988',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                'amount' => 10000.00,
            ],
        ], $this->templeAdmin->id);

        $paymentService->verifyPayment($payment, $this->treasurer->id);
        $payment->refresh();

        $journal = JournalEntry::where('source_type', CollectionPayment::class)
            ->where('source_id', $payment->id)
            ->first();

        $bankAcc = FinancialAccount::findByCode(FinancialAccount::CODE_BANK);
        $genIncomeAcc = FinancialAccount::findByCode(FinancialAccount::CODE_INCOME_GENERAL);

        // Debit Bank Account 1020
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'financial_account_id' => $bankAcc->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 10000.00,
        ]);

        // Credit General Donation Income 4010
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'financial_account_id' => $genIncomeAcc->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 10000.00,
        ]);
    }

    public function test_preventing_pending_and_rejected_payments_from_entering_ledger(): void
    {
        $paymentService = app(CollectionPaymentService::class);

        $payment = $paymentService->submitPayment([
            'payer_person_id' => $this->person->id,
            'collection_type' => 'voluntary',
            'payment_date' => '2026-10-14',
            'amount' => 2000.00,
            'payment_method' => 'cash',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                'amount' => 2000.00,
            ],
        ], $this->templeAdmin->id);

        // Pending: No journal entries
        $this->assertEquals(0, JournalEntry::where('source_type', CollectionPayment::class)->where('source_id', $payment->id)->count());
        $this->assertNull($payment->finance_posted_at);

        // Reject payment
        $paymentService->rejectPayment($payment, $this->treasurer->id, 'Discrepancy in currency count');
        $payment->refresh();

        $this->assertTrue($payment->isRejected());
        // Rejected: Still no journal entries
        $this->assertEquals(0, JournalEntry::where('source_type', CollectionPayment::class)->where('source_id', $payment->id)->count());
        $this->assertNull($payment->finance_posted_at);
    }

    public function test_preventing_duplicate_posting_of_same_payment(): void
    {
        $paymentService = app(CollectionPaymentService::class);
        $postingService = app(FinancePostingService::class);

        $payment = $paymentService->submitPayment([
            'payer_person_id' => $this->person->id,
            'collection_type' => 'voluntary',
            'payment_date' => '2026-10-14',
            'amount' => 1500.00,
            'payment_method' => 'cash',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                'amount' => 1500.00,
            ],
        ], $this->templeAdmin->id);

        $paymentService->verifyPayment($payment, $this->treasurer->id);
        $payment->refresh();

        $initialJournalCount = JournalEntry::where('source_type', CollectionPayment::class)
            ->where('source_id', $payment->id)
            ->count();
        $this->assertEquals(1, $initialJournalCount);

        // Attempting to post again directly via posting service
        $postingService->postCollectionPayment($payment, $this->treasurer->id);

        $finalJournalCount = JournalEntry::where('source_type', CollectionPayment::class)
            ->where('source_id', $payment->id)
            ->count();
        $this->assertEquals(1, $finalJournalCount);
    }

    public function test_posting_excess_donation_amounts_exactly_once(): void
    {
        $paymentService = app(CollectionPaymentService::class);

        $pledge = Pledge::create([
            'pledge_code' => 'PLG-EXCESS-001',
            'person_id' => $this->person->id,
            'event_id' => $this->event->id,
            'pledged_on' => '2026-10-10',
            'pledge_year' => 2026,
            'pledged_amount' => 3000.00,
            'status' => Pledge::STATUS_ACTIVE,
            'created_by' => $this->treasurer->id,
        ]);

        // Payment of 5,000 against pledge of 3,000 (2,000 excess)
        $payment = $paymentService->submitPayment([
            'payer_person_id' => $this->person->id,
            'collection_type' => 'pledge',
            'event_id' => $this->event->id,
            'payment_date' => '2026-10-15',
            'amount' => 5000.00,
            'payment_method' => 'cash',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_PLEDGE,
                'pledge_id' => $pledge->id,
                'amount' => 5000.00,
            ],
        ], $this->templeAdmin->id);

        $paymentService->verifyPayment($payment, $this->treasurer->id);

        $journal = JournalEntry::where('source_type', CollectionPayment::class)
            ->where('source_id', $payment->id)
            ->first();

        $this->assertNotNull($journal);
        // Total debit must equal exact cash received: 5000.00
        $this->assertEquals(5000.00, $journal->total_debit);
        $this->assertEquals(5000.00, $journal->total_credit);

        // Lines count: 1 Debit (Cash 5000), 2 Credits (Pledge 3000, Voluntary 2000)
        $this->assertCount(3, $journal->lines);
    }

    public function test_reversal_of_posted_payment_without_deleting_history(): void
    {
        $paymentService = app(CollectionPaymentService::class);

        $payment = $paymentService->submitPayment([
            'payer_person_id' => $this->person->id,
            'collection_type' => 'voluntary',
            'payment_date' => '2026-10-14',
            'amount' => 2500.00,
            'payment_method' => 'cash',
        ], [
            [
                'target_type' => PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
                'amount' => 2500.00,
            ],
        ], $this->templeAdmin->id);

        $paymentService->verifyPayment($payment, $this->treasurer->id);
        $payment->refresh();

        $originalJournal = JournalEntry::where('source_type', CollectionPayment::class)
            ->where('source_id', $payment->id)
            ->first();
        $this->assertEquals(JournalEntry::STATUS_POSTED, $originalJournal->status);

        // Reverse payment
        $paymentService->reversePayment($payment, $this->treasurer->id, 'Donor requested cancellation due to wrong amount');
        $payment->refresh();
        $originalJournal->refresh();

        // Original journal preserved and marked reversed
        $this->assertEquals(JournalEntry::STATUS_REVERSED, $originalJournal->status);

        // Reversal voucher created
        $reversalJournal = JournalEntry::where('source_type', JournalEntry::SOURCE_REVERSAL)
            ->where('source_id', $originalJournal->id)
            ->first();

        $this->assertNotNull($reversalJournal);
        $this->assertEquals(JournalEntry::STATUS_POSTED, $reversalJournal->status);
        $this->assertEquals(2500.00, $reversalJournal->total_debit);
        $this->assertEquals(2500.00, $reversalJournal->total_credit);

        // Check inverse lines
        $cashAcc = FinancialAccount::findByCode(FinancialAccount::CODE_CASH);
        $genIncomeAcc = FinancialAccount::findByCode(FinancialAccount::CODE_INCOME_GENERAL);

        // Reversal debits income, credits cash
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $reversalJournal->id,
            'financial_account_id' => $genIncomeAcc->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 2500.00,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $reversalJournal->id,
            'financial_account_id' => $cashAcc->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 2500.00,
        ]);
    }

    public function test_expense_requisition_approval_and_posting_workflow(): void
    {
        $expenseService = app(ExpenseService::class);
        $pujaExpenseAcc = FinancialAccount::findByCode(FinancialAccount::CODE_EXP_PUJA);
        $cashAcc = FinancialAccount::findByCode(FinancialAccount::CODE_CASH);

        // Submit expense requisition
        $expense = $expenseService->createExpense([
            'title_en' => 'Prasad & Fruits for Puja',
            'expense_account_id' => $pujaExpenseAcc->id,
            'payment_account_id' => $cashAcc->id,
            'expense_date' => '2026-10-16',
            'amount' => 4500.00,
            'payment_method' => 'cash',
            'vendor_name' => 'Bhog Bhandar',
            'event_id' => $this->event->id,
        ], $this->templeAdmin->id);

        $this->assertEquals(Expense::STATUS_PENDING, $expense->status);
        $this->assertNull($expense->journal_entry_id);

        // Non-authorized devotee cannot approve
        $this->actingAs($this->devotee)->post(route('finance.expenses.approve', $expense))
            ->assertStatus(403);

        // Treasurer approves
        $this->actingAs($this->treasurer)->post(route('finance.expenses.approve', $expense), [
            'note' => 'Verified voucher and approved disbursement',
        ])->assertRedirect(route('finance.expenses.show', $expense));

        $expense->refresh();
        $this->assertEquals(Expense::STATUS_APPROVED, $expense->status);
        $this->assertNotNull($expense->journal_entry_id);

        // Journal created: Debit Expense 5010, Credit Cash 1010
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $expense->journal_entry_id,
            'financial_account_id' => $pujaExpenseAcc->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 4500.00,
            'event_id' => $this->event->id,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $expense->journal_entry_id,
            'financial_account_id' => $cashAcc->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 4500.00,
        ]);

        // Reversal of expense
        $this->actingAs($this->treasurer)->post(route('finance.expenses.reverse', $expense), [
            'reason' => 'Vendor issued refund due to damaged prasad delivery',
        ])->assertRedirect(route('finance.expenses.show', $expense));

        $expense->refresh();
        $this->assertEquals(Expense::STATUS_REVERSED, $expense->status);
    }

    public function test_fund_transfer_accounting_without_income_or_expense_duplication(): void
    {
        $transferService = app(FundTransferService::class);
        $cashAcc = FinancialAccount::findByCode(FinancialAccount::CODE_CASH);
        $bankAcc = FinancialAccount::findByCode(FinancialAccount::CODE_BANK);

        $transfer = $transferService->transferFunds([
            'from_account_id' => $cashAcc->id,
            'to_account_id' => $bankAcc->id,
            'transfer_date' => '2026-10-18',
            'amount' => 20000.00,
            'fee_amount' => 25.00,
            'reference_number' => 'DEP-SLIP-7711',
        ], $this->treasurer->id);

        $this->assertEquals(FundTransfer::STATUS_COMPLETED, $transfer->status);
        $this->assertNotNull($transfer->journal_entry_id);

        $journal = $transfer->journalEntry;
        $this->assertNotNull($journal);

        // Debits Bank 1020 for 20,000, Credits Cash 1010 for 20,000
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'financial_account_id' => $bankAcc->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 20000.00,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'financial_account_id' => $cashAcc->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 20000.00,
        ]);

        // Fee: Debits Bank Charges 5080 for 25, Credits Cash 1010 for 25
        $feeAcc = FinancialAccount::findByCode(FinancialAccount::CODE_EXP_BANK_CHARGES);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'financial_account_id' => $feeAcc->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 25.00,
        ]);

        // Never touches income accounts
        $this->assertEquals(0, JournalLine::where('journal_entry_id', $journal->id)
            ->whereHas('account', fn ($q) => $q->where('account_type', FinancialAccount::TYPE_INCOME))
            ->count());

        // Verify relationship on FundTransfer model
        $this->assertNotNull($transfer->processor);
        $this->assertEquals($this->treasurer->id, $transfer->processor->id);
        $this->assertTrue($transfer->isCompleted());

        // Verify index page renders cleanly with existing transfers and loads processor
        $this->actingAs($this->treasurer)->get(route('finance.transfers.index'))
            ->assertStatus(200)
            ->assertSee($transfer->transfer_number);
    }

    public function test_source_and_destination_account_validation(): void
    {
        $cashAcc = FinancialAccount::findByCode(FinancialAccount::CODE_CASH);
        $expenseAcc = FinancialAccount::findByCode(FinancialAccount::CODE_EXP_PUJA);

        // Same account transfer fails
        $sameResponse = $this->actingAs($this->treasurer)->post(route('finance.transfers.store'), [
            'from_account_id' => $cashAcc->id,
            'to_account_id' => $cashAcc->id,
            'transfer_date' => '2026-10-18',
            'amount' => 1000.00,
        ]);
        $sameResponse->assertSessionHasErrors('to_account_id');

        // Non-asset destination fails
        $nonAssetResponse = $this->actingAs($this->treasurer)->post(route('finance.transfers.store'), [
            'from_account_id' => $cashAcc->id,
            'to_account_id' => $expenseAcc->id,
            'transfer_date' => '2026-10-18',
            'amount' => 1000.00,
        ]);
        $nonAssetResponse->assertSessionHasErrors('to_account_id');
    }

    public function test_opening_balance_validation_and_duplicate_prevention(): void
    {
        $cashAcc = FinancialAccount::findByCode(FinancialAccount::CODE_CASH);

        // Record opening balance
        $response = $this->actingAs($this->treasurer)->post(route('finance.opening-balances.store'), [
            'account_id' => $cashAcc->id,
            'balance_type' => 'debit',
            'amount' => 75000.00,
            'entry_date' => '2026-01-01',
            'notes' => 'Initial physical cash vault count',
        ]);

        $response->assertRedirect(route('finance.opening-balances.index'));

        // Check journal: Debits Cash 1010, Credits Opening Balance Equity 3020
        $equityAcc = FinancialAccount::findByCode(FinancialAccount::CODE_OPENING_EQUITY);

        $journal = JournalEntry::where('source_type', JournalEntry::SOURCE_OPENING_BALANCE)
            ->where('source_id', $cashAcc->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals(75000.00, $journal->total_debit);
        $this->assertEquals(75000.00, $journal->total_credit);

        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'financial_account_id' => $cashAcc->id,
            'entry_type' => JournalLine::TYPE_DEBIT,
            'amount' => 75000.00,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_entry_id' => $journal->id,
            'financial_account_id' => $equityAcc->id,
            'entry_type' => JournalLine::TYPE_CREDIT,
            'amount' => 75000.00,
        ]);

        // Attempting duplicate opening balance for Cash account is rejected
        $dupResponse = $this->actingAs($this->treasurer)->post(route('finance.opening-balances.store'), [
            'account_id' => $cashAcc->id,
            'balance_type' => 'debit',
            'amount' => 5000.00,
            'entry_date' => '2026-01-01',
        ]);
        $dupResponse->assertSessionHasErrors('account_id');

        // Does not count as period income in Income Statement
        $reportService = app(FinancialReportService::class);
        $statement = $reportService->getIncomeExpenseStatement('2026-01-01', '2026-12-31');
        $this->assertEquals(0.00, $statement['total_income']);
    }

    public function test_trial_balance_debit_and_credit_reconciliation(): void
    {
        $postingService = app(FinancePostingService::class);
        $cashAcc = FinancialAccount::findByCode(FinancialAccount::CODE_CASH);

        // Add opening balance
        $postingService->postOpeningBalance($cashAcc, 'debit', 50000.00, '2026-01-01', null, $this->treasurer->id);

        $reportService = app(FinancialReportService::class);
        $trialBalance = $reportService->getTrialBalance();

        $this->assertTrue($trialBalance['is_balanced']);
        $this->assertEquals(0.00, $trialBalance['difference']);
        $this->assertGreaterThan(0.00, $trialBalance['total_debit']);
        $this->assertEquals($trialBalance['total_debit'], $trialBalance['total_credit']);
    }

    public function test_event_wise_financial_summary_accuracy(): void
    {
        $postingService = app(FinancePostingService::class);
        $expenseService = app(ExpenseService::class);
        $pujaExpenseAcc = FinancialAccount::findByCode(FinancialAccount::CODE_EXP_PUJA);
        $cashAcc = FinancialAccount::findByCode(FinancialAccount::CODE_CASH);
        $incomeAcc = FinancialAccount::findByCode(FinancialAccount::CODE_INCOME_EVENT);

        // Expense for Durga Puja
        $expense = $expenseService->createExpense([
            'title_en' => 'Durga Idol Making',
            'expense_account_id' => $pujaExpenseAcc->id,
            'payment_account_id' => $cashAcc->id,
            'expense_date' => '2026-10-12',
            'amount' => 15000.00,
            'payment_method' => 'cash',
            'event_id' => $this->event->id,
        ], $this->templeAdmin->id);
        $expenseService->approveExpense($expense, $this->treasurer->id);

        $reportService = app(FinancialReportService::class);
        $summary = $reportService->getEventSummary($this->event->id);

        $this->assertNotEmpty($summary);
        $eventData = $summary[0];
        $this->assertEquals($this->event->id, $eventData['event']->id);
        $this->assertEquals(15000.00, $eventData['total_expense']);
    }

    public function test_permission_enforcement_for_sensitive_finance_operations(): void
    {
        // Devotee denied on finance routes
        $this->actingAs($this->devotee)->get(route('finance.dashboard'))->assertStatus(403);
        $this->actingAs($this->devotee)->get(route('finance.journals.index'))->assertStatus(403);
        $this->actingAs($this->devotee)->get(route('finance.expenses.index'))->assertStatus(403);
        $this->actingAs($this->devotee)->get(route('finance.transfers.index'))->assertStatus(403);
        $this->actingAs($this->devotee)->get(route('finance.reports.trial-balance'))->assertStatus(403);

        // Committee member has read-only access to dashboard and reports
        $this->actingAs($this->committeeMember)->get(route('finance.dashboard'))->assertStatus(200);
        $this->actingAs($this->committeeMember)->get(route('finance.reports.trial-balance'))->assertStatus(200);

        // Committee member cannot approve expenses or execute transfers
        $this->actingAs($this->committeeMember)->get(route('finance.transfers.create'))->assertStatus(403);
    }

    public function test_journal_voucher_view_and_reversal_workflow(): void
    {
        $journalService = app(JournalEntryService::class);
        $cashAcc = FinancialAccount::findByCode(FinancialAccount::CODE_CASH);
        $equityAcc = FinancialAccount::findByCode(FinancialAccount::CODE_GENERAL_FUND);

        $journal = $journalService->createEntry([
            'entry_date' => '2026-10-25',
            'description' => 'Test voucher for reversal check',
            'source_type' => JournalEntry::SOURCE_MANUAL,
            'lines' => [
                ['financial_account_id' => $cashAcc->id, 'entry_type' => JournalLine::TYPE_DEBIT, 'amount' => 500.00],
                ['financial_account_id' => $equityAcc->id, 'entry_type' => JournalLine::TYPE_CREDIT, 'amount' => 500.00],
            ],
            'created_by' => $this->treasurer->id,
            'posted_by' => $this->treasurer->id,
        ]);

        // 1. Can view posted journal entry
        $this->actingAs($this->treasurer)
            ->get(route('finance.journals.show', $journal))
            ->assertStatus(200)
            ->assertSee($journal->journal_number);

        // 2. Reverse journal entry
        $response = $this->actingAs($this->treasurer)
            ->post(route('finance.journals.reverse', $journal), [
                'reason' => 'Administrative correction for mistaken cash receipt entry',
            ]);

        $journal->refresh();
        $this->assertEquals(JournalEntry::STATUS_REVERSED, $journal->status);
        $this->assertNotNull($journal->reversedByJournal);

        $reversalJournal = $journal->reversedByJournal;
        $this->assertEquals($journal->id, $reversalJournal->reversed_journal_id);
        $this->assertEquals($journal->id, $reversalJournal->reversedJournal->id);

        $response->assertRedirect(route('finance.journals.show', $reversalJournal));

        // 3. View original reversed journal entry (renders reversedByJournal)
        $this->actingAs($this->treasurer)
            ->get(route('finance.journals.show', $journal))
            ->assertStatus(200)
            ->assertSee($reversalJournal->journal_number);

        // 4. View reversal journal entry (renders reversedJournal)
        $this->actingAs($this->treasurer)
            ->get(route('finance.journals.show', $reversalJournal))
            ->assertStatus(200)
            ->assertSee($journal->journal_number);
    }

    public function test_all_finance_pages_and_views_render_cleanly(): void
    {
        $journal = JournalEntry::first();
        if ($journal) {
            $this->actingAs($this->treasurer)->get(route('finance.journals.show', $journal))->assertStatus(200);
        }

        $this->actingAs($this->treasurer)->get(route('finance.dashboard'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.accounts.index'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.journals.index'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.expenses.index'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.expenses.create'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.transfers.index'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.transfers.create'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.income.index'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.income.create'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.opening-balances.index'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.reports.trial-balance'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.reports.general-ledger'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.reports.income-expense'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.reports.cash-book'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.reports.account-statement'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.reports.account-balances'))->assertStatus(200);
        $this->actingAs($this->treasurer)->get(route('finance.reports.event-summary'))->assertStatus(200);
    }

    public function test_english_and_bengali_localization_parity(): void
    {
        $en = require resource_path('../lang/en/finance.php');
        $bn = require resource_path('../lang/bn/finance.php');

        $enKeys = array_keys($en);
        $bnKeys = array_keys($bn);

        sort($enKeys);
        sort($bnKeys);

        $this->assertEquals($enKeys, $bnKeys, 'Keys in lang/en/finance.php and lang/bn/finance.php must be identical');
    }
}
