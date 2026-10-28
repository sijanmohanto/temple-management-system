<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(
        protected FinancePostingService $postingService,
        protected JournalEntryService $journalService
    ) {}

    /**
     * Submit a new expense voucher.
     *
     * @throws ValidationException
     */
    public function createExpense(array $data, int $userId): Expense
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Expense amount must be greater than zero.'],
            ]);
        }

        return DB::transaction(function () use ($data, $amount, $userId) {
            $expenseNumber = Expense::generateExpenseNumber($data['expense_date'] ?? null);

            $expenseAccountId = $data['financial_account_id'] ?? $data['expense_account_id'] ?? null;
            $titleEn = $data['title_en'] ?? $data['description'] ?? 'Expense Voucher';

            $expense = Expense::create([
                'expense_number' => $expenseNumber,
                'financial_account_id' => $expenseAccountId,
                'expense_account_id' => $expenseAccountId,
                'payment_account_id' => $data['payment_account_id'],
                'expense_date' => $data['expense_date'],
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'title_en' => $titleEn,
                'title_bn' => $data['title_bn'] ?? null,
                'vendor_name' => $data['vendor_name'] ?? $data['payee_name'] ?? null,
                'vendor_phone' => $data['vendor_phone'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'status' => Expense::STATUS_PENDING,
                'event_id' => $data['event_id'] ?? null,
                'notes' => $data['notes'] ?? $data['description'] ?? null,
                'created_by' => $userId,
            ]);

            AuditLogger::log(
                'expense_created',
                "Submitted expense {$expense->expense_number} for amount {$expense->amount}",
                $expense,
                $expense->toArray()
            );

            return $expense;
        });
    }

    /**
     * Update an existing pending expense voucher.
     *
     * @throws ValidationException
     */
    public function updateExpense(Expense $expense, array $data, int $userId): Expense
    {
        if ($expense->status !== Expense::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'expense' => [__('finance.only_pending_expenses_can_be_edited')],
            ]);
        }

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Expense amount must be greater than zero.'],
            ]);
        }

        return DB::transaction(function () use ($expense, $data, $amount, $userId) {
            /** @var Expense $lockedExpense */
            $lockedExpense = Expense::where('id', $expense->id)->lockForUpdate()->firstOrFail();

            if ($lockedExpense->status !== Expense::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'expense' => [__('finance.only_pending_expenses_can_be_edited')],
                ]);
            }

            $expenseAccountId = $data['financial_account_id'] ?? $data['expense_account_id'] ?? $lockedExpense->financial_account_id;
            $titleEn = $data['title_en'] ?? $data['description'] ?? $lockedExpense->title_en;
            $oldData = $lockedExpense->only([
                'title_en', 'title_bn', 'financial_account_id', 'payment_account_id',
                'amount', 'expense_date', 'payment_method', 'vendor_name', 'vendor_phone',
                'invoice_number', 'event_id', 'notes',
            ]);

            $lockedExpense->update([
                'financial_account_id' => $expenseAccountId,
                'expense_account_id' => $expenseAccountId,
                'payment_account_id' => $data['payment_account_id'],
                'expense_date' => $data['expense_date'],
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'title_en' => $titleEn,
                'title_bn' => $data['title_bn'] ?? null,
                'vendor_name' => $data['vendor_name'] ?? $data['payee_name'] ?? null,
                'vendor_phone' => $data['vendor_phone'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? null,
                'event_id' => $data['event_id'] ?? null,
                'notes' => $data['notes'] ?? $data['description'] ?? null,
            ]);

            AuditLogger::log(
                'expense_updated',
                "Updated pending expense voucher {$lockedExpense->expense_number}",
                $lockedExpense,
                [
                    'old' => $oldData,
                    'new' => $lockedExpense->only([
                        'title_en', 'title_bn', 'financial_account_id', 'payment_account_id',
                        'amount', 'expense_date', 'payment_method', 'vendor_name', 'vendor_phone',
                        'invoice_number', 'event_id', 'notes',
                    ]),
                    'updated_by' => $userId,
                ]
            );

            return $lockedExpense->fresh(['expenseAccount', 'paymentAccount', 'event']);
        });
    }

    /**
     * Approve a pending expense voucher and post to ledger.
     *
     * @throws ValidationException
     */
    public function approveExpense(Expense $expense, int $userId, ?string $note = null): Expense
    {
        if ($expense->status !== Expense::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'expense' => ['Only pending expenses can be approved.'],
            ]);
        }

        return DB::transaction(function () use ($expense, $userId, $note) {
            /** @var Expense $lockedExpense */
            $lockedExpense = Expense::where('id', $expense->id)->lockForUpdate()->firstOrFail();

            if ($lockedExpense->status !== Expense::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'expense' => ['Expense has already been processed.'],
                ]);
            }

            $lockedExpense->update([
                'status' => Expense::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
                'notes' => $note ? trim(($lockedExpense->notes ?? '')." | Approval note: {$note}") : $lockedExpense->notes,
            ]);

            $this->postingService->postExpense($lockedExpense, $userId);

            AuditLogger::log(
                'expense_approved',
                "Approved expense voucher {$lockedExpense->expense_number}",
                $lockedExpense,
                [
                    'status' => Expense::STATUS_APPROVED,
                    'approved_by' => $userId,
                ]
            );

            return $lockedExpense->fresh(['journalEntry', 'expenseAccount', 'paymentAccount']);
        });
    }

    /**
     * Reject a pending expense requisition.
     *
     * @throws ValidationException
     */
    public function rejectExpense(Expense $expense, int $userId, string $reason): Expense
    {
        if ($expense->status !== Expense::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'expense' => ['Only pending expenses can be rejected.'],
            ]);
        }

        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'rejection_reason' => ['Rejection reason is required.'],
            ]);
        }

        return DB::transaction(function () use ($expense, $userId, $reason) {
            /** @var Expense $lockedExpense */
            $lockedExpense = Expense::where('id', $expense->id)->lockForUpdate()->firstOrFail();

            $lockedExpense->update([
                'status' => Expense::STATUS_REJECTED,
                'rejected_by' => $userId,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            AuditLogger::log(
                'expense_rejected',
                "Rejected expense voucher {$lockedExpense->expense_number}. Reason: {$reason}",
                $lockedExpense,
                [
                    'status' => Expense::STATUS_REJECTED,
                    'rejection_reason' => $reason,
                ]
            );

            return $lockedExpense->fresh();
        });
    }

    /**
     * Reverse an approved expense voucher.
     *
     * @throws ValidationException
     */
    public function reverseExpense(Expense $expense, int $reverserId, string $reason): Expense
    {
        if ($expense->status !== Expense::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'expense' => ['Only approved expenses can be reversed.'],
            ]);
        }

        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reversal_reason' => ['Reversal reason is required.'],
            ]);
        }

        return DB::transaction(function () use ($expense, $reverserId, $reason) {
            /** @var Expense $lockedExpense */
            $lockedExpense = Expense::where('id', $expense->id)->lockForUpdate()->firstOrFail();

            if ($lockedExpense->journalEntry) {
                $this->journalService->createReversal($lockedExpense->journalEntry, $reverserId, $reason);
            }

            $lockedExpense->update([
                'status' => Expense::STATUS_REVERSED,
                'notes' => trim(($lockedExpense->notes ?? '')." | Reversed by user #{$reverserId}: {$reason}"),
            ]);

            AuditLogger::log(
                'expense_reversed',
                "Reversed expense {$lockedExpense->expense_number}. Reason: {$reason}",
                $lockedExpense,
                [
                    'status' => Expense::STATUS_REVERSED,
                ]
            );

            return $lockedExpense->fresh();
        });
    }
}
