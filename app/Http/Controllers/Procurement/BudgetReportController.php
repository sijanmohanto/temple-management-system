<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetReportController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * Display Budget vs Actual ledger comparison report.
     */
    public function budgetVsActual(Request $request): View
    {
        if (! auth()->user()->hasPermission('view_budgets')) {
            abort(403, __('common.unauthorized'));
        }

        $approvedBudgets = Budget::where('status', Budget::STATUS_APPROVED)
            ->with('event')
            ->orderByDesc('fiscal_year')
            ->orderByDesc('id')
            ->get();

        $selectedBudgetId = (int) $request->input('budget_id', 0);
        $selectedBudget = null;

        if ($selectedBudgetId > 0) {
            $selectedBudget = Budget::find($selectedBudgetId);
        } elseif ($approvedBudgets->isNotEmpty()) {
            $selectedBudget = $approvedBudgets->first();
        }

        $reportData = null;
        if ($selectedBudget) {
            $reportData = $this->budgetService->calculateBudgetVsActual($selectedBudget);
        }

        return view('procurement.reports.budget-vs-actual', compact('approvedBudgets', 'selectedBudget', 'reportData'));
    }
}
