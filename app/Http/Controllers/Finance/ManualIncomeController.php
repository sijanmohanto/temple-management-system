<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManualIncomeRequest;
use App\Models\Event;
use App\Models\FinancialAccount;
use App\Models\ManualIncome;
use App\Services\AuditLogger;
use App\Services\FinancePostingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManualIncomeController extends Controller
{
    public function __construct(
        protected FinancePostingService $postingService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('view_income');

        if ($request->ajax()) {
            $query = ManualIncome::with(['incomeAccount', 'depositAccount', 'event', 'creator']);

            if ($request->filled('start_date')) {
                $query->where('income_date', '>=', $request->get('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->where('income_date', '<=', $request->get('end_date'));
            }

            $totalRecords = $query->count();

            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('income_number', 'like', "%{$search}%")
                        ->orWhere('title_en', 'like', "%{$search}%")
                        ->orWhere('payer_name', 'like', "%{$search}%");
                });
            }

            $filteredRecords = $query->count();

            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);

            $incomes = $query->orderBy('income_date', 'desc')
                ->orderBy('id', 'desc')
                ->skip($start)
                ->take($length)
                ->get();

            $data = $incomes->map(function ($inc) {
                return [
                    'id' => $inc->id,
                    'income_number' => $inc->income_number,
                    'income_date' => $inc->income_date->toDateString(),
                    'title' => $inc->title_en,
                    'category' => $inc->incomeAccount?->name_en,
                    'deposited_to' => $inc->depositAccount?->name_en,
                    'amount' => number_format($inc->amount, 2),
                    'payer' => $inc->payer_name ?: '-',
                    'method' => ucfirst(str_replace('_', ' ', $inc->payment_method)),
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw'),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        $incomes = ManualIncome::with(['incomeAccount', 'depositAccount', 'event', 'creator', 'journalEntry'])
            ->orderBy('income_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('finance.income.index', compact('incomes'));
    }

    public function create(): View
    {
        $this->authorize('record_income');

        $incomeAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_INCOME)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $assetAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_ASSET)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $events = Event::where('status', '!=', Event::STATUS_DRAFT)
            ->orderBy('start_date', 'desc')
            ->get();

        return view('finance.income.create', compact('incomeAccounts', 'assetAccounts', 'events'));
    }

    public function store(StoreManualIncomeRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('record_income');

        $data = $request->validated();
        $incomeNumber = ManualIncome::generateIncomeNumber($data['income_date']);

        $income = ManualIncome::create(array_merge($data, [
            'income_number' => $incomeNumber,
            'created_by' => auth()->id(),
        ]));

        $this->postingService->postManualIncome($income, auth()->id());

        AuditLogger::log(
            'manual_income_recorded',
            "Recorded miscellaneous income {$income->income_number} for amount {$income->amount}",
            $income,
            $income->toArray()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('finance.income_recorded_successfully'),
                'income' => $income,
            ]);
        }

        return redirect()->route('finance.income.index')
            ->with('success', __('finance.income_recorded_successfully'));
    }
}
