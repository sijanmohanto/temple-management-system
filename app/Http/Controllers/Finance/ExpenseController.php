<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Event;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function __construct(
        protected ExpenseService $expenseService
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('view_expenses');

        if ($request->ajax()) {
            $query = Expense::with(['expenseAccount', 'paymentAccount', 'event', 'creator', 'approver']);

            if ($request->filled('status')) {
                $status = $request->get('status');
                if ($status === 'pending') {
                    $status = Expense::STATUS_PENDING;
                }
                $query->where('status', $status);
            }
            if ($request->filled('event_id')) {
                $query->where('event_id', $request->get('event_id'));
            }
            if ($request->filled('start_date')) {
                $query->where('expense_date', '>=', $request->get('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->where('expense_date', '<=', $request->get('end_date'));
            }

            $totalRecords = $query->count();

            if ($request->filled('search.value')) {
                $search = $request->input('search.value');
                $query->where(function ($q) use ($search) {
                    $q->where('expense_number', 'like', "%{$search}%")
                        ->orWhere('title_en', 'like', "%{$search}%")
                        ->orWhere('vendor_name', 'like', "%{$search}%");
                });
            }

            $filteredRecords = $query->count();

            $start = (int) $request->input('start', 0);
            $length = (int) $request->input('length', 25);
            $orderColIdx = (int) $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir', 'desc');

            $columns = ['expense_number', 'expense_date', 'title_en', 'expense_account_id', 'amount', 'status', 'created_at'];
            $orderCol = $columns[$orderColIdx] ?? 'expense_date';

            $expenses = $query->orderBy($orderCol, $orderDir)
                ->skip($start)
                ->take($length)
                ->get();

            $user = auth()->user();
            $canApprove = $user?->hasPermission('approve_expenses') ?? false;
            $canManage = ($user?->hasPermission('manage_expenses') || $user?->hasPermission('record_expenses')) ?? false;
            $canReverse = $user?->hasPermission('reverse_transactions') ?? false;

            $data = $expenses->map(function ($exp) use ($canApprove, $canManage, $canReverse) {
                $isPending = $exp->isPending();
                $isApproved = $exp->isApproved();

                return [
                    'id' => $exp->id,
                    'expense_number' => $exp->expense_number,
                    'expense_date' => $exp->expense_date->toDateString(),
                    'title' => $exp->title_en,
                    'category' => $exp->expenseAccount?->name_en,
                    'paid_from' => $exp->paymentAccount?->name_en,
                    'amount' => number_format($exp->amount, 2),
                    'raw_amount' => (float) $exp->amount,
                    'status' => $exp->status,
                    'vendor' => $exp->vendor_name ?: '-',
                    'view_url' => route('finance.expenses.show', $exp),
                    'edit_url' => route('finance.expenses.edit', $exp),
                    'approve_url' => route('finance.expenses.approve', $exp),
                    'reject_url' => route('finance.expenses.reject', $exp),
                    'reverse_url' => route('finance.expenses.reverse', $exp),
                    'is_pending' => $isPending,
                    'is_approved' => $isApproved,
                    'can_approve' => $canApprove,
                    'can_manage' => $canManage,
                    'can_reverse' => $canReverse,
                ];
            });

            return response()->json([
                'draw' => (int) $request->input('draw'),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        $events = Event::orderBy('start_date', 'desc')->get();

        return view('finance.expenses.index', compact('events'));
    }

    public function create(): View
    {
        $this->authorize('record_expenses');

        $expenseAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_EXPENSE)
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

        return view('finance.expenses.create', compact('expenseAccounts', 'assetAccounts', 'events'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('record_expenses');

        $expense = $this->expenseService->createExpense($request->validated(), auth()->id());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('finance.expense_created_successfully'),
                'expense' => $expense,
            ]);
        }

        return redirect()->route('finance.expenses.show', $expense)
            ->with('success', __('finance.expense_created_successfully'));
    }

    public function show(Expense $expense): View
    {
        $this->authorize('view_expenses');

        $expense->load(['expenseAccount', 'paymentAccount', 'event', 'creator', 'approver', 'rejecter', 'journalEntry.lines.account']);

        return view('finance.expenses.show', compact('expense'));
    }

    public function edit(Expense $expense): View|RedirectResponse
    {
        $this->authorize('manage_expenses');

        if (! $expense->isPending()) {
            return redirect()->route('finance.expenses.show', $expense)
                ->with('error', __('finance.only_pending_expenses_can_be_edited'));
        }

        $expenseAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_EXPENSE)
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

        return view('finance.expenses.edit', compact('expense', 'expenseAccounts', 'assetAccounts', 'events'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse|JsonResponse
    {
        if (! $expense->isPending()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('finance.only_pending_expenses_can_be_edited'),
                ], 422);
            }

            return redirect()->route('finance.expenses.show', $expense)
                ->with('error', __('finance.only_pending_expenses_can_be_edited'));
        }

        $updatedExpense = $this->expenseService->updateExpense($expense, $request->validated(), auth()->id());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('finance.expense_updated_successfully'),
                'expense' => $updatedExpense,
            ]);
        }

        return redirect()->route('finance.expenses.show', $updatedExpense)
            ->with('success', __('finance.expense_updated_successfully'));
    }

    public function approve(Request $request, Expense $expense): RedirectResponse|JsonResponse
    {
        $this->authorize('approve_expenses');

        if (! $expense->isPending()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending expenses can be approved.',
                ], 422);
            }

            return redirect()->route('finance.expenses.show', $expense)
                ->with('error', 'Only pending expenses can be approved.');
        }

        $note = $request->input('note');
        $approvedExpense = $this->expenseService->approveExpense($expense, auth()->id(), $note);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('finance.expense_approved_successfully'),
                'expense' => $approvedExpense,
            ]);
        }

        return redirect()->route('finance.expenses.show', $approvedExpense)
            ->with('success', __('finance.expense_approved_successfully'));
    }

    public function reject(Request $request, Expense $expense): RedirectResponse|JsonResponse
    {
        $this->authorize('approve_expenses');

        if (! $expense->isPending()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending expenses can be rejected.',
                ], 422);
            }

            return redirect()->route('finance.expenses.show', $expense)
                ->with('error', 'Only pending expenses can be rejected.');
        }

        $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $rejectedExpense = $this->expenseService->rejectExpense($expense, auth()->id(), $request->input('reason'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('finance.expense_rejected_successfully'),
                'expense' => $rejectedExpense,
            ]);
        }

        return redirect()->route('finance.expenses.show', $rejectedExpense)
            ->with('success', __('finance.expense_rejected_successfully'));
    }

    public function reverse(Request $request, Expense $expense): RedirectResponse|JsonResponse
    {
        $this->authorize('reverse_transactions');

        $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $reversedExpense = $this->expenseService->reverseExpense($expense, auth()->id(), $request->input('reason'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('finance.expense_reversed_successfully'),
                'expense' => $reversedExpense,
            ]);
        }

        return redirect()->route('finance.expenses.show', $reversedExpense)
            ->with('success', __('finance.expense_reversed_successfully'));
    }
}
