<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StorePurchaseRequest;
use App\Http\Requests\Procurement\UpdatePurchaseRequest;
use App\Models\Budget;
use App\Models\Event;
use App\Models\FinancialAccount;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Services\PurchaseRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    public function __construct(
        protected PurchaseRequestService $requestService
    ) {}

    /**
     * Display a listing of purchase requests.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizePermission('view_purchase_requests');

        if ($request->ajax() || $request->wantsJson()) {
            return $this->getDataTableResponse($request);
        }

        $summary = [
            'total_requests' => PurchaseRequest::count(),
            'approved_requests' => PurchaseRequest::where('status', PurchaseRequest::STATUS_APPROVED)->count(),
            'pending_requests' => PurchaseRequest::where('status', PurchaseRequest::STATUS_SUBMITTED)->count(),
            'total_approved_amount' => '৳'.number_format((float) PurchaseRequest::where('status', PurchaseRequest::STATUS_APPROVED)->sum('total_estimated_amount'), 2),
        ];

        $events = Event::orderBy('start_date', 'desc')->get();

        return view('procurement.requests.index', compact('summary', 'events'));
    }

    /**
     * Show creation form.
     */
    public function create(): View
    {
        $this->authorizePermission('manage_purchase_requests');

        $expenseAccounts = FinancialAccount::where('account_type', 'expense')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $approvedBudgets = Budget::where('status', Budget::STATUS_APPROVED)
            ->orderBy('fiscal_year', 'desc')
            ->get();

        $events = Event::orderBy('start_date', 'desc')->get();
        $users = User::where('status', 'active')->orderBy('name')->get();

        return view('procurement.requests.create', compact('expenseAccounts', 'approvedBudgets', 'events', 'users'));
    }

    /**
     * Store newly created purchase request.
     */
    public function store(StorePurchaseRequest $request): RedirectResponse|JsonResponse
    {
        $purchaseRequest = $this->requestService->createRequest($request->validated(), $request->user());

        session()->flash('success', __('procurement.request_created_successfully', ['code' => $purchaseRequest->request_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.request_created_successfully', ['code' => $purchaseRequest->request_number]),
                'purchase_request' => $purchaseRequest,
            ]);
        }

        return redirect()->route('procurement.requests.show', $purchaseRequest);
    }

    /**
     * Display purchase request details and budget analysis.
     */
    public function show(PurchaseRequest $purchaseRequest): View
    {
        $this->authorizePermission('view_purchase_requests');

        $purchaseRequest->load([
            'items.expenseAccount',
            'items.budgetItem',
            'requester',
            'creator',
            'approver',
            'rejecter',
            'event',
            'budget',
        ]);

        $budgetAnalysis = $this->requestService->checkBudgetUtilization($purchaseRequest);

        return view('procurement.requests.show', compact('purchaseRequest', 'budgetAnalysis'));
    }

    /**
     * Show edit form for draft/rejected request.
     */
    public function edit(PurchaseRequest $purchaseRequest): View
    {
        $this->authorizePermission('manage_purchase_requests');

        if (! $purchaseRequest->canBeEdited()) {
            abort(403, __('procurement.request_cannot_be_edited'));
        }

        $purchaseRequest->load(['items.expenseAccount', 'event', 'budget']);

        $expenseAccounts = FinancialAccount::where('account_type', 'expense')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $approvedBudgets = Budget::where('status', Budget::STATUS_APPROVED)
            ->orderBy('fiscal_year', 'desc')
            ->get();

        $events = Event::orderBy('start_date', 'desc')->get();
        $users = User::where('status', 'active')->orderBy('name')->get();

        return view('procurement.requests.edit', compact('purchaseRequest', 'expenseAccounts', 'approvedBudgets', 'events', 'users'));
    }

    /**
     * Update existing request.
     */
    public function update(UpdatePurchaseRequest $request, PurchaseRequest $purchaseRequest): RedirectResponse|JsonResponse
    {
        $updated = $this->requestService->updateRequest($purchaseRequest, $request->validated(), $request->user());

        session()->flash('success', __('procurement.request_updated_successfully', ['code' => $updated->request_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.request_updated_successfully', ['code' => $updated->request_number]),
                'purchase_request' => $updated,
            ]);
        }

        return redirect()->route('procurement.requests.show', $updated);
    }

    /**
     * Submit request for authorization.
     */
    public function submit(PurchaseRequest $purchaseRequest, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('manage_purchase_requests');

        $submitted = $this->requestService->submitRequest($purchaseRequest, $request->user());

        session()->flash('success', __('procurement.request_submitted_successfully', ['code' => $submitted->request_number]));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.request_submitted_successfully', ['code' => $submitted->request_number]),
            ]);
        }

        return back();
    }

    /**
     * Approve purchase request.
     */
    public function approve(PurchaseRequest $purchaseRequest, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('approve_purchase_requests');

        $approved = $this->requestService->approveRequest($purchaseRequest, $request->user());

        session()->flash('success', __('procurement.request_approved_successfully', ['code' => $approved->request_number]));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.request_approved_successfully', ['code' => $approved->request_number]),
            ]);
        }

        return back();
    }

    /**
     * Reject purchase request with mandatory reason.
     */
    public function reject(PurchaseRequest $purchaseRequest, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('approve_purchase_requests');

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $rejected = $this->requestService->rejectRequest($purchaseRequest, (string) $request->input('rejection_reason'), $request->user());

        session()->flash('warning', __('procurement.request_rejected_successfully', ['code' => $rejected->request_number]));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.request_rejected_successfully', ['code' => $rejected->request_number]),
            ]);
        }

        return back();
    }

    /**
     * Cancel purchase request.
     */
    public function cancel(PurchaseRequest $purchaseRequest, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('manage_purchase_requests');

        $cancelled = $this->requestService->cancelRequest($purchaseRequest, $request->input('reason'), $request->user());

        session()->flash('info', __('procurement.request_cancelled_successfully', ['code' => $cancelled->request_number]));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.request_cancelled_successfully', ['code' => $cancelled->request_number]),
            ]);
        }

        return back();
    }

    /**
     * Server-side DataTables response.
     * Strictly NO row number / # column.
     */
    protected function getDataTableResponse(Request $request): JsonResponse
    {
        $query = PurchaseRequest::with(['requester', 'event', 'items']);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('event_id') && $request->input('event_id') !== 'all') {
            $query->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('requester', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $recordsTotal = PurchaseRequest::count();
        $recordsFiltered = (clone $query)->count();

        $columns = [
            0 => 'title',
            1 => 'request_date',
            2 => 'requested_by',
            3 => 'event_id',
            4 => 'total_estimated_amount',
            5 => 'status',
        ];

        $orderIndex = $request->input('order.0.column');
        if ($orderIndex !== null && isset($columns[(int) $orderIndex])) {
            $orderColumn = $columns[(int) $orderIndex];
            $orderDirection = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        } else {
            $orderColumn = 'id';
            $orderDirection = 'desc';
        }

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 15);
        if ($length <= 0) {
            $length = 15;
        }

        $requests = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $canManage = auth()->user()->hasPermission('manage_purchase_requests');

        $data = [];
        foreach ($requests as $r) {
            $codeHtml = '<a href="'.route('procurement.requests.show', $r).'" class="fw-bold font-monospace text-primary text-decoration-none">'.e($r->request_number).'</a>';

            $titleHtml = '<a href="'.route('procurement.requests.show', $r).'" class="text-decoration-none text-dark fw-semibold">'.e($r->title).'</a><small class="text-muted d-block">'.$r->items->count().' '.__('procurement.items').'</small><span class="d-none">'.e($r->request_number).'</span>';

            $dateHtml = '<span class="text-nowrap">'.$r->request_date?->format('d M Y').'</span>';

            $requesterHtml = e($r->requester?->name ?? '-');

            $scopeHtml = $r->event
                ? '<span class="badge bg-light text-dark border"><i class="bi bi-calendar-event me-1 text-primary"></i>'.e($r->event->title_en).'</span>'
                : '<span class="badge bg-light text-secondary border">'.__('procurement.type_general_fund').'</span>';

            $amountHtml = '৳'.number_format((float) $r->total_estimated_amount, 2);

            $actions = '<div class="dropdown text-end">';
            $actions .= '<button class="btn btn-light btn-sm rounded-circle p-0 d-inline-flex align-items-center justify-content-center border shadow-sm" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-popper-config=\'{"strategy":"fixed"}\' aria-expanded="false" style="width: 32px; height: 32px;" title="'.__('common.actions').'">';
            $actions .= '<i class="bi bi-three-dots-vertical text-secondary"></i>';
            $actions .= '</button>';
            $actions .= '<ul class="dropdown-menu dropdown-menu-end shadow-sm border py-1" style="min-width: 170px; font-size: 0.875rem;">';
            $actions .= '<li><a class="dropdown-item py-1 px-3 d-flex align-items-center" href="'.route('procurement.requests.show', $r).'"><i class="bi bi-eye text-primary me-2"></i>'.__('common.view').'</a></li>';

            if ($canManage && $r->canBeEdited()) {
                $actions .= '<li><a class="dropdown-item py-1 px-3 d-flex align-items-center" href="'.route('procurement.requests.edit', $r).'"><i class="bi bi-pencil text-secondary me-2"></i>'.__('common.edit').'</a></li>';
            }

            if ($r->canBeConvertedToPo()) {
                $actions .= '<li><a class="dropdown-item py-1 px-3 d-flex align-items-center text-success" href="'.route('procurement.requests.show', $r).'#convert"><i class="bi bi-box-arrow-right text-success me-2"></i>'.__('procurement.convert_to_order').'</a></li>';
            }

            $actions .= '</ul>';
            $actions .= '</div>';

            $data[] = [
                'request_number' => $codeHtml,
                'title' => $titleHtml,
                'request_date' => $dateHtml,
                'requester' => $requesterHtml,
                'scope' => $scopeHtml,
                'total_amount' => $amountHtml,
                'status' => $r->status_badge,
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
