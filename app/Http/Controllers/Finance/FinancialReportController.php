<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FinancialAccount;
use App\Services\FinancialReportService;
use App\Services\IncomeExpenseReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService,
        protected IncomeExpenseReportService $incomeExpenseReportService
    ) {}

    public function trialBalance(Request $request): View
    {
        $this->authorize('view_financial_reports');

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $report = $this->reportService->getTrialBalance($startDate, $endDate);

        return view('finance.reports.trial_balance', compact('report', 'startDate', 'endDate'));
    }

    public function generalLedger(Request $request): View
    {
        $this->authorize('view_financial_reports');

        $accounts = FinancialAccount::orderBy('account_code')->get();
        $selectedAccountId = (int) ($request->get('account_id') ?? $accounts->first()?->id);
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $ledger = $selectedAccountId
            ? $this->reportService->getGeneralLedger($selectedAccountId, $startDate, $endDate)
            : null;

        return view('finance.reports.general_ledger', compact('accounts', 'selectedAccountId', 'ledger', 'startDate', 'endDate'));
    }

    public function incomeExpense(Request $request): View
    {
        $this->authorize('view_financial_reports');

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $eventId = $request->get('event_id') ? (int) $request->get('event_id') : null;

        $report = $this->reportService->getIncomeExpenseStatement($startDate, $endDate, $eventId);
        $events = Event::orderBy('start_date', 'desc')->get();

        return view('finance.reports.income_expense', compact('report', 'startDate', 'endDate', 'eventId', 'events'));
    }

    public function cashBook(Request $request): View
    {
        $this->authorize('view_financial_reports');

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $cashBook = $this->reportService->getCashBook($startDate, $endDate);

        return view('finance.reports.cash_book', compact('cashBook', 'startDate', 'endDate'));
    }

    public function accountStatement(Request $request): View
    {
        $this->authorize('view_financial_reports');

        $accounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_ASSET)
            ->where('account_code', '!=', FinancialAccount::CODE_CASH)
            ->orderBy('account_code')
            ->get();

        $selectedAccountId = (int) ($request->get('account_id') ?? $accounts->first()?->id);
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $statement = $selectedAccountId
            ? $this->reportService->getGeneralLedger($selectedAccountId, $startDate, $endDate)
            : null;

        return view('finance.reports.account_statement', compact('accounts', 'selectedAccountId', 'statement', 'startDate', 'endDate'));
    }

    public function accountBalances(): View
    {
        $this->authorize('view_financial_reports');

        $grouped = $this->reportService->getAccountBalances();

        return view('finance.reports.account_balances', compact('grouped'));
    }

    public function eventSummary(Request $request): View
    {
        $this->authorize('view_financial_reports');

        $eventId = $request->get('event_id') ? (int) $request->get('event_id') : null;
        $summaries = $this->reportService->getEventSummary($eventId);
        $events = Event::orderBy('start_date', 'desc')->get();

        return view('finance.reports.event_summary', compact('summaries', 'eventId', 'events'));
    }

    /**
     * Clear Income Report view & server-side DataTables handler.
     */
    public function incomeReport(Request $request): View|JsonResponse
    {
        $this->authorize('view_financial_reports');

        if ($request->ajax() || $request->wantsJson()) {
            if ($request->boolean('summary_only')) {
                return response()->json($this->incomeExpenseReportService->getIncomeSummaryCards($request));
            }

            return response()->json($this->incomeExpenseReportService->getIncomeDataTable($request));
        }

        $events = Event::orderBy('start_date', 'desc')->get();
        $summary = $this->incomeExpenseReportService->getIncomeSummaryCards($request);

        return view('finance.reports.income', compact('events', 'summary'));
    }

    /**
     * AJAX endpoint to render Income Offcanvas Details view.
     */
    public function incomeDetails(Request $request, string $type, int $id): View|JsonResponse
    {
        $this->authorize('view_financial_reports');

        $details = $this->incomeExpenseReportService->getIncomeDetails($type, $id);

        if ($request->wantsJson() && ! $request->ajax()) {
            return response()->json($details);
        }

        return view('finance.reports.partials.income_details', $details);
    }

    /**
     * Clear Expense Report view & server-side DataTables handler.
     */
    public function expenseReport(Request $request): View|JsonResponse
    {
        $this->authorize('view_financial_reports');

        if ($request->ajax() || $request->wantsJson()) {
            if ($request->boolean('summary_only')) {
                return response()->json($this->incomeExpenseReportService->getExpenseSummaryCards($request));
            }

            return response()->json($this->incomeExpenseReportService->getExpenseDataTable($request));
        }

        $events = Event::orderBy('start_date', 'desc')->get();
        $expenseCategories = FinancialAccount::where('account_type', FinancialAccount::TYPE_EXPENSE)
            ->orderBy('account_code')
            ->get();
        $summary = $this->incomeExpenseReportService->getExpenseSummaryCards($request);

        return view('finance.reports.expenses', compact('events', 'expenseCategories', 'summary'));
    }

    /**
     * AJAX endpoint to render Expense Offcanvas Details view.
     */
    public function expenseDetails(Request $request, int $id): View|JsonResponse
    {
        $this->authorize('view_financial_reports');

        $expense = $this->incomeExpenseReportService->getExpenseDetails($id);

        if ($request->wantsJson() && ! $request->ajax()) {
            return response()->json($expense);
        }

        return view('finance.reports.partials.expense_details', compact('expense'));
    }

    /**
     * AJAX Select2 endpoint for person & vendor search.
     */
    public function searchPeople(Request $request): JsonResponse
    {
        $this->authorize('view_financial_reports');

        return response()->json($this->incomeExpenseReportService->searchPeople($request));
    }
}
