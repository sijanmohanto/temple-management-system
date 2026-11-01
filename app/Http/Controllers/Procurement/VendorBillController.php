<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreVendorBillRequest;
use App\Http\Requests\Procurement\UpdateVendorBillRequest;
use App\Models\Budget;
use App\Models\Event;
use App\Models\FinancialAccount;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Services\VendorBillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorBillController extends Controller
{
    public function __construct(
        protected VendorBillService $billService
    ) {}

    /**
     * Display a listing of vendor bills.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizePermission('view_vendor_bills');

        if ($request->ajax() || $request->wantsJson()) {
            return $this->getDataTableResponse($request);
        }

        $summary = [
            'total_bills' => VendorBill::count(),
            'approved_amount' => '৳'.number_format((float) VendorBill::where('status', VendorBill::STATUS_APPROVED)->sum('total_amount'), 2),
            'outstanding_amount' => '৳'.number_format((float) VendorBill::where('status', VendorBill::STATUS_APPROVED)->sum('total_amount'), 2),
            'pending_count' => VendorBill::whereIn('status', [VendorBill::STATUS_SUBMITTED, VendorBill::STATUS_VERIFIED])->count(),
            'overdue_count' => VendorBill::where('status', VendorBill::STATUS_APPROVED)
                ->whereNotNull('due_date')
                ->where('due_date', '<', now()->toDateString())
                ->count(),
        ];

        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $events = Event::orderBy('start_date', 'desc')->get();

        return view('procurement.bills.index', compact('summary', 'vendors', 'events'));
    }

    /**
     * Show bill creation form.
     */
    public function create(Request $request): View
    {
        $this->authorizePermission('manage_vendor_bills');

        $linkedPo = null;
        if ($request->filled('purchase_order_id')) {
            $linkedPo = PurchaseOrder::with(['items.expenseAccount', 'vendor', 'event', 'budget'])
                ->find((int) $request->input('purchase_order_id'));
        }

        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $expenseAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_EXPENSE)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();
        $approvedBudgets = Budget::where('status', Budget::STATUS_APPROVED)
            ->orderBy('fiscal_year', 'desc')
            ->get();
        $events = Event::orderBy('start_date', 'desc')->get();
        $availableOrders = PurchaseOrder::whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_COMPLETED])
            ->orderBy('order_date', 'desc')
            ->get();

        return view('procurement.bills.create', compact('linkedPo', 'vendors', 'expenseAccounts', 'approvedBudgets', 'events', 'availableOrders'));
    }

    /**
     * Store a newly created vendor bill.
     */
    public function store(StoreVendorBillRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $bill = $this->billService->createBill($validated, $request->user(), $request->file('document'));

        session()->flash('success', __('procurement.bill_created_successfully', ['code' => $bill->bill_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.bill_created_successfully', ['code' => $bill->bill_number]),
                'bill' => $bill,
                'redirect' => route('procurement.bills.show', $bill),
            ]);
        }

        return redirect()->route('procurement.bills.show', $bill);
    }

    /**
     * Display the specified vendor bill.
     */
    public function show(VendorBill $vendorBill): View
    {
        $this->authorizePermission('view_vendor_bills');

        $vendorBill->loadMissing([
            'vendor',
            'purchaseOrder.items',
            'event',
            'budget',
            'items.expenseAccount',
            'items.purchaseOrderItem',
            'journalEntry.lines.account',
            'creator',
            'updater',
            'submitter',
            'verifier',
            'approver',
            'rejecter',
            'canceller',
        ]);

        return view('procurement.bills.show', compact('vendorBill'));
    }

    /**
     * Show edit form for draft bill.
     */
    public function edit(VendorBill $vendorBill): View|RedirectResponse
    {
        $this->authorizePermission('manage_vendor_bills');

        if (! $vendorBill->canBeEdited()) {
            session()->flash('error', __('procurement.bill_cannot_be_edited'));

            return redirect()->route('procurement.bills.show', $vendorBill);
        }

        $vendorBill->loadMissing(['items.expenseAccount', 'vendor', 'purchaseOrder']);

        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        $expenseAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_EXPENSE)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();
        $approvedBudgets = Budget::where('status', Budget::STATUS_APPROVED)
            ->orderBy('fiscal_year', 'desc')
            ->get();
        $events = Event::orderBy('start_date', 'desc')->get();
        $availableOrders = PurchaseOrder::whereIn('status', [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_COMPLETED])
            ->where('vendor_id', $vendorBill->vendor_id)
            ->orderBy('order_date', 'desc')
            ->get();

        return view('procurement.bills.edit', compact('vendorBill', 'vendors', 'expenseAccounts', 'approvedBudgets', 'events', 'availableOrders'));
    }

    /**
     * Update an existing draft vendor bill.
     */
    public function update(UpdateVendorBillRequest $request, VendorBill $vendorBill): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $bill = $this->billService->updateBill($vendorBill, $validated, $request->user(), $request->file('document'));

        session()->flash('success', __('procurement.bill_updated_successfully', ['code' => $bill->bill_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.bill_updated_successfully', ['code' => $bill->bill_number]),
                'bill' => $bill,
                'redirect' => route('procurement.bills.show', $bill),
            ]);
        }

        return redirect()->route('procurement.bills.show', $bill);
    }

    /**
     * Submit draft bill for verification.
     */
    public function submit(Request $request, VendorBill $vendorBill): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('manage_vendor_bills');

        $bill = $this->billService->submitBill($vendorBill, $request->user());

        session()->flash('success', __('procurement.bill_submitted_successfully', ['code' => $bill->bill_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.bill_submitted_successfully', ['code' => $bill->bill_number]),
            ]);
        }

        return redirect()->route('procurement.bills.show', $bill);
    }

    /**
     * Verify submitted vendor bill.
     */
    public function verify(Request $request, VendorBill $vendorBill): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('verify_vendor_bills');

        $request->validate([
            'verification_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $bill = $this->billService->verifyBill($vendorBill, $request->input('verification_notes'), $request->user());

        session()->flash('success', __('procurement.bill_verified_successfully', ['code' => $bill->bill_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.bill_verified_successfully', ['code' => $bill->bill_number]),
            ]);
        }

        return redirect()->route('procurement.bills.show', $bill);
    }

    /**
     * Approve verified vendor bill.
     */
    public function approve(Request $request, VendorBill $vendorBill): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('approve_vendor_bills');

        $bill = $this->billService->approveBill($vendorBill, $request->user());

        session()->flash('success', __('procurement.bill_approved_successfully', ['code' => $bill->bill_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.bill_approved_successfully', ['code' => $bill->bill_number]),
            ]);
        }

        return redirect()->route('procurement.bills.show', $bill);
    }

    /**
     * Reject vendor bill with recorded reason.
     */
    public function reject(Request $request, VendorBill $vendorBill): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('approve_vendor_bills');

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ], [
            'rejection_reason.required' => __('procurement.rejection_reason_required'),
        ]);

        $bill = $this->billService->rejectBill($vendorBill, $request->input('rejection_reason'), $request->user());

        session()->flash('success', __('procurement.bill_rejected_successfully', ['code' => $bill->bill_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.bill_rejected_successfully', ['code' => $bill->bill_number]),
            ]);
        }

        return redirect()->route('procurement.bills.show', $bill);
    }

    /**
     * Cancel vendor bill with recorded reason.
     */
    public function cancel(Request $request, VendorBill $vendorBill): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('cancel_vendor_bills');

        $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ], [
            'cancellation_reason.required' => __('procurement.cancellation_reason_required'),
        ]);

        $bill = $this->billService->cancelBill($vendorBill, $request->input('cancellation_reason'), $request->user());

        session()->flash('success', __('procurement.bill_cancelled_successfully', ['code' => $bill->bill_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.bill_cancelled_successfully', ['code' => $bill->bill_number]),
            ]);
        }

        return redirect()->route('procurement.bills.show', $bill);
    }

    /**
     * Print official vendor bill voucher.
     */
    public function printBill(VendorBill $vendorBill): View
    {
        $this->authorizePermission('view_vendor_bills');

        $vendorBill->loadMissing([
            'vendor',
            'purchaseOrder',
            'event',
            'budget',
            'items.expenseAccount',
            'creator',
            'verifier',
            'approver',
        ]);

        return view('procurement.bills.print', compact('vendorBill'));
    }

    /**
     * AJAX endpoint to fetch billable items from a Purchase Order.
     */
    public function getBillablePoItems(PurchaseOrder $purchaseOrder, Request $request): JsonResponse
    {
        $this->authorizePermission('view_vendor_bills');

        $billId = $request->query('bill_id') ? (int) $request->query('bill_id') : null;
        $currentBill = $billId ? VendorBill::find($billId) : null;

        $items = $this->billService->getBillablePoItems($purchaseOrder, $currentBill);

        return response()->json([
            'success' => true,
            'vendor_id' => $purchaseOrder->vendor_id,
            'event_id' => $purchaseOrder->event_id,
            'budget_id' => $purchaseOrder->budget_id,
            'items' => $items,
        ]);
    }

    /**
     * Server-side DataTables response.
     * Strictly NO row number / # column.
     */
    protected function getDataTableResponse(Request $request): JsonResponse
    {
        $query = VendorBill::with(['vendor', 'purchaseOrder', 'event', 'items']);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('vendor_id') && $request->input('vendor_id') !== 'all') {
            $query->where('vendor_id', $request->input('vendor_id'));
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                    ->orWhere('vendor_invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($vq) use ($search) {
                        $vq->where('name', 'like', "%{$search}%")
                            ->orWhere('vendor_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseOrder', function ($pq) use ($search) {
                        $pq->where('order_number', 'like', "%{$search}%");
                    });
            });
        }

        $recordsTotal = VendorBill::count();
        $recordsFiltered = (clone $query)->count();

        $columns = [
            0 => 'bill_number',
            1 => 'vendor_id',
            2 => 'vendor_invoice_number',
            3 => 'bill_date',
            4 => 'due_date',
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

        $bills = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $canManage = auth()->user()->hasPermission('manage_vendor_bills');

        $data = [];
        foreach ($bills as $b) {
            $codeHtml = '<a href="'.route('procurement.bills.show', $b).'" class="fw-bold font-monospace text-primary text-decoration-none">'.e($b->bill_number).'</a>';

            $vendorHtml = '<a href="'.route('procurement.vendors.show', $b->vendor).'" class="fw-semibold text-dark text-decoration-none">'.e($b->vendor?->name).'</a>';

            $invoiceHtml = '<span class="font-monospace fw-semibold text-dark">'.e($b->vendor_invoice_number).'</span>';
            if ($b->purchaseOrder) {
                $invoiceHtml .= '<br><a href="'.route('procurement.orders.show', $b->purchaseOrder).'" class="small text-info text-decoration-none font-monospace"><i class="bi bi-box-seam me-1"></i>'.e($b->purchaseOrder->order_number).'</a>';
            }

            $billDateHtml = '<span class="text-nowrap">'.$b->bill_date?->format('d M Y').'</span>';

            $dueDateHtml = $b->due_date
                ? '<span class="text-nowrap '.($b->due_date < now()->toDateString() && $b->status === VendorBill::STATUS_APPROVED ? 'text-danger fw-bold' : '').'">'.$b->due_date->format('d M Y').'</span>'
                : '<span class="text-muted small">-</span>';

            $amountHtml = '<span class="fw-bold font-monospace">৳'.number_format((float) $b->total_amount, 2).'</span>';

            $actions = '<div class="btn-group btn-group-sm" role="group">';
            $actions .= '<a href="'.route('procurement.bills.show', $b).'" class="btn btn-outline-info" title="'.__('common.view').'"><i class="bi bi-eye"></i></a>';

            if ($canManage && $b->canBeEdited()) {
                $actions .= '<a href="'.route('procurement.bills.edit', $b).'" class="btn btn-outline-primary" title="'.__('common.edit').'"><i class="bi bi-pencil"></i></a>';
            }

            $actions .= '<a href="'.route('procurement.bills.print', $b).'" target="_blank" class="btn btn-outline-dark" title="'.__('procurement.print_bill').'"><i class="bi bi-printer"></i></a>';
            $actions .= '</div>';

            $data[] = [
                'bill_number' => $codeHtml,
                'vendor' => $vendorHtml,
                'invoice_reference' => $invoiceHtml,
                'bill_date' => $billDateHtml,
                'due_date' => $dueDateHtml,
                'total_amount' => $amountHtml,
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
     * Enforce server-side authorization check.
     */
    protected function authorizePermission(string $permission): void
    {
        if (! auth()->check()) {
            abort(401);
        }

        if (auth()->user()->hasRole('super_admin')) {
            return;
        }

        if (! auth()->user()->hasPermission($permission)) {
            abort(403, __('common.unauthorized_access'));
        }
    }
}
