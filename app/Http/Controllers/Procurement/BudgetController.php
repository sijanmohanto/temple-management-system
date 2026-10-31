<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreBudgetRequest;
use App\Http\Requests\Procurement\UpdateBudgetRequest;
use App\Models\Budget;
use App\Models\Event;
use App\Models\FinancialAccount;
use App\Services\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetService $budgetService
    ) {}

    /**
     * Display a listing of budgets.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizePermission('view_budgets');

        if ($request->ajax() || $request->wantsJson()) {
            return $this->getDataTableResponse($request);
        }

        $summary = [
            'total_budgets' => Budget::count(),
            'approved_budgets' => Budget::where('status', Budget::STATUS_APPROVED)->count(),
            'pending_budgets' => Budget::where('status', Budget::STATUS_SUBMITTED)->count(),
            'total_approved_amount' => '৳'.number_format((float) Budget::where('status', Budget::STATUS_APPROVED)->sum('total_amount'), 2),
        ];

        $events = Event::orderBy('start_date', 'desc')->get();
        $years = Budget::distinct()->orderByDesc('fiscal_year')->pluck('fiscal_year')->all();
        if (! in_array((int) now()->year, $years, true)) {
            array_unshift($years, (int) now()->year);
        }

        return view('procurement.budgets.index', compact('summary', 'events', 'years'));
    }

    /**
     * Show the form for creating a new budget.
     */
    public function create(): View
    {
        $this->authorizePermission('manage_budgets');

        $expenseAccounts = FinancialAccount::where('account_type', 'expense')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $events = Event::whereIn('status', ['draft', 'upcoming', 'active', 'closing'])
            ->orderBy('start_date', 'desc')
            ->get();

        return view('procurement.budgets.create', compact('expenseAccounts', 'events'));
    }

    /**
     * Store a newly created budget in storage.
     */
    public function store(StoreBudgetRequest $request): RedirectResponse
    {
        $budget = $this->budgetService->createBudget($request->validated(), $request->user());

        session()->flash('success', __('procurement.budget_created_successfully', ['code' => $budget->budget_code]));

        return redirect()->route('procurement.budgets.show', $budget);
    }

    /**
     * Display the specified budget.
     */
    public function show(Budget $budget): View
    {
        $this->authorizePermission('view_budgets');

        $budget->load(['items.expenseAccount', 'event', 'creator', 'submitter', 'approver', 'rejecter']);

        $variance = null;
        if ($budget->isApproved()) {
            $variance = $this->budgetService->calculateBudgetVsActual($budget);
        }

        return view('procurement.budgets.show', compact('budget', 'variance'));
    }

    /**
     * Show the form for editing the specified budget.
     */
    public function edit(Budget $budget): View
    {
        $this->authorizePermission('manage_budgets');

        if (! $budget->canBeEdited()) {
            abort(403, __('procurement.budget_cannot_be_edited'));
        }

        $budget->load('items.expenseAccount');

        $expenseAccounts = FinancialAccount::where('account_type', 'expense')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $events = Event::orderBy('start_date', 'desc')->get();

        return view('procurement.budgets.edit', compact('budget', 'expenseAccounts', 'events'));
    }

    /**
     * Update the specified budget in storage.
     */
    public function update(UpdateBudgetRequest $request, Budget $budget): RedirectResponse
    {
        $budget = $this->budgetService->updateBudget($budget, $request->validated(), $request->user());

        session()->flash('success', __('procurement.budget_updated_successfully', ['code' => $budget->budget_code]));

        return redirect()->route('procurement.budgets.show', $budget);
    }

    /**
     * Submit budget for approval.
     */
    public function submit(Budget $budget, Request $request): RedirectResponse
    {
        $this->authorizePermission('manage_budgets');

        $this->budgetService->submitBudget($budget, $request->user());

        session()->flash('success', __('procurement.budget_submitted_successfully', ['code' => $budget->budget_code]));

        return redirect()->back();
    }

    /**
     * Approve submitted budget.
     */
    public function approve(Budget $budget, Request $request): RedirectResponse
    {
        $this->authorizePermission('approve_budgets');

        $this->budgetService->approveBudget($budget, $request->user());

        session()->flash('success', __('procurement.budget_approved_successfully', ['code' => $budget->budget_code]));

        return redirect()->back();
    }

    /**
     * Reject submitted budget.
     */
    public function reject(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizePermission('approve_budgets');

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $this->budgetService->rejectBudget($budget, $request->user(), $request->input('rejection_reason'));

        session()->flash('warning', __('procurement.budget_rejected_successfully', ['code' => $budget->budget_code]));

        return redirect()->back();
    }

    /**
     * Cancel a draft or submitted budget.
     */
    public function cancel(Request $request, Budget $budget): RedirectResponse
    {
        $this->authorizePermission('manage_budgets');

        $this->budgetService->cancelBudget($budget, $request->user(), $request->input('cancel_reason'));

        session()->flash('info', __('procurement.budget_cancelled_successfully', ['code' => $budget->budget_code]));

        return redirect()->back();
    }

    /**
     * Server-side DataTables AJAX response.
     */
    protected function getDataTableResponse(Request $request): JsonResponse
    {
        $query = Budget::with(['event', 'creator', 'items']);

        // Filter: Status
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        // Filter: Budget Type
        if ($request->filled('budget_type') && $request->input('budget_type') !== 'all') {
            $query->where('budget_type', $request->input('budget_type'));
        }

        // Filter: Fiscal Year
        if ($request->filled('fiscal_year') && $request->input('fiscal_year') !== 'all') {
            $query->where('fiscal_year', (int) $request->input('fiscal_year'));
        }

        // Filter: Event
        if ($request->filled('event_id') && $request->input('event_id') !== 'all') {
            $query->where('event_id', (int) $request->input('event_id'));
        }

        // Global search
        $search = trim((string) ($request->input('search.value') ?? $request->input('search', '')));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('budget_code', 'like', "%{$search}%")
                    ->orWhere('title_en', 'like', "%{$search}%")
                    ->orWhere('title_bn', 'like', "%{$search}%")
                    ->orWhereHas('event', function ($eq) use ($search) {
                        $eq->where('name_en', 'like', "%{$search}%")
                            ->orWhere('name_bn', 'like', "%{$search}%");
                    });
            });
        }

        $recordsTotal = Budget::count();
        $recordsFiltered = (clone $query)->count();

        // 7 columns: 0=code, 1=title, 2=type, 3=scope, 4=total, 5=status, 6=actions
        $orderColIdx = (int) $request->input('order.0.column', 0);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $orderCol = match ($orderColIdx) {
            0 => 'budget_code',
            1 => 'title_en',
            2 => 'budget_type',
            3 => 'fiscal_year',
            4 => 'total_amount',
            5 => 'status',
            default => 'id',
        };

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 15);

        $budgets = $query->orderBy($orderCol, $orderDir)
            ->skip($start)
            ->take($length)
            ->get();

        $user = $request->user();
        $data = [];

        foreach ($budgets as $b) {
            $scopeText = $b->budget_type === Budget::TYPE_EVENT
                ? ($b->event?->title_en ?? 'Event')
                : $b->fiscal_year;

            $actions = '<div class="btn-group btn-group-sm">';
            $actions .= '<a href="'.route('procurement.budgets.show', $b).'" class="btn btn-outline-primary" title="'.__('common.view').'"><i class="bi bi-eye"></i></a>';

            if ($b->canBeEdited() && $user->hasPermission('manage_budgets')) {
                $actions .= '<a href="'.route('procurement.budgets.edit', $b).'" class="btn btn-outline-secondary" title="'.__('common.edit').'"><i class="bi bi-pencil"></i></a>';
            }

            if ($b->isApproved()) {
                $actions .= '<a href="'.route('procurement.reports.budget-vs-actual', ['budget_id' => $b->id]).'" class="btn btn-outline-success" title="'.__('procurement.budget_vs_actual').'"><i class="bi bi-graph-up"></i></a>';
            }

            $actions .= '</div>';

            $data[] = [
                'budget_code' => '<a href="'.route('procurement.budgets.show', $b).'" class="fw-bold font-monospace text-primary text-decoration-none">'.e($b->budget_code).'</a>',
                'title' => '<div class="fw-semibold">'.e($b->title).'</div><small class="text-muted">'.$b->items->count().' '.__('procurement.items').'</small>',
                'budget_type' => '<span class="badge bg-light text-dark border">'.e($b->type_label).'</span>',
                'scope_period' => '<span class="fw-medium">'.e($scopeText).'</span>',
                'total_amount' => '৳'.number_format((float) $b->total_amount, 2),
                'status' => $b->status_badge,
                'actions' => $actions,
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    /**
     * Check user permission or abort 403.
     */
    protected function authorizePermission(string $permission): void
    {
        if (! auth()->user()->hasPermission($permission)) {
            abort(403, __('common.unauthorized'));
        }
    }
}
