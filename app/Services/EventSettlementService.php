<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventSettlement;
use App\Models\FinancialAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventSettlementService
{
    public function __construct(
        protected EventCollectionReportService $reportService,
        protected JournalEntryService $journalService
    ) {}

    /**
     * Finalize event fund settlement, transferring net cash surplus to General Temple Fund.
     *
     * @throws ValidationException
     */
    public function settleEvent(Event $event, array $data, int $userId): EventSettlement
    {
        // 1. Event status check: must be closing or closed
        if (! in_array($event->status, [Event::STATUS_CLOSING, Event::STATUS_CLOSED], true)) {
            throw ValidationException::withMessages([
                'event' => [__('events.settlement_requires_closing_or_closed_status')],
            ]);
        }

        // 2. Idempotency check: prevent duplicate settlement
        if ($event->settlement()->where('status', EventSettlement::STATUS_COMPLETED)->exists()) {
            throw ValidationException::withMessages([
                'settlement' => [__('events.event_already_settled_error')],
            ]);
        }

        $settlementDate = $data['settlement_date'] ?? now()->toDateString();
        $notes = trim($data['notes'] ?? '');

        return DB::transaction(function () use ($event, $settlementDate, $notes, $userId) {
            /** @var Event $lockedEvent */
            $lockedEvent = Event::where('id', $event->id)->lockForUpdate()->firstOrFail();

            if ($lockedEvent->settlement()->where('status', EventSettlement::STATUS_COMPLETED)->exists()) {
                throw ValidationException::withMessages([
                    'settlement' => [__('events.event_already_settled_error')],
                ]);
            }

            // Calculate reconciled financial snapshot
            $metrics = $this->reportService->getMetrics($lockedEvent);
            $totalCollections = round((float) $metrics['total_verified_collections'], 2);
            $totalExpenses = round((float) $metrics['total_approved_expenses'], 2);
            $surplusAmount = round($totalCollections - $totalExpenses, 2);

            // General Temple Fund account (3010)
            $generalFundAccount = FinancialAccount::findByCode(FinancialAccount::CODE_GENERAL_FUND);
            if (! $generalFundAccount) {
                throw new \RuntimeException('General Temple Fund equity account (3010) not found in chart of accounts.');
            }

            $settlementNumber = EventSettlement::generateSettlementNumber($settlementDate);

            $settlement = EventSettlement::create([
                'settlement_number' => $settlementNumber,
                'event_id' => $lockedEvent->id,
                'settlement_date' => $settlementDate,
                'total_collections' => $totalCollections,
                'total_expenses' => $totalExpenses,
                'surplus_amount' => $surplusAmount,
                'destination_account_id' => $generalFundAccount->id,
                'status' => EventSettlement::STATUS_COMPLETED,
                'notes' => $notes,
                'settled_by' => $userId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            // If there is net surplus > 0, post balanced double-entry journal entry to transfer surplus to General Fund
            if ($surplusAmount > 0.009) {
                $lines = [
                    // Line 1: Debit General Fund with event dimension (releasing/clearing event surplus balance)
                    [
                        'account_id' => $generalFundAccount->id,
                        'debit_amount' => $surplusAmount,
                        'credit_amount' => 0.00,
                        'line_description_en' => "Settlement transfer out for {$lockedEvent->name_en} ({$lockedEvent->event_code}) surplus balance",
                        'line_description_bn' => "অনুষ্ঠান উদ্বৃত্ত তহবিল নিষ্পত্তি ও স্থানান্তর ({$lockedEvent->event_code})",
                        'event_id' => $lockedEvent->id,
                        'reference_type' => EventSettlement::class,
                        'reference_id' => $settlement->id,
                    ],
                    // Line 2: Credit General Fund without event dimension (unrestricted temple reserve)
                    [
                        'account_id' => $generalFundAccount->id,
                        'debit_amount' => 0.00,
                        'credit_amount' => $surplusAmount,
                        'line_description_en' => "Surplus transferred to unrestricted General Temple Fund from {$lockedEvent->name_en}",
                        'line_description_bn' => "সাধারণ মন্দির তহবিলে অনুষ্ঠান উদ্বৃত্ত জমা ({$lockedEvent->event_code})",
                        'event_id' => null,
                        'reference_type' => EventSettlement::class,
                        'reference_id' => $settlement->id,
                    ],
                ];

                $journal = $this->journalService->createEntry([
                    'entry_date' => $settlementDate,
                    'source_type' => EventSettlement::class,
                    'source_id' => $settlement->id,
                    'description' => "Event fund settlement for {$lockedEvent->name_en} ({$lockedEvent->event_code}) - Net surplus ৳".number_format($surplusAmount, 2).' transferred to General Temple Fund',
                    'event_id' => $lockedEvent->id,
                    'created_by' => $userId,
                    'posted_by' => $userId,
                    'lines' => $lines,
                ]);

                $settlement->updateQuietly([
                    'journal_entry_id' => $journal->id,
                ]);
            }

            AuditLogger::log(
                'event_funds_settled',
                "Settled funds for event {$lockedEvent->name_en} ({$lockedEvent->event_code}): Collections ৳{$totalCollections}, Expenses ৳{$totalExpenses}, Net Surplus ৳{$surplusAmount}",
                $settlement,
                $settlement->toArray()
            );

            return $settlement->fresh(['event', 'journalEntry', 'settledBy', 'destinationAccount']);
        });
    }
}
