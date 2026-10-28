<?php

namespace App\Services;

use App\Models\Event;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;

class FinancialReportService
{
    /**
     * Generate the Trial Balance report.
     *
     * @return array{
     *     start_date: string|null,
     *     end_date: string|null,
     *     accounts: array,
     *     total_debit: float,
     *     total_credit: float,
     *     is_balanced: bool,
     *     difference: float
     * }
     */
    public function getTrialBalance(?string $startDate = null, ?string $endDate = null): array
    {
        $accounts = FinancialAccount::orderBy('account_code')->get();

        $rows = [];
        $grandDebit = 0.00;
        $grandCredit = 0.00;

        foreach ($accounts as $account) {
            $query = JournalLine::where('financial_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                    $q->where('status', JournalEntry::STATUS_POSTED);
                    if ($startDate) {
                        $q->where('entry_date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $q->where('entry_date', '<=', $endDate);
                    }
                });

            $totalDebit = (float) (clone $query)->where('entry_type', JournalLine::TYPE_DEBIT)->sum('amount');
            $totalCredit = (float) (clone $query)->where('entry_type', JournalLine::TYPE_CREDIT)->sum('amount');

            // Net balance for trial balance
            if ($account->normal_balance === FinancialAccount::BALANCE_DEBIT) {
                $net = $totalDebit - $totalCredit;
                $displayDebit = $net >= 0 ? $net : 0.00;
                $displayCredit = $net < 0 ? abs($net) : 0.00;
            } else {
                $net = $totalCredit - $totalDebit;
                $displayCredit = $net >= 0 ? $net : 0.00;
                $displayDebit = $net < 0 ? abs($net) : 0.00;
            }

            // Include if there has been any activity or non-zero balance
            if ($totalDebit > 0 || $totalCredit > 0 || $displayDebit > 0 || $displayCredit > 0) {
                $rows[] = [
                    'account_id' => $account->id,
                    'code' => $account->code,
                    'name_en' => $account->name_en,
                    'name_bn' => $account->name_bn,
                    'type' => $account->account_type,
                    'normal_balance' => $account->normal_balance,
                    'debit' => round($displayDebit, 2),
                    'credit' => round($displayCredit, 2),
                ];

                $grandDebit += $displayDebit;
                $grandCredit += $displayCredit;
            }
        }

        $grandDebit = round($grandDebit, 2);
        $grandCredit = round($grandCredit, 2);
        $difference = round(abs($grandDebit - $grandCredit), 2);

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'accounts' => $rows,
            'total_debit' => $grandDebit,
            'total_credit' => $grandCredit,
            'is_balanced' => $difference < 0.01,
            'difference' => $difference,
        ];
    }

    /**
     * Generate General Ledger report for a specific account.
     */
    public function getGeneralLedger(int $accountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $account = FinancialAccount::findOrFail($accountId);

        // Calculate opening balance before startDate
        $openingDebit = 0.00;
        $openingCredit = 0.00;

        if ($startDate) {
            $priorQuery = JournalLine::where('financial_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($startDate) {
                    $q->where('status', JournalEntry::STATUS_POSTED)
                        ->where('entry_date', '<', $startDate);
                });

            $openingDebit = (float) (clone $priorQuery)->where('entry_type', JournalLine::TYPE_DEBIT)->sum('amount');
            $openingCredit = (float) (clone $priorQuery)->where('entry_type', JournalLine::TYPE_CREDIT)->sum('amount');
        }

        $openingBalance = $account->normal_balance === FinancialAccount::BALANCE_DEBIT
            ? ($openingDebit - $openingCredit)
            : ($openingCredit - $openingDebit);

        // Lines in range
        $linesQuery = JournalLine::with(['journalEntry', 'event'])
            ->where('financial_account_id', $account->id)
            ->whereHas('journalEntry', function ($q) use ($startDate, $endDate) {
                $q->where('status', JournalEntry::STATUS_POSTED);
                if ($startDate) {
                    $q->where('entry_date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('entry_date', '<=', $endDate);
                }
            })
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->orderBy('journal_entries.entry_date', 'asc')
            ->orderBy('journal_entries.id', 'asc')
            ->select('journal_lines.*');

        $lines = $linesQuery->get();

        $runningBalance = $openingBalance;
        $formattedLines = [];
        $totalPeriodDebit = 0.00;
        $totalPeriodCredit = 0.00;

        foreach ($lines as $line) {
            $debit = $line->entry_type === JournalLine::TYPE_DEBIT ? (float) $line->amount : 0.00;
            $credit = $line->entry_type === JournalLine::TYPE_CREDIT ? (float) $line->amount : 0.00;

            if ($account->normal_balance === FinancialAccount::BALANCE_DEBIT) {
                $runningBalance += ($debit - $credit);
            } else {
                $runningBalance += ($credit - $debit);
            }

            $totalPeriodDebit += $debit;
            $totalPeriodCredit += $credit;

            $formattedLines[] = [
                'id' => $line->id,
                'entry_date' => $line->journalEntry->entry_date->toDateString(),
                'journal_number' => $line->journalEntry->journal_number,
                'description' => $line->description ?: $line->journalEntry->description,
                'event_title' => $line->event?->title_en,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => round($runningBalance, 2),
            ];
        }

        return [
            'account' => $account,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => round($openingBalance, 2),
            'lines' => $formattedLines,
            'total_debit' => round($totalPeriodDebit, 2),
            'total_credit' => round($totalPeriodCredit, 2),
            'closing_balance' => round($runningBalance, 2),
        ];
    }

    /**
     * Generate Income and Expense Statement (Statement of Activities).
     */
    public function getIncomeExpenseStatement(?string $startDate = null, ?string $endDate = null, ?int $eventId = null): array
    {
        $accounts = FinancialAccount::whereIn('account_type', [
            FinancialAccount::TYPE_INCOME,
            FinancialAccount::TYPE_EXPENSE,
        ])->orderBy('account_code')->get();

        $incomeRows = [];
        $expenseRows = [];
        $totalIncome = 0.00;
        $totalExpense = 0.00;

        foreach ($accounts as $account) {
            $query = JournalLine::where('financial_account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($startDate, $endDate, $eventId) {
                    $q->where('status', JournalEntry::STATUS_POSTED);
                    if ($startDate) {
                        $q->where('entry_date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $q->where('entry_date', '<=', $endDate);
                    }
                    if ($eventId) {
                        $q->where('event_id', $eventId);
                    }
                });

            if ($eventId) {
                $query->where(function ($q) use ($eventId) {
                    $q->where('event_id', $eventId)
                        ->orWhereHas('journalEntry', fn ($sub) => $sub->where('event_id', $eventId));
                });
            }

            $debit = (float) (clone $query)->where('entry_type', JournalLine::TYPE_DEBIT)->sum('amount');
            $credit = (float) (clone $query)->where('entry_type', JournalLine::TYPE_CREDIT)->sum('amount');

            if ($account->account_type === FinancialAccount::TYPE_INCOME) {
                $amount = $credit - $debit; // Net Income
                if ($amount != 0) {
                    $incomeRows[] = [
                        'code' => $account->code,
                        'name_en' => $account->name_en,
                        'name_bn' => $account->name_bn,
                        'amount' => round($amount, 2),
                    ];
                    $totalIncome += $amount;
                }
            } else {
                $amount = $debit - $credit; // Net Expense
                if ($amount != 0) {
                    $expenseRows[] = [
                        'code' => $account->code,
                        'name_en' => $account->name_en,
                        'name_bn' => $account->name_bn,
                        'amount' => round($amount, 2),
                    ];
                    $totalExpense += $amount;
                }
            }
        }

        $totalIncome = round($totalIncome, 2);
        $totalExpense = round($totalExpense, 2);
        $netSurplus = round($totalIncome - $totalExpense, 2);

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'event' => $eventId ? Event::find($eventId) : null,
            'incomes' => $incomeRows,
            'total_income' => $totalIncome,
            'expenses' => $expenseRows,
            'total_expense' => $totalExpense,
            'net_surplus' => $netSurplus,
        ];
    }

    /**
     * Generate Cash Book report (Account 1010).
     */
    public function getCashBook(?string $startDate = null, ?string $endDate = null): array
    {
        $cashAccount = FinancialAccount::findByCode(FinancialAccount::CODE_CASH);
        if (! $cashAccount) {
            return [];
        }

        return $this->getGeneralLedger($cashAccount->id, $startDate, $endDate);
    }

    /**
     * Generate Event-wise Financial Summary.
     */
    public function getEventSummary(?int $eventId = null): array
    {
        $eventsQuery = Event::with('category')->orderBy('start_date', 'desc');
        if ($eventId) {
            $eventsQuery->where('id', $eventId);
        }

        $events = $eventsQuery->get();
        $summaries = [];

        foreach ($events as $event) {
            $report = $this->getIncomeExpenseStatement(null, null, $event->id);

            $summaries[] = [
                'event' => $event,
                'total_income' => $report['total_income'],
                'total_expense' => $report['total_expense'],
                'net_surplus' => $report['net_surplus'],
                'incomes' => $report['incomes'],
                'expenses' => $report['expenses'],
                'budget' => (float) ($event->budget ?? 0.00),
            ];
        }

        return $summaries;
    }

    /**
     * Get live account balances for all accounts grouped by type.
     *
     * @return array<string, array{type_title: string, accounts: array, total: float}>
     */
    public function getAccountBalances(): array
    {
        $accounts = FinancialAccount::orderBy('account_code')->get();

        $grouped = [
            FinancialAccount::TYPE_ASSET => ['type_title' => 'Assets', 'accounts' => [], 'total' => 0.00],
            FinancialAccount::TYPE_LIABILITY => ['type_title' => 'Liabilities', 'accounts' => [], 'total' => 0.00],
            FinancialAccount::TYPE_EQUITY => ['type_title' => 'Equity & Fund Balance', 'accounts' => [], 'total' => 0.00],
            FinancialAccount::TYPE_INCOME => ['type_title' => 'Income', 'accounts' => [], 'total' => 0.00],
            FinancialAccount::TYPE_EXPENSE => ['type_title' => 'Expenses', 'accounts' => [], 'total' => 0.00],
        ];

        foreach ($accounts as $account) {
            $balance = $account->calculateBalance();
            $grouped[$account->account_type]['accounts'][] = [
                'account' => $account,
                'balance' => $balance,
            ];
            $grouped[$account->account_type]['total'] += $balance;
        }

        foreach ($grouped as $key => $group) {
            $grouped[$key]['total'] = round($group['total'], 2);
        }

        return $grouped;
    }
}
