<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFinancialAccountRequest;
use App\Http\Requests\UpdateFinancialAccountRequest;
use App\Models\FinancialAccount;
use App\Services\AuditLogger;
use App\Services\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialAccountController extends Controller
{
    public function __construct(
        protected FinancialReportService $reportService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('view_accounts');

        $grouped = $this->reportService->getAccountBalances();

        if ($request->ajax()) {
            $accounts = FinancialAccount::with('parent')->orderBy('account_code')->get();
            $data = $accounts->map(function ($acc) {
                return [
                    'id' => $acc->id,
                    'code' => $acc->code,
                    'name_en' => $acc->name_en,
                    'name_bn' => $acc->name_bn,
                    'account_type' => ucfirst($acc->account_type),
                    'normal_balance' => ucfirst($acc->normal_balance),
                    'balance' => number_format($acc->calculateBalance(), 2),
                    'is_system' => $acc->is_system,
                    'is_active' => $acc->is_active,
                    'parent_name' => $acc->parent?->name_en,
                ];
            });

            return response()->json(['data' => $data]);
        }

        $parentAccounts = FinancialAccount::orderBy('account_code')->get();

        return view('finance.accounts.index', compact('grouped', 'parentAccounts'));
    }

    public function store(StoreFinancialAccountRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('manage_accounts');

        $account = FinancialAccount::create(array_merge($request->validated(), [
            'is_system' => false,
            'is_active' => true,
        ]));

        AuditLogger::log(
            'account_created',
            "Created chart of account {$account->code} - {$account->name_en}",
            $account,
            $account->toArray()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('finance.account_created_successfully'),
                'account' => $account,
            ]);
        }

        return redirect()->route('finance.accounts.index')
            ->with('success', __('finance.account_created_successfully'));
    }

    public function show(FinancialAccount $account, Request $request): View
    {
        $this->authorize('view_accounts');

        $ledger = $this->reportService->getGeneralLedger($account->id, $request->get('start_date'), $request->get('end_date'));

        return view('finance.accounts.show', compact('account', 'ledger'));
    }

    public function update(UpdateFinancialAccountRequest $request, FinancialAccount $account): RedirectResponse|JsonResponse
    {
        $this->authorize('manage_accounts');

        $old = $account->toArray();
        $account->update($request->validated());

        AuditLogger::log(
            'account_updated',
            "Updated account {$account->code} - {$account->name_en}",
            $account,
            $account->toArray()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('finance.account_updated_successfully'),
                'account' => $account,
            ]);
        }

        return redirect()->route('finance.accounts.index')
            ->with('success', __('finance.account_updated_successfully'));
    }
}
