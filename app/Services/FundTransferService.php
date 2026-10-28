<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\FundTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FundTransferService
{
    public function __construct(
        protected FinancePostingService $postingService,
        protected JournalEntryService $journalService
    ) {}

    /**
     * Execute a fund transfer between asset accounts.
     *
     * @throws ValidationException
     */
    public function transferFunds(array $data, int $userId): FundTransfer
    {
        $fromAccountId = (int) ($data['from_account_id'] ?? 0);
        $toAccountId = (int) ($data['to_account_id'] ?? 0);
        $amount = round((float) ($data['amount'] ?? 0), 2);
        $feeAmount = round((float) ($data['fee_amount'] ?? 0), 2);

        if ($fromAccountId === $toAccountId) {
            throw ValidationException::withMessages([
                'to_account_id' => ['Source and destination accounts must be different.'],
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Transfer amount must be greater than zero.'],
            ]);
        }

        if ($feeAmount < 0) {
            throw ValidationException::withMessages([
                'fee_amount' => ['Transfer fee cannot be negative.'],
            ]);
        }

        $fromAccount = FinancialAccount::findOrFail($fromAccountId);
        $toAccount = FinancialAccount::findOrFail($toAccountId);

        if ($fromAccount->account_type !== FinancialAccount::TYPE_ASSET) {
            throw ValidationException::withMessages([
                'from_account_id' => ['Source account must be a cash, bank, or MFS asset account.'],
            ]);
        }

        if ($toAccount->account_type !== FinancialAccount::TYPE_ASSET) {
            throw ValidationException::withMessages([
                'to_account_id' => ['Destination account must be a cash, bank, or MFS asset account.'],
            ]);
        }

        return DB::transaction(function () use ($data, $fromAccountId, $toAccountId, $amount, $feeAmount, $userId) {
            $transferNumber = FundTransfer::generateTransferNumber($data['transfer_date'] ?? null);

            $transfer = FundTransfer::create([
                'transfer_number' => $transferNumber,
                'from_account_id' => $fromAccountId,
                'to_account_id' => $toAccountId,
                'transfer_date' => $data['transfer_date'],
                'amount' => $amount,
                'fee_amount' => $feeAmount,
                'bank_charge_amount' => $feeAmount,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'description' => $data['notes'] ?? 'Fund Transfer',
                'status' => FundTransfer::STATUS_COMPLETED,
                'processed_by' => $userId,
                'created_by' => $userId,
                'processed_at' => now(),
            ]);

            // Post to ledger atomically
            $this->postingService->postTransfer($transfer, $userId);

            AuditLogger::log(
                'transfer_created',
                "Executed fund transfer {$transfer->transfer_number} of {$transfer->amount} from account #{$fromAccountId} to #{$toAccountId}",
                $transfer,
                $transfer->toArray()
            );

            return $transfer->fresh(['fromAccount', 'toAccount', 'journalEntry']);
        });
    }

    /**
     * Reverse a completed fund transfer.
     *
     * @throws ValidationException
     */
    public function reverseTransfer(FundTransfer $transfer, int $reverserId, string $reason): FundTransfer
    {
        if ($transfer->status === FundTransfer::STATUS_REVERSED) {
            throw ValidationException::withMessages([
                'transfer' => ['Fund transfer has already been reversed.'],
            ]);
        }

        if (empty(trim($reason))) {
            throw ValidationException::withMessages([
                'reversal_reason' => ['Reversal reason is required.'],
            ]);
        }

        return DB::transaction(function () use ($transfer, $reverserId, $reason) {
            /** @var FundTransfer $lockedTransfer */
            $lockedTransfer = FundTransfer::where('id', $transfer->id)->lockForUpdate()->firstOrFail();

            if ($lockedTransfer->journalEntry) {
                $this->journalService->createReversal($lockedTransfer->journalEntry, $reverserId, $reason);
            }

            $lockedTransfer->update([
                'status' => FundTransfer::STATUS_REVERSED,
                'notes' => trim(($lockedTransfer->notes ?? '')." | Reversed: {$reason}"),
            ]);

            AuditLogger::log(
                'transfer_reversed',
                "Reversed fund transfer {$lockedTransfer->transfer_number}. Reason: {$reason}",
                $lockedTransfer,
                ['status' => FundTransfer::STATUS_REVERSED]
            );

            return $lockedTransfer->fresh();
        });
    }
}
