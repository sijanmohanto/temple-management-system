<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFundTransferRequest;
use App\Models\FinancialAccount;
use App\Models\FundTransfer;
use App\Services\FundTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FundTransferController extends Controller
{
    public function __construct(
        protected FundTransferService $transferService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('view_transfers');

        if ($request->ajax()) {
            $query = FundTransfer::with(['fromAccount', 'toAccount', 'processor']);

            if ($request->filled('start_date')) {
                $query->where('transfer_date', '>=', $request->get('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->where('transfer_date', '<=', $request->get('end_date'));
            }

            $totalRecords = $query->count();

            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('transfer_number', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%");
                });
            }

            $filteredRecords = $query->count();

            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);

            $transfers = $query->orderBy('transfer_date', 'desc')
                ->orderBy('id', 'desc')
                ->skip($start)
                ->take($length)
                ->get();

            $data = $transfers->map(function ($t) {
                return [
                    'id' => $t->id,
                    'transfer_number' => $t->transfer_number,
                    'transfer_date' => $t->transfer_date->toDateString(),
                    'from_account' => $t->fromAccount?->name_en,
                    'to_account' => $t->toAccount?->name_en,
                    'amount' => number_format($t->amount, 2),
                    'fee_amount' => number_format($t->fee_amount, 2),
                    'status' => $t->status,
                    'reference' => $t->reference_number ?: '-',
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw'),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        $transfers = FundTransfer::with(['fromAccount', 'toAccount', 'processor', 'journalEntry'])
            ->orderBy('transfer_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25);

        return view('finance.transfers.index', compact('transfers'));
    }

    public function create(): View
    {
        $this->authorize('manage_transfers');

        $assetAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_ASSET)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        return view('finance.transfers.create', compact('assetAccounts'));
    }

    public function store(StoreFundTransferRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('manage_transfers');

        $transfer = $this->transferService->transferFunds($request->validated(), auth()->id());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('finance.transfer_completed_successfully'),
                'transfer' => $transfer,
            ]);
        }

        return redirect()->route('finance.transfers.index')
            ->with('success', __('finance.transfer_completed_successfully'));
    }

    public function reverse(Request $request, FundTransfer $transfer): RedirectResponse|JsonResponse
    {
        $this->authorize('reverse_transactions');

        $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $reversed = $this->transferService->reverseTransfer($transfer, auth()->id(), $request->input('reason'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('finance.transfer_reversed_successfully'),
                'transfer' => $reversed,
            ]);
        }

        return redirect()->route('finance.transfers.index')
            ->with('success', __('finance.transfer_reversed_successfully'));
    }
}
