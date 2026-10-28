<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FinancialAccount;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
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
}
