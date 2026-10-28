<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalEntryService
{
    /**
     * Create and post a balanced double-entry journal entry.
     *
     * @param array{
     *     entry_date: string|Carbon,
     *     source_type: string,
     *     source_id?: int|null,
     *     description_en: string,
     *     description_bn?: string|null,
     *     reference_number?: string|null,
     *     notes?: string|null,
     *     event_id?: int|null,
     *     status?: string,
     *     posted_by?: int|null,
     *     lines: array<int, array{
     *         account_id: int,
     *         debit_amount?: float|string|null,
     *         credit_amount?: float|string|null,
     *         line_description_en?: string|null,
     *         line_description_bn?: string|null,
     *         event_id?: int|null,
     *         reference_type?: string|null,
     *         reference_id?: int|null,
     *     }>
     * } $data
     *
     * @throws ValidationException
     */
    public function createEntry(array $data): JournalEntry
    {
        $lines = $data['lines'] ?? [];

        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'lines' => ['A journal entry must contain at least two balanced lines (debit and credit).'],
            ]);
        }

        $totalDebit = 0.00;
        $totalCredit = 0.00;
        $normalizedLines = [];

        foreach ($lines as $index => $line) {
            $debit = round((float) ($line['debit_amount'] ?? ($line['entry_type'] === 'debit' ? $line['amount'] : 0)), 2);
            $credit = round((float) ($line['credit_amount'] ?? ($line['entry_type'] === 'credit' ? $line['amount'] : 0)), 2);

            if ($debit < 0 || $credit < 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => ['Debit and credit amounts must be non-negative.'],
                ]);
            }

            if ($debit == 0 && $credit == 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => ['A journal line must specify either a debit or credit amount greater than zero.'],
                ]);
            }

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    "lines.{$index}" => ['A single journal line cannot have both debit and credit amounts.'],
                ]);
            }

            $totalDebit += $debit;
            $totalCredit += $credit;

            $normalizedLines[] = [
                'financial_account_id' => $line['financial_account_id'] ?? $line['account_id'],
                'entry_type' => $debit > 0 ? JournalLine::TYPE_DEBIT : JournalLine::TYPE_CREDIT,
                'amount' => $debit > 0 ? $debit : $credit,
                'event_id' => $line['event_id'] ?? ($data['event_id'] ?? null),
                'description' => $line['description'] ?? $line['line_description_en'] ?? ($data['description'] ?? ($data['description_en'] ?? null)),
            ];
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        if (abs($totalDebit - $totalCredit) > 0.009) {
            throw ValidationException::withMessages([
                'lines' => [
                    sprintf('Journal entry is out of balance. Total Debit: %.2f, Total Credit: %.2f (Difference: %.2f).',
                        $totalDebit, $totalCredit, abs($totalDebit - $totalCredit)
                    ),
                ],
            ]);
        }

        return DB::transaction(function () use ($data, $normalizedLines, $totalDebit, $totalCredit) {
            $journalNumber = JournalEntry::generateJournalNumber($data['entry_date'] ?? null);
            $userId = $data['created_by'] ?? ($data['posted_by'] ?? (auth()->id() ?? 1));

            $journal = JournalEntry::create([
                'journal_number' => $journalNumber,
                'entry_date' => $data['entry_date'],
                'description' => $data['description'] ?? ($data['description_en'] ?? 'General Journal Entry'),
                'source_type' => $data['source_type'],
                'source_id' => $data['source_id'] ?? null,
                'status' => $data['status'] ?? JournalEntry::STATUS_POSTED,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'event_id' => $data['event_id'] ?? null,
                'created_by' => $userId,
                'posted_by' => $data['posted_by'] ?? $userId,
                'posted_at' => now(),
            ]);

            foreach ($normalizedLines as $lineData) {
                $journal->lines()->create($lineData);
            }

            AuditLogger::log(
                'journal_created',
                "Created journal entry {$journal->journal_number} (Debit: {$totalDebit}, Credit: {$totalCredit})",
                $journal,
                [
                    'journal_number' => $journal->journal_number,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'source_type' => $journal->source_type,
                    'source_id' => $journal->source_id,
                ]
            );

            return $journal->load('lines.account');
        });
    }

    /**
     * Create an inverse reversal journal entry for a posted journal entry.
     *
     * @throws ValidationException
     */
    public function createReversal(JournalEntry $originalJournal, int $reverserId, string $reason): JournalEntry
    {
        if ($originalJournal->status === JournalEntry::STATUS_REVERSED) {
            throw ValidationException::withMessages([
                'journal' => ["Journal entry {$originalJournal->journal_number} has already been reversed."],
            ]);
        }

        return DB::transaction(function () use ($originalJournal, $reverserId, $reason) {
            $originalJournal->loadMissing('lines');

            $reversalLines = [];
            foreach ($originalJournal->lines as $line) {
                // Invert debits and credits
                $reversalLines[] = [
                    'financial_account_id' => $line->financial_account_id,
                    'entry_type' => $line->entry_type === JournalLine::TYPE_DEBIT ? JournalLine::TYPE_CREDIT : JournalLine::TYPE_DEBIT,
                    'amount' => $line->amount,
                    'event_id' => $line->event_id,
                    'description' => "Reversal of {$originalJournal->journal_number}: ".($line->description ?? ''),
                ];
            }

            $reversalNumber = JournalEntry::generateJournalNumber();

            $reversalJournal = JournalEntry::create([
                'journal_number' => $reversalNumber,
                'entry_date' => now()->toDateString(),
                'description' => "Reversal of {$originalJournal->journal_number}. Reason: {$reason}",
                'source_type' => JournalEntry::SOURCE_REVERSAL,
                'source_id' => $originalJournal->id,
                'status' => JournalEntry::STATUS_POSTED,
                'total_debit' => $originalJournal->total_credit,
                'total_credit' => $originalJournal->total_debit,
                'event_id' => $originalJournal->event_id,
                'created_by' => $reverserId,
                'posted_by' => $reverserId,
                'posted_at' => now(),
                'reversed_journal_id' => $originalJournal->id,
                'reversal_reason' => $reason,
            ]);

            foreach ($reversalLines as $lineData) {
                $reversalJournal->lines()->create($lineData);
            }

            // Mark original journal as reversed
            $originalJournal->update([
                'status' => JournalEntry::STATUS_REVERSED,
                'reversal_reason' => $reason,
            ]);

            AuditLogger::log(
                'journal_reversed',
                "Reversed journal {$originalJournal->journal_number} via reversal voucher {$reversalJournal->journal_number}. Reason: {$reason}",
                $originalJournal,
                [
                    'status' => JournalEntry::STATUS_REVERSED,
                    'reversal_journal_number' => $reversalJournal->journal_number,
                    'reason' => $reason,
                ]
            );

            return $reversalJournal->load('lines.account');
        });
    }
}
