<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpeningBalanceRequest;
use App\Models\FinancialAccount;
use App\Models\JournalEntry;
use App\Services\FinancePostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OpeningBalanceController extends Controller
{
    public function __construct(
        protected FinancePostingService $postingService
    ) {}

    public function index(): View
    {
        $this->authorize('manage_opening_balances');

        $this->postingService->ensureAccountsExist();

        $accounts = FinancialAccount::where('account_code', '!=', FinancialAccount::CODE_OPENING_EQUITY)
            ->orderBy('account_code')
            ->get();

        $openingEntries = JournalEntry::with(['lines.account', 'creator'])
            ->where('source_type', JournalEntry::SOURCE_OPENING_BALANCE)
            ->orderBy('entry_date', 'desc')
            ->get();

        return view('finance.opening_balances.index', compact('accounts', 'openingEntries'));
    }

    public function store(StoreOpeningBalanceRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('manage_opening_balances');

        $account = FinancialAccount::findOrFail($request->input('account_id'));

        // Prevent duplicate opening balance for the same account
        $existing = JournalEntry::where('source_type', JournalEntry::SOURCE_OPENING_BALANCE)
            ->where('source_id', $account->id)
            ->where('status', JournalEntry::STATUS_POSTED)
            ->exists();

        if ($existing) {
            return back()->withErrors([
                'account_id' => __('finance.opening_balance_already_exists_for_account'),
            ]);
        }

        $journal = $this->postingService->postOpeningBalance(
            $account,
            $request->input('balance_type'),
            (float) $request->input('amount'),
            $request->input('entry_date'),
            $request->input('notes'),
            auth()->id()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('finance.opening_balance_posted_successfully'),
                'journal' => $journal,
            ]);
        }

        return redirect()->route('finance.opening-balances.index')
            ->with('success', __('finance.opening_balance_posted_successfully'));
    }
}
