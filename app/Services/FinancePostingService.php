<?php

namespace App\Services;

use App\Models\CollectionPayment;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\FundTransfer;
use App\Models\JournalEntry;
use App\Models\ManualIncome;
use App\Models\PaymentAllocation;
use App\Models\VendorBill;
use App\Models\VendorBillItem;
use App\Models\VendorPayment;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancePostingService
{
    public function __construct(
        protected JournalEntryService $journalService
    ) {}

    /**
     * Post a verified collection payment to the general ledger.
     *
     * @throws ValidationException
     */
    public function postCollectionPayment(CollectionPayment $payment, int $userId): JournalEntry
    {
        $this->ensureAccountsExist();

        if ($payment->status !== CollectionPayment::STATUS_VERIFIED) {
            throw ValidationException::withMessages([
                'payment' => ['Only verified collection payments can be posted to the finance ledger.'],
            ]);
        }

        // Check if already posted
        $existing = JournalEntry::where('source_type', CollectionPayment::class)
            ->where('source_id', $payment->id)
            ->first();

        if ($existing) {
            if (! $payment->finance_posted_at) {
                $payment->update([
                    'finance_posted_at' => $existing->posted_at ?? now(),
                    'finance_post_reference' => $existing->journal_number,
                ]);
            }

            return $existing;
        }

        return DB::transaction(function () use ($payment, $userId) {
            // Determine asset deposit account based on payment method
            $assetCode = match ($payment->payment_method) {
                'cash' => FinancialAccount::CODE_CASH,
                'bank_transfer', 'cheque' => FinancialAccount::CODE_BANK,
                'bkash', 'nagad', 'rocket', 'upay' => FinancialAccount::CODE_MFS,
                default => FinancialAccount::CODE_CASH,
            };

            $assetAccount = FinancialAccount::findByCode($assetCode);
            if (! $assetAccount) {
                throw new \RuntimeException("Asset account with code {$assetCode} not found in chart of accounts.");
            }

            $lines = [];

            // 1. Debit Asset Account (total payment amount)
            $lines[] = [
                'account_id' => $assetAccount->id,
                'debit_amount' => (float) $payment->amount,
                'credit_amount' => 0.00,
                'line_description_en' => "Collection payment {$payment->payment_code} received via ".ucfirst(str_replace('_', ' ', $payment->payment_method)),
                'line_description_bn' => "আদায়কৃত অর্থপ্রাপ্তি {$payment->payment_code} (".$payment->payment_method.')',
                'event_id' => $payment->event_id,
                'reference_type' => CollectionPayment::class,
                'reference_id' => $payment->id,
            ];

            // 2. Credit Income Accounts based on active allocations
            $allocations = $payment->allocations()
                ->where('is_active', true)
                ->with(['pledge', 'monthlyContribution', 'event'])
                ->get();

            if ($allocations->isEmpty()) {
                throw ValidationException::withMessages([
                    'payment' => ['Cannot post payment with no active allocations.'],
                ]);
            }

            foreach ($allocations as $alloc) {
                $allocAmount = (float) $alloc->amount;
                $eventId = null;
                $descEn = '';
                $descBn = '';

                if ($alloc->target_type === PaymentAllocation::TARGET_PLEDGE && $alloc->pledge) {
                    $incomeCode = FinancialAccount::CODE_INCOME_EVENT;
                    $eventId = $alloc->pledge->event_id;
                    $descEn = "Pledge collection for {$alloc->pledge->pledge_code}";
                    $descBn = "মানত আদায় ({$alloc->pledge->pledge_code})";
                } elseif ($alloc->target_type === PaymentAllocation::TARGET_MONTHLY_CONTRIBUTION && $alloc->monthlyContribution) {
                    $incomeCode = FinancialAccount::CODE_INCOME_COMMITTEE;
                    $descEn = 'Monthly contribution for '.($alloc->monthlyContribution->month_name ?? 'Committee');
                    $descBn = 'মাসিক কার্যনির্বাহী চাঁদা';
                } elseif ($alloc->target_type === PaymentAllocation::TARGET_VOLUNTARY_EVENT) {
                    $incomeCode = FinancialAccount::CODE_INCOME_EVENT;
                    $eventId = $alloc->event_id ?? $payment->event_id;
                    $descEn = 'Event voluntary donation'.($alloc->event ? " ({$alloc->event->title_en})" : '');
                    $descBn = 'উৎসবের ঐচ্ছিক অনুদান';
                } else {
                    $incomeCode = FinancialAccount::CODE_INCOME_GENERAL;
                    $eventId = null;
                    $descEn = 'General temple voluntary donation';
                    $descBn = 'সাধারণ মন্দির অনুদান';
                }

                $incomeAccount = FinancialAccount::findByCode($incomeCode);
                if (! $incomeAccount) {
                    throw new \RuntimeException("Income account with code {$incomeCode} not found in chart of accounts.");
                }

                $lines[] = [
                    'account_id' => $incomeAccount->id,
                    'debit_amount' => 0.00,
                    'credit_amount' => $allocAmount,
                    'line_description_en' => $descEn,
                    'line_description_bn' => $descBn,
                    'event_id' => $eventId,
                    'reference_type' => get_class($alloc),
                    'reference_id' => $alloc->id,
                ];
            }

            $journal = $this->journalService->createEntry([
                'entry_date' => $payment->payment_date,
                'source_type' => CollectionPayment::class,
                'source_id' => $payment->id,
                'description' => "Verified collection payment {$payment->payment_code} from ".($payment->payer_name ?: 'Devotee'),
                'event_id' => $payment->event_id,
                'created_by' => $userId,
                'posted_by' => $userId,
                'lines' => $lines,
            ]);

            $payment->updateQuietly([
                'finance_posted_at' => now(),
                'finance_post_reference' => $journal->journal_number,
            ]);

            AuditLogger::log(
                'collection_payment_finance_posted',
                "Posted collection payment {$payment->payment_code} to ledger journal {$journal->journal_number}",
                $payment,
                [
                    'journal_number' => $journal->journal_number,
                    'amount' => $payment->amount,
                ]
            );

            return $journal;
        });
    }

    /**
     * Reverse a previously posted collection payment from the general ledger.
     */
    public function reverseCollectionPayment(CollectionPayment $payment, int $reverserId, string $reason): ?JournalEntry
    {
        $journal = JournalEntry::where('source_type', CollectionPayment::class)
            ->where('source_id', $payment->id)
            ->first();

        if (! $journal) {
            return null;
        }

        if ($journal->status === JournalEntry::STATUS_REVERSED) {
            return null;
        }

        return $this->journalService->createReversal($journal, $reverserId, $reason);
    }

    /**
     * Post an approved expense to the general ledger.
     */
    public function postExpense(Expense $expense, int $userId): JournalEntry
    {
        $this->ensureAccountsExist();

        $existing = JournalEntry::where('source_type', Expense::class)
            ->where('source_id', $expense->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($expense, $userId) {
            $amount = (float) $expense->amount;

            $lines = [
                // Debit: Expense Account
                [
                    'account_id' => $expense->expense_account_id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0.00,
                    'line_description_en' => "Expense: {$expense->title_en}".($expense->vendor_name ? " (Payee: {$expense->vendor_name})" : ''),
                    'line_description_bn' => 'ব্যয়: '.($expense->title_bn ?: $expense->title_en),
                    'event_id' => $expense->event_id,
                    'reference_type' => Expense::class,
                    'reference_id' => $expense->id,
                ],
                // Credit: Asset Account (Payment Source)
                [
                    'account_id' => $expense->payment_account_id,
                    'debit_amount' => 0.00,
                    'credit_amount' => $amount,
                    'line_description_en' => "Disbursement for {$expense->expense_number} via ".ucfirst(str_replace('_', ' ', $expense->payment_method)),
                    'line_description_bn' => "ভাউচার {$expense->expense_number} পরিশোধ (".$expense->payment_method.')',
                    'event_id' => $expense->event_id,
                    'reference_type' => Expense::class,
                    'reference_id' => $expense->id,
                ],
            ];

            $journal = $this->journalService->createEntry([
                'entry_date' => $expense->expense_date,
                'source_type' => Expense::class,
                'source_id' => $expense->id,
                'description' => "Expense voucher {$expense->expense_number}: {$expense->title_en}",
                'event_id' => $expense->event_id,
                'created_by' => $userId,
                'posted_by' => $userId,
                'lines' => $lines,
            ]);

            $expense->updateQuietly([
                'journal_entry_id' => $journal->id,
            ]);

            return $journal;
        });
    }

    /**
     * Post an approved vendor bill to the general ledger as Accounts Payable.
     * In double-entry accounting:
     * - Debit: Expense Accounts (for each line item)
     * - Credit: Accounts Payable (2010 Liability account)
     * Cash/Bank balance is NOT decreased.
     */
    public function postVendorBill(VendorBill $bill, int $userId): JournalEntry
    {
        $this->ensureAccountsExist();

        if ($bill->status !== VendorBill::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'bill' => ['Only approved vendor bills can be posted to the finance ledger.'],
            ]);
        }

        $existing = JournalEntry::where('source_type', VendorBill::class)
            ->where('source_id', $bill->id)
            ->first();

        if ($existing) {
            if (! $bill->journal_entry_id) {
                $bill->updateQuietly(['journal_entry_id' => $existing->id]);
            }

            return $existing;
        }

        return DB::transaction(function () use ($bill, $userId) {
            $apAccount = FinancialAccount::findByCode(FinancialAccount::CODE_AP);
            if (! $apAccount) {
                $apAccount = FinancialAccount::where('account_type', FinancialAccount::TYPE_LIABILITY)
                    ->where('is_active', true)
                    ->first();
            }

            if (! $apAccount) {
                throw new \RuntimeException('Accounts Payable liability account (2010) not found in chart of accounts.');
            }

            $bill->loadMissing(['items.expenseAccount', 'vendor']);

            $lines = [];
            $totalAmount = (float) $bill->total_amount;
            $subtotal = (float) $bill->subtotal;
            $items = $bill->items;

            $allocatedDebitTotal = 0.00;
            $itemCount = $items->count();

            foreach ($items as $index => $item) {
                $itemBase = (float) $item->line_total;

                // Scale line item amount proportionately if header-level discount or tax is present
                if ($subtotal > 0 && abs($subtotal - $totalAmount) > 0.001) {
                    if ($index === $itemCount - 1) {
                        $lineDebit = max(0.00, round($totalAmount - $allocatedDebitTotal, 2));
                    } else {
                        $ratio = $itemBase / $subtotal;
                        $lineDebit = round($totalAmount * $ratio, 2);
                        $allocatedDebitTotal += $lineDebit;
                    }
                } else {
                    $lineDebit = $itemBase;
                    $allocatedDebitTotal += $lineDebit;
                }

                if ($lineDebit > 0) {
                    $lines[] = [
                        'account_id' => $item->financial_account_id,
                        'debit_amount' => $lineDebit,
                        'credit_amount' => 0.00,
                        'line_description_en' => "Bill {$bill->bill_number} - {$item->description}".($bill->vendor ? " (Payee: {$bill->vendor->name})" : ''),
                        'line_description_bn' => "বিল {$bill->bill_number} - {$item->description}",
                        'event_id' => $bill->event_id,
                        'reference_type' => VendorBillItem::class,
                        'reference_id' => $item->id,
                    ];
                }
            }

            // Credit: Accounts Payable (Liability)
            $lines[] = [
                'account_id' => $apAccount->id,
                'debit_amount' => 0.00,
                'credit_amount' => $totalAmount,
                'line_description_en' => "Accounts Payable obligation for Bill {$bill->bill_number} (Vendor: {$bill->vendor?->name}, Invoice: {$bill->vendor_invoice_number})",
                'line_description_bn' => "বিল {$bill->bill_number} এর জন্য প্রদেয় হিসাব দায় (সরবরাহকারী: {$bill->vendor?->name}, চালান: {$bill->vendor_invoice_number})",
                'event_id' => $bill->event_id,
                'reference_type' => VendorBill::class,
                'reference_id' => $bill->id,
            ];

            $journal = $this->journalService->createEntry([
                'entry_date' => $bill->bill_date,
                'source_type' => VendorBill::class,
                'source_id' => $bill->id,
                'description' => "Vendor bill {$bill->bill_number}: {$bill->vendor_invoice_number} ({$bill->vendor?->name})",
                'event_id' => $bill->event_id,
                'created_by' => $userId,
                'posted_by' => $userId,
                'lines' => $lines,
            ]);

            $bill->updateQuietly([
                'journal_entry_id' => $journal->id,
            ]);

            return $journal;
        });
    }

    /**
     * Reverse a posted vendor bill in the general ledger.
     */
    public function reverseVendorBill(VendorBill $bill, int $reverserId, string $reason): ?JournalEntry
    {
        $journal = JournalEntry::where('source_type', VendorBill::class)
            ->where('source_id', $bill->id)
            ->first();

        if (! $journal) {
            return null;
        }

        if ($journal->status === JournalEntry::STATUS_REVERSED) {
            return null;
        }

        return $this->journalService->createReversal($journal, $reverserId, $reason);
    }

    /**
     * Post an authorized vendor payment to the general ledger.
     * Settles the Accounts Payable liability (Debits AP 2010) and Credits the liquid payment asset account.
     * Settles the allocated vendor bills atomically.
     */
    public function postVendorPayment(VendorPayment $payment, int $userId): JournalEntry
    {
        $this->ensureAccountsExist();

        // Idempotency check: if already posted, return existing journal entry
        $existing = JournalEntry::where('source_type', VendorPayment::class)
            ->where('source_id', $payment->id)
            ->first();

        if ($existing) {
            if (! $payment->journal_entry_id) {
                $payment->updateQuietly(['journal_entry_id' => $existing->id]);
            }

            return $existing;
        }

        return DB::transaction(function () use ($payment, $userId) {
            $apAccount = FinancialAccount::findByCode(FinancialAccount::CODE_AP);
            if (! $apAccount) {
                $apAccount = FinancialAccount::where('account_type', FinancialAccount::TYPE_LIABILITY)
                    ->where('is_active', true)
                    ->first();
            }

            if (! $apAccount) {
                throw new \RuntimeException('Accounts Payable liability account (2010) not found in chart of accounts.');
            }

            $paymentAccount = $payment->financialAccount;
            if (! $paymentAccount || $paymentAccount->account_type !== FinancialAccount::TYPE_ASSET) {
                throw new \RuntimeException('Valid liquid asset payment account (Cash/Bank) must be configured for vendor payment.');
            }

            $payment->loadMissing(['allocations.bill', 'vendor']);

            $amount = (float) $payment->amount;

            $lines = [
                // Debit: Accounts Payable (Liability reduced)
                [
                    'financial_account_id' => $apAccount->id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0.00,
                    'line_description_en' => "Settlement of Accounts Payable for Payment {$payment->payment_number} to {$payment->vendor?->name}",
                    'line_description_bn' => "পরিশোধ {$payment->payment_number} এর বিপরীতে প্রদেয় হিসাব দায় সমন্বয় (সরবরাহকারী: {$payment->vendor?->name})",
                    'event_id' => null,
                    'reference_type' => VendorPayment::class,
                    'reference_id' => $payment->id,
                ],
                // Credit: Payment Asset Account (Cash/Bank balance reduced)
                [
                    'financial_account_id' => $paymentAccount->id,
                    'debit_amount' => 0.00,
                    'credit_amount' => $amount,
                    'line_description_en' => "Vendor disbursement via {$payment->payment_method} ({$payment->payment_number}) to {$payment->vendor?->name}".($payment->reference_number ? " Ref: {$payment->reference_number}" : ''),
                    'line_description_bn' => "সরবরাহকারী পরিশোধ ({$payment->payment_number}) - {$payment->payment_method} মাধ্যমে ছাড় (সরবরাহকারী: {$payment->vendor?->name})",
                    'event_id' => null,
                    'reference_type' => VendorPayment::class,
                    'reference_id' => $payment->id,
                ],
            ];

            $journal = $this->journalService->createEntry([
                'entry_date' => $payment->payment_date,
                'source_type' => VendorPayment::class,
                'source_id' => $payment->id,
                'description' => "Vendor payment {$payment->payment_number}: {$payment->vendor?->name} (৳{$amount})",
                'event_id' => null,
                'created_by' => $userId,
                'posted_by' => $userId,
                'lines' => $lines,
            ]);

            // Update payment record
            $payment->updateQuietly([
                'status' => VendorPayment::STATUS_POSTED,
                'journal_entry_id' => $journal->id,
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            // Update allocated bills
            foreach ($payment->allocations as $alloc) {
                $bill = $alloc->bill;
                if ($bill) {
                    $newPaidAmount = round((float) $bill->paid_amount + (float) $alloc->allocated_amount, 2);
                    $newStatus = ($newPaidAmount >= (float) $bill->total_amount)
                        ? 'paid'
                        : 'partially_paid';

                    $bill->updateQuietly([
                        'paid_amount' => $newPaidAmount,
                        'payment_status' => $newStatus,
                    ]);
                }
            }

            return $journal;
        });
    }

    /**
     * Reverse a posted vendor payment in the general ledger.
     * Restores Accounts Payable liability and un-settles allocated bill balances.
     */
    public function reverseVendorPayment(VendorPayment $payment, int $reverserId, string $reason): ?JournalEntry
    {
        $journal = JournalEntry::where('source_type', VendorPayment::class)
            ->where('source_id', $payment->id)
            ->first();

        if (! $journal) {
            return null;
        }

        if ($journal->status === JournalEntry::STATUS_REVERSED) {
            return null;
        }

        return DB::transaction(function () use ($payment, $journal, $reverserId, $reason) {
            $reversal = $this->journalService->createReversal($journal, $reverserId, $reason);

            $payment->loadMissing('allocations.bill');

            // Restore bill balances
            foreach ($payment->allocations as $alloc) {
                $bill = $alloc->bill;
                if ($bill) {
                    $newPaidAmount = max(0.00, round((float) $bill->paid_amount - (float) $alloc->allocated_amount, 2));
                    $newStatus = ($newPaidAmount <= 0.00)
                        ? 'unpaid'
                        : (($newPaidAmount >= (float) $bill->total_amount) ? 'paid' : 'partially_paid');

                    $bill->updateQuietly([
                        'paid_amount' => $newPaidAmount,
                        'payment_status' => $newStatus,
                    ]);
                }
            }

            $payment->updateQuietly([
                'status' => VendorPayment::STATUS_REVERSED,
                'reversal_journal_entry_id' => $reversal->id,
                'reversed_by' => $reverserId,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            return $reversal;
        });
    }

    /**
     * Post a fund transfer between asset accounts.
     */
    public function postTransfer(FundTransfer $transfer, int $userId): JournalEntry
    {
        $this->ensureAccountsExist();

        $existing = JournalEntry::where('source_type', FundTransfer::class)
            ->where('source_id', $transfer->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($transfer, $userId) {
            $amount = (float) $transfer->amount;
            $fee = (float) ($transfer->fee_amount ?? 0);

            $lines = [
                // Debit: Destination Account (Receives transferred principal)
                [
                    'account_id' => $transfer->to_account_id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0.00,
                    'line_description_en' => "Fund transfer received from {$transfer->fromAccount->name_en}",
                    'line_description_bn' => "তহবিল স্থানান্তর গ্রহণ: {$transfer->fromAccount->name_bn}",
                    'reference_type' => FundTransfer::class,
                    'reference_id' => $transfer->id,
                ],
                // Credit: Source Account (Funds disbursed)
                [
                    'account_id' => $transfer->from_account_id,
                    'debit_amount' => 0.00,
                    'credit_amount' => $amount,
                    'line_description_en' => "Fund transfer sent to {$transfer->toAccount->name_en}",
                    'line_description_bn' => "তহবিল স্থানান্তর প্রদান: {$transfer->toAccount->name_bn}",
                    'reference_type' => FundTransfer::class,
                    'reference_id' => $transfer->id,
                ],
            ];

            // If bank or processing fee applies
            if ($fee > 0) {
                $feeAccount = FinancialAccount::findByCode(FinancialAccount::CODE_EXP_BANK_CHARGES);
                if ($feeAccount) {
                    $lines[] = [
                        'account_id' => $feeAccount->id,
                        'debit_amount' => $fee,
                        'credit_amount' => 0.00,
                        'line_description_en' => "Transfer fee/charges for {$transfer->transfer_number}",
                        'line_description_bn' => 'স্থানান্তর মাশুল / ব্যাংক চার্জ',
                        'reference_type' => FundTransfer::class,
                        'reference_id' => $transfer->id,
                    ];
                    $lines[] = [
                        'account_id' => $transfer->from_account_id,
                        'debit_amount' => 0.00,
                        'credit_amount' => $fee,
                        'line_description_en' => 'Transfer fee deducted from source account',
                        'line_description_bn' => 'উৎস হিসাব থেকে মাশুল কর্তন',
                        'reference_type' => FundTransfer::class,
                        'reference_id' => $transfer->id,
                    ];
                }
            }

            $journal = $this->journalService->createEntry([
                'entry_date' => $transfer->transfer_date,
                'source_type' => FundTransfer::class,
                'source_id' => $transfer->id,
                'description' => "Fund transfer {$transfer->transfer_number} from {$transfer->fromAccount->name_en} to {$transfer->toAccount->name_en}",
                'created_by' => $userId,
                'posted_by' => $userId,
                'lines' => $lines,
            ]);

            $transfer->updateQuietly([
                'journal_entry_id' => $journal->id,
            ]);

            return $journal;
        });
    }

    /**
     * Post miscellaneous manual income.
     */
    public function postManualIncome(ManualIncome $income, int $userId): JournalEntry
    {
        $this->ensureAccountsExist();

        $existing = JournalEntry::where('source_type', ManualIncome::class)
            ->where('source_id', $income->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($income, $userId) {
            $amount = (float) $income->amount;

            $lines = [
                // Debit: Asset Deposit Account
                [
                    'account_id' => $income->deposit_account_id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0.00,
                    'line_description_en' => "Income deposit: {$income->title_en}".($income->payer_name ? " (Received from: {$income->payer_name})" : ''),
                    'line_description_bn' => 'আয় জমা: '.($income->title_bn ?: $income->title_en),
                    'event_id' => $income->event_id,
                    'reference_type' => ManualIncome::class,
                    'reference_id' => $income->id,
                ],
                // Credit: Income Account
                [
                    'account_id' => $income->income_account_id,
                    'debit_amount' => 0.00,
                    'credit_amount' => $amount,
                    'line_description_en' => "Revenue: {$income->title_en}",
                    'line_description_bn' => 'খাত: '.($income->title_bn ?: $income->title_en),
                    'event_id' => $income->event_id,
                    'reference_type' => ManualIncome::class,
                    'reference_id' => $income->id,
                ],
            ];

            $journal = $this->journalService->createEntry([
                'entry_date' => $income->income_date,
                'source_type' => ManualIncome::class,
                'source_id' => $income->id,
                'description' => "Miscellaneous Income {$income->income_number}: {$income->title_en}",
                'event_id' => $income->event_id,
                'created_by' => $userId,
                'posted_by' => $userId,
                'lines' => $lines,
            ]);

            $income->updateQuietly([
                'journal_entry_id' => $journal->id,
            ]);

            return $journal;
        });
    }

    /**
     * Post an opening balance adjustment balancing against Opening Balance Equity (3020).
     *
     * @param  string  $balanceType  'debit' or 'credit'
     */
    public function postOpeningBalance(
        FinancialAccount $account,
        string $balanceType,
        float $amount,
        string $date,
        ?string $notes,
        int $userId
    ): JournalEntry {
        $this->ensureAccountsExist();

        $equityAccount = FinancialAccount::findByCode(FinancialAccount::CODE_OPENING_EQUITY);
        if (! $equityAccount) {
            (new ChartOfAccountsSeeder)->run();
            $equityAccount = FinancialAccount::findByCode(FinancialAccount::CODE_OPENING_EQUITY);
        }

        if (! $equityAccount) {
            $equityAccount = FinancialAccount::create([
                'account_code' => FinancialAccount::CODE_OPENING_EQUITY,
                'account_name_en' => 'Opening Balance Equity',
                'account_name_bn' => 'প্রারম্ভিক উদ্বৃত্ত হিসাব',
                'account_type' => FinancialAccount::TYPE_EQUITY,
                'normal_balance' => FinancialAccount::BALANCE_CREDIT,
                'is_system' => true,
                'is_active' => true,
                'description' => 'Balancing equity account for initial balance setup and period opening entries',
            ]);
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Opening balance amount must be greater than zero.'],
            ]);
        }

        $lines = [];
        if ($balanceType === 'debit') {
            // Debit target account, Credit Opening Balance Equity
            $lines[] = [
                'account_id' => $account->id,
                'debit_amount' => $amount,
                'credit_amount' => 0.00,
                'line_description_en' => "Opening debit balance for {$account->code} - {$account->name_en}",
                'line_description_bn' => "প্রারম্ভিক ডেবিট জের: {$account->code} - {$account->name_bn}",
            ];
            $lines[] = [
                'account_id' => $equityAccount->id,
                'debit_amount' => 0.00,
                'credit_amount' => $amount,
                'line_description_en' => "Opening balance offset for {$account->code}",
                'line_description_bn' => "প্রারম্ভিক ইকুইটি সমন্বয়: {$account->code}",
            ];
        } else {
            // Credit target account, Debit Opening Balance Equity
            $lines[] = [
                'account_id' => $equityAccount->id,
                'debit_amount' => $amount,
                'credit_amount' => 0.00,
                'line_description_en' => "Opening balance offset for {$account->code}",
                'line_description_bn' => "প্রারম্ভিক ইকুইটি সমন্বয়: {$account->code}",
            ];
            $lines[] = [
                'account_id' => $account->id,
                'debit_amount' => 0.00,
                'credit_amount' => $amount,
                'line_description_en' => "Opening credit balance for {$account->code} - {$account->name_en}",
                'line_description_bn' => "প্রারম্ভিক ক্রেডিট জের: {$account->code} - {$account->name_bn}",
            ];
        }

        return $this->journalService->createEntry([
            'entry_date' => $date,
            'source_type' => JournalEntry::SOURCE_OPENING_BALANCE,
            'source_id' => $account->id,
            'description' => "Opening balance entry for {$account->code} - {$account->name_en}".($notes ? " ({$notes})" : ''),
            'created_by' => $userId,
            'posted_by' => $userId,
            'lines' => $lines,
        ]);
    }

    public function ensureAccountsExist(): void
    {
        $requiredCodes = [
            FinancialAccount::CODE_CASH,
            FinancialAccount::CODE_GENERAL_FUND,
            FinancialAccount::CODE_OPENING_EQUITY,
            FinancialAccount::CODE_INCOME_GENERAL,
            FinancialAccount::CODE_INCOME_EVENT,
            FinancialAccount::CODE_INCOME_COMMITTEE,
            FinancialAccount::CODE_INCOME_MISC,
        ];

        $existingCount = FinancialAccount::whereIn('account_code', $requiredCodes)->count();
        if ($existingCount < count($requiredCodes)) {
            (new ChartOfAccountsSeeder)->run();
        }
    }
}
