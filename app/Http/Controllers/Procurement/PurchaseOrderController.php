<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StorePurchaseOrderRequest;
use App\Http\Requests\Procurement\UpdatePurchaseOrderRequest;
use App\Models\Budget;
use App\Models\Event;
use App\Models\FinancialAccount;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Vendor;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $orderService
    ) {}

    /**
     * Display a listing of purchase orders.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizePermission('view_purchase_orders');

        if ($request->ajax() || $request->wantsJson()) {
            return $this->getDataTableResponse($request);
        }

        $summary = [
            'total_orders' => PurchaseOrder::count(),
            'approved_orders' => PurchaseOrder::whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SENT])->count(),
            'completed_orders' => PurchaseOrder::where('status', PurchaseOrder::STATUS_COMPLETED)->count(),
            'total_order_value' => '৳'.number_format((float) PurchaseOrder::whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_COMPLETED])->sum('total_amount'), 2),
        ];

        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $events = Event::orderBy('start_date', 'desc')->get();

        return view('procurement.orders.index', compact('summary', 'vendors', 'events'));
    }

    /**
     * Show creation / conversion form.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $this->authorizePermission('manage_purchase_orders');

        $sourceRequest = null;
        if ($request->filled('request_id')) {
            $sourceRequest = PurchaseRequest::with(['items.expenseAccount', 'event', 'budget'])->find((int) $request->input('request_id'));

            if (! $sourceRequest || ! $sourceRequest->canBeConvertedToPo()) {
                session()->flash('error', __('procurement.request_already_converted_or_not_eligible'));

                return redirect()->route('procurement.requests.index');
            }
        }

        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $expenseAccounts = FinancialAccount::where('account_type', 'expense')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();
        $approvedBudgets = Budget::where('status', Budget::STATUS_APPROVED)
            ->orderBy('fiscal_year', 'desc')
            ->get();
        $events = Event::orderBy('start_date', 'desc')->get();

        return view('procurement.orders.create', compact('sourceRequest', 'vendors', 'expenseAccounts', 'approvedBudgets', 'events'));
    }

    /**
     * Store newly created purchase order (or convert from request).
     */
    public function store(StorePurchaseOrderRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        if (! empty($validated['purchase_request_id'])) {
            $purchaseRequest = PurchaseRequest::findOrFail($validated['purchase_request_id']);
            $order = $this->orderService->convertFromRequest($purchaseRequest, $validated, $request->user());
        } else {
            $order = $this->orderService->createOrder($validated, $request->user());
        }

        session()->flash('success', __('procurement.order_created_successfully', ['code' => $order->order_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.order_created_successfully', ['code' => $order->order_number]),
                'order' => $order,
            ]);
        }

        return redirect()->route('procurement.orders.show', $order);
    }

    /**
     * Display purchase order details.
     */
    public function show(PurchaseOrder $purchaseOrder): View
    {
        $this->authorizePermission('view_purchase_orders');

        $purchaseOrder->load([
            'items.expenseAccount',
            'items.budgetItem',
            'vendor',
            'purchaseRequest',
            'event',
            'budget',
            'creator',
            'approver',
        ]);

        return view('procurement.orders.show', compact('purchaseOrder'));
    }

    /**
     * Show edit form for draft purchase order.
     */
    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $this->authorizePermission('manage_purchase_orders');

        if (! $purchaseOrder->canBeEdited()) {
            abort(403, __('procurement.order_cannot_be_edited'));
        }

        $purchaseOrder->load(['items.expenseAccount', 'vendor', 'event', 'budget']);

        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $expenseAccounts = FinancialAccount::where('account_type', 'expense')
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();
        $approvedBudgets = Budget::where('status', Budget::STATUS_APPROVED)
            ->orderBy('fiscal_year', 'desc')
            ->get();
        $events = Event::orderBy('start_date', 'desc')->get();

        return view('procurement.orders.edit', compact('purchaseOrder', 'vendors', 'expenseAccounts', 'approvedBudgets', 'events'));
    }

    /**
     * Update existing draft purchase order.
     */
    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse|JsonResponse
    {
        $updated = $this->orderService->updateOrder($purchaseOrder, $request->validated(), $request->user());

        session()->flash('success', __('procurement.order_updated_successfully', ['code' => $updated->order_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.order_updated_successfully', ['code' => $updated->order_number]),
                'order' => $updated,
            ]);
        }

        return redirect()->route('procurement.orders.show', $updated);
    }

    /**
     * Approve purchase order.
     */
    public function approve(PurchaseOrder $purchaseOrder, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('approve_purchase_orders');

        $approved = $this->orderService->approveOrder($purchaseOrder, $request->user());

        session()->flash('success', __('procurement.order_approved_successfully', ['code' => $approved->order_number]));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.order_approved_successfully', ['code' => $approved->order_number]),
            ]);
        }

        return back();
    }

    /**
     * Mark order as sent to vendor.
     */
    public function sendOrder(PurchaseOrder $purchaseOrder, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('manage_purchase_orders');

        $sent = $this->orderService->sendOrder($purchaseOrder, $request->user());

        session()->flash('success', __('procurement.order_sent_successfully', ['code' => $sent->order_number]));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.order_sent_successfully', ['code' => $sent->order_number]),
            ]);
        }

        return back();
    }

    /**
     * Mark order as completed.
     */
    public function complete(PurchaseOrder $purchaseOrder, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('manage_purchase_orders');

        $completed = $this->orderService->completeOrder($purchaseOrder, $request->user());

        session()->flash('success', __('procurement.order_completed_successfully', ['code' => $completed->order_number]));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.order_completed_successfully', ['code' => $completed->order_number]),
            ]);
        }

        return back();
    }

    /**
     * Cancel purchase order.
     */
    public function cancel(PurchaseOrder $purchaseOrder, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('manage_purchase_orders');

        $cancelled = $this->orderService->cancelOrder($purchaseOrder, $request->input('reason'), $request->user());

        session()->flash('info', __('procurement.order_cancelled_successfully', ['code' => $cancelled->order_number]));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.order_cancelled_successfully', ['code' => $cancelled->order_number]),
            ]);
        }

        return back();
    }

    /**
     * Printable PO voucher view.
     */
    public function printOrder(PurchaseOrder $purchaseOrder): View
    {
        $this->authorizePermission('view_purchase_orders');

        $purchaseOrder->load([
            'items.expenseAccount',
            'vendor',
            'purchaseRequest',
            'event',
            'budget',
            'creator',
            'approver',
        ]);

        return view('procurement.orders.print', compact('purchaseOrder'));
    }

    /**
     * Server-side DataTables response.
     * Strictly NO row number / # column.
     */
    protected function getDataTableResponse(Request $request): JsonResponse
    {
        $query = PurchaseOrder::with(['vendor', 'event', 'purchaseRequest', 'items']);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('vendor_id') && $request->input('vendor_id') !== 'all') {
            $query->where('vendor_id', $request->input('vendor_id'));
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($vq) use ($search) {
                        $vq->where('name', 'like', "%{$search}%")
                            ->orWhere('vendor_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseRequest', function ($rq) use ($search) {
                        $rq->where('request_number', 'like', "%{$search}%");
                    });
            });
        }

        $recordsTotal = PurchaseOrder::count();
        $recordsFiltered = (clone $query)->count();

        $columns = [
            0 => 'order_number',
            1 => 'vendor_id',
            2 => 'order_date',
            3 => 'purchase_request_id',
            4 => 'event_id',
            5 => 'total_amount',
            6 => 'status',
        ];

        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderColumn = $columns[$orderIndex] ?? 'created_at';

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 15);
        if ($length <= 0) {
            $length = 15;
        }

        $orders = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $canManage = auth()->user()->hasPermission('manage_purchase_orders');

        $data = [];
        foreach ($orders as $o) {
            $codeHtml = '<a href="'.route('procurement.orders.show', $o).'" class="fw-bold font-monospace text-primary text-decoration-none">'.e($o->order_number).'</a>';

            $vendorHtml = '<a href="'.route('procurement.vendors.show', $o->vendor).'" class="fw-semibold text-dark text-decoration-none">'.e($o->vendor?->name).'</a><br><small class="text-muted font-monospace">'.e($o->vendor?->vendor_code).'</small>';

            $dateHtml = '<span class="text-nowrap">'.$o->order_date?->format('d M Y').'</span>';

            $reqHtml = $o->purchaseRequest
                ? '<a href="'.route('procurement.requests.show', $o->purchaseRequest).'" class="font-monospace text-decoration-none small text-info"><i class="bi bi-link-45deg"></i>'.e($o->purchaseRequest->request_number).'</a>'
                : '<span class="text-muted small">-</span>';

            $scopeHtml = $o->event
                ? '<span class="badge bg-light text-dark border"><i class="bi bi-calendar-event me-1 text-primary"></i>'.e($o->event->title_en).'</span>'
                : '<span class="badge bg-light text-secondary border">'.__('procurement.type_general_fund').'</span>';

            $amountHtml = '৳'.number_format((float) $o->total_amount, 2);

            $actions = '<div class="btn-group btn-group-sm" role="group">';
            $actions .= '<a href="'.route('procurement.orders.show', $o).'" class="btn btn-outline-info" title="'.__('common.view').'"><i class="bi bi-eye"></i></a>';

            if ($canManage && $o->canBeEdited()) {
                $actions .= '<a href="'.route('procurement.orders.edit', $o).'" class="btn btn-outline-secondary" title="'.__('common.edit').'"><i class="bi bi-pencil"></i></a>';
            }

            $actions .= '<a href="'.route('procurement.orders.print', $o).'" target="_blank" class="btn btn-outline-secondary" title="'.__('common.print').'"><i class="bi bi-printer"></i></a>';

            $actions .= '</div>';

            $data[] = [
                'order_number' => $codeHtml,
                'vendor' => $vendorHtml,
                'order_date' => $dateHtml,
                'purchase_request' => $reqHtml,
                'scope' => $scopeHtml,
                'total_amount' => $amountHtml,
                'status' => $o->status_badge,
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
