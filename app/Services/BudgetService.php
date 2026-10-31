<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BudgetService
{
    /**
     * Create a new budget with line items.
     */
    public function createBudget(array $data, User $creator): Budget
    {
        $this->validateScopeUniqueness($data['budget_type'], (int) $data['fiscal_year'], $data['event_id'] ?? null);

        return DB::transaction(function () use ($data, $creator) {
            $fiscalYear = (int) $data['fiscal_year'];
            $budgetCode = Budget::generateBudgetCode($fiscalYear);

            $budget = Budget::create([
                'budget_code' => $budgetCode,
                'title_en' => $data['title_en'],
                'title_bn' => $data['title_bn'] ?? null,
                'budget_type' => $data['budget_type'],
                'fiscal_year' => $fiscalYear,
                'event_id' => $data['budget_type'] === Budget::TYPE_EVENT ? ($data['event_id'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'status' => Budget::STATUS_DRAFT,
                'version' => 1,
                'created_by' => $creator->id,
                'total_amount' => 0.00,
            ]);

            $totalAmount = 0.00;
            if (! empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $amount = round((float) ($item['allocated_amount'] ?? 0), 2);
                    if ($amount <= 0) {
                        continue;
                    }

                    $accountId = (int) $item['financial_account_id'];
                    $account = FinancialAccount::where('id', $accountId)
                        ->where('account_type', 'expense')
                        ->first();

                    if (! $account) {
                        throw ValidationException::withMessages([
                            'items' => [__('procurement.invalid_expense_account')],
                        ]);
                    }

                    BudgetItem::create([
                        'budget_id' => $budget->id,
                        'financial_account_id' => $accountId,
                        'allocated_amount' => $amount,
                        'notes' => $item['notes'] ?? null,
                    ]);

                    $totalAmount += $amount;
                }
            }

            $budget->update(['total_amount' => $totalAmount]);

            return $budget->load(['items.expenseAccount', 'event', 'creator']);
        });
    }

    /**
     * Update an existing draft or rejected budget.
     */
    public function updateBudget(Budget $budget, array $data, User $updater): Budget
    {
        if (! $budget->canBeEdited()) {
            throw ValidationException::withMessages([
                'budget' => [__('procurement.budget_cannot_be_edited')],
            ]);
        }

        $newType = $data['budget_type'] ?? $budget->budget_type;
        $newYear = (int) ($data['fiscal_year'] ?? $budget->fiscal_year);
        $newEventId = $newType === Budget::TYPE_EVENT ? ($data['event_id'] ?? null) : null;

        $this->validateScopeUniqueness($newType, $newYear, $newEventId, $budget->id);

        return DB::transaction(function () use ($budget, $data, $newType, $newYear, $newEventId) {
            $budget->update([
                'title_en' => $data['title_en'],
                'title_bn' => $data['title_bn'] ?? null,
                'budget_type' => $newType,
                'fiscal_year' => $newYear,
                'event_id' => $newEventId,
                'notes' => $data['notes'] ?? null,
            ]);

            // Replace line items
            $budget->items()->delete();

            $totalAmount = 0.00;
            if (! empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $amount = round((float) ($item['allocated_amount'] ?? 0), 2);
                    if ($amount <= 0) {
                        continue;
                    }

                    $accountId = (int) $item['financial_account_id'];
                    $account = FinancialAccount::where('id', $accountId)
                        ->where('account_type', 'expense')
                        ->first();

                    if (! $account) {
                        throw ValidationException::withMessages([
                            'items' => [__('procurement.invalid_expense_account')],
                        ]);
                    }

                    BudgetItem::create([
                        'budget_id' => $budget->id,
                        'financial_account_id' => $accountId,
                        'allocated_amount' => $amount,
                        'notes' => $item['notes'] ?? null,
                    ]);

                    $totalAmount += $amount;
                }
            }

            $budget->update(['total_amount' => $totalAmount]);

            return $budget->load(['items.expenseAccount', 'event', 'creator']);
        });
    }

    /**
     * Submit a budget for approval.
     */
    public function submitBudget(Budget $budget, User $submitter): Budget
    {
        if (! $budget->canBeSubmitted()) {
            throw ValidationException::withMessages([
                'budget' => [__('procurement.budget_cannot_be_submitted')],
            ]);
        }

        $budget->update([
            'status' => Budget::STATUS_SUBMITTED,
            'submitted_by' => $submitter->id,
            'submitted_at' => now(),
            'rejection_reason' => null,
        ]);

        return $budget;
    }

    /**
     * Authorize and approve a submitted budget.
     */
    public function approveBudget(Budget $budget, User $approver): Budget
    {
        if (! $budget->canBeApprovedBy($approver)) {
            throw ValidationException::withMessages([
                'budget' => [__('procurement.unauthorized_to_approve_budget')],
            ]);
        }

        return DB::transaction(function () use ($budget, $approver) {
            // Lock and check that no other approved budget exists for this scope
            $this->validateScopeUniqueness(
                $budget->budget_type,
                $budget->fiscal_year,
                $budget->event_id,
                $budget->id,
                true // check only approved
            );

            $budget->update([
                'status' => Budget::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            return $budget;
        });
    }

    /**
     * Reject a submitted budget with mandatory reason.
     */
    public function rejectBudget(Budget $budget, User $rejecter, string $reason): Budget
    {
        if (! $budget->canBeRejectedBy($rejecter)) {
            throw ValidationException::withMessages([
                'budget' => [__('procurement.unauthorized_to_reject_budget')],
            ]);
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => [__('procurement.rejection_reason_required')],
            ]);
        }

        $budget->update([
            'status' => Budget::STATUS_REJECTED,
            'rejected_by' => $rejecter->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $budget;
    }

    /**
     * Cancel a budget.
     */
    public function cancelBudget(Budget $budget, User $user, ?string $reason = null): Budget
    {
        if ($budget->isApproved()) {
            throw ValidationException::withMessages([
                'budget' => [__('procurement.approved_budget_cannot_be_cancelled')],
            ]);
        }

        $budget->update([
            'status' => Budget::STATUS_CANCELLED,
            'notes' => $reason ? ($budget->notes."\n[Cancelled]: ".$reason) : $budget->notes,
        ]);

        return $budget;
    }

    /**
     * Validate that no duplicate active/approved budget exists for the same scope.
     */
    protected function validateScopeUniqueness(
        string $budgetType,
        int $fiscalYear,
        ?int $eventId = null,
        ?int $excludeBudgetId = null,
        bool $approvedOnly = false
    ): void {
        $statuses = $approvedOnly
            ? [Budget::STATUS_APPROVED]
            : [Budget::STATUS_APPROVED, Budget::STATUS_SUBMITTED];

        $query = Budget::where('budget_type', $budgetType)
            ->whereIn('status', $statuses);

        if ($excludeBudgetId) {
            $query->where('id', '!=', $excludeBudgetId);
        }

        if ($budgetType === Budget::TYPE_EVENT) {
            if (! $eventId) {
                throw ValidationException::withMessages([
                    'event_id' => [__('procurement.event_required_for_event_budget')],
                ]);
            }
            $query->where('event_id', $eventId);
        } else {
            $query->where('fiscal_year', $fiscalYear)
                ->whereNull('event_id');
        }

        $existing = $query->first();

        if ($existing) {
            $scopeName = $budgetType === Budget::TYPE_EVENT
                ? ($existing->event?->title_en ?? 'Selected Event')
                : ($budgetType === Budget::TYPE_ANNUAL ? "Annual {$fiscalYear}" : "General Fund {$fiscalYear}");

            throw ValidationException::withMessages([
                'budget_type' => [__('procurement.duplicate_active_budget_exists', ['scope' => $scopeName, 'code' => $existing->budget_code])],
            ]);
        }
    }

    /**
     * Calculate Budget vs Actual ledger comparison.
     */
    public function calculateBudgetVsActual(Budget $budget): array
    {
        $budget->load(['items.expenseAccount', 'event']);

        // Gather base expense query for this budget scope
        $expenseQuery = Expense::query();
        if ($budget->budget_type === Budget::TYPE_EVENT && $budget->event_id) {
            $expenseQuery->where('event_id', $budget->event_id);
        } else {
            $expenseQuery->whereNull('event_id')
                ->whereYear('expense_date', $budget->fiscal_year);
        }

        // Fetch approved expenses grouped by account
        $approvedExpenses = (clone $expenseQuery)
            ->where('status', Expense::STATUS_APPROVED)
            ->select('financial_account_id', DB::raw('SUM(amount) as total_spent'))
            ->groupBy('financial_account_id')
            ->pluck('total_spent', 'financial_account_id')
            ->map(fn ($val) => (float) $val)
            ->all();

        // Fetch pending expenses grouped by account
        $pendingExpenses = (clone $expenseQuery)
            ->where('status', Expense::STATUS_PENDING)
            ->select('financial_account_id', DB::raw('SUM(amount) as total_pending'))
            ->groupBy('financial_account_id')
            ->pluck('total_pending', 'financial_account_id')
            ->map(fn ($val) => (float) $val)
            ->all();

        $rows = [];
        $totalBudget = 0.00;
        $totalActual = 0.00;
        $totalPending = 0.00;
        $processedAccountIds = [];

        foreach ($budget->items as $item) {
            $accountId = $item->financial_account_id;
            $processedAccountIds[] = $accountId;

            $allocated = (float) $item->allocated_amount;
            $actual = (float) ($approvedExpenses[$accountId] ?? 0.00);
            $pending = (float) ($pendingExpenses[$accountId] ?? 0.00);

            $remaining = max(0.00, $allocated - $actual);
            $overBudget = max(0.00, $actual - $allocated);
            $utilization = $allocated > 0 ? round(($actual / $allocated) * 100, 1) : 0.0;

            $totalBudget += $allocated;
            $totalActual += $actual;
            $totalPending += $pending;

            $rows[] = [
                'account_id' => $accountId,
                'account_code' => $item->expenseAccount?->account_code ?? 'N/A',
                'account_name' => $item->expenseAccount?->account_name ?? 'Unknown Account',
                'allocated' => $allocated,
                'allocated_formatted' => '৳'.number_format($allocated, 2),
                'actual' => $actual,
                'actual_formatted' => '৳'.number_format($actual, 2),
                'pending' => $pending,
                'pending_formatted' => '৳'.number_format($pending, 2),
                'remaining' => $remaining,
                'remaining_formatted' => '৳'.number_format($remaining, 2),
                'over_budget' => $overBudget,
                'over_budget_formatted' => '৳'.number_format($overBudget, 2),
                'utilization_pct' => $utilization,
                'is_over_budget' => $overBudget > 0,
                'is_unbudgeted' => false,
            ];
        }

        // Check for any approved expenses under accounts that were NOT in the budget
        foreach ($approvedExpenses as $accId => $spent) {
            if (! in_array($accId, $processedAccountIds, true) && $spent > 0) {
                $account = FinancialAccount::find($accId);
                $pending = (float) ($pendingExpenses[$accId] ?? 0.00);
                $totalActual += $spent;
                $totalPending += $pending;

                $rows[] = [
                    'account_id' => $accId,
                    'account_code' => $account?->account_code ?? 'N/A',
                    'account_name' => ($account?->account_name ?? 'Unbudgeted Account').' ('.__('procurement.unbudgeted').')',
                    'allocated' => 0.00,
                    'allocated_formatted' => '৳0.00',
                    'actual' => $spent,
                    'actual_formatted' => '৳'.number_format($spent, 2),
                    'pending' => $pending,
                    'pending_formatted' => '৳'.number_format($pending, 2),
                    'remaining' => 0.00,
                    'remaining_formatted' => '৳0.00',
                    'over_budget' => $spent,
                    'over_budget_formatted' => '৳'.number_format($spent, 2),
                    'utilization_pct' => 100.0,
                    'is_over_budget' => true,
                    'is_unbudgeted' => true,
                ];
            }
        }

        $totalRemaining = max(0.00, $totalBudget - $totalActual);
        $totalOverBudget = max(0.00, $totalActual - $totalBudget);
        $overallUtilization = $totalBudget > 0 ? round(($totalActual / $totalBudget) * 100, 1) : 0.0;

        return [
            'budget' => $budget,
            'items' => $rows,
            'summary' => [
                'total_budget' => $totalBudget,
                'total_budget_formatted' => '৳'.number_format($totalBudget, 2),
                'total_actual' => $totalActual,
                'total_actual_formatted' => '৳'.number_format($totalActual, 2),
                'total_pending' => $totalPending,
                'total_pending_formatted' => '৳'.number_format($totalPending, 2),
                'total_remaining' => $totalRemaining,
                'total_remaining_formatted' => '৳'.number_format($totalRemaining, 2),
                'total_over_budget' => $totalOverBudget,
                'total_over_budget_formatted' => '৳'.number_format($totalOverBudget, 2),
                'overall_utilization_pct' => $overallUtilization,
                'is_over_budget' => $totalOverBudget > 0,
            ],
        ];
    }
}
