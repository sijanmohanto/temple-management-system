<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreVendorPaymentRequest;
use App\Http\Requests\Procurement\UpdateVendorPaymentRequest;
use App\Models\FinancialAccount;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Services\VendorPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorPaymentController extends Controller
{
    public function __construct(
        protected VendorPaymentService $paymentService
    ) {}

    /**
     * Display a listing of vendor payments.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizePermission('view_vendor_payments');

        if ($request->ajax() || $request->wantsJson()) {
            return $this->getDataTableResponse($request);
        }

        $summary = [
            'total_payments' => VendorPayment::count(),
            'posted_amount' => '৳'.number_format((float) VendorPayment::where('status', VendorPayment::STATUS_POSTED)->sum('amount'), 2),
            'pending_count' => VendorPayment::whereIn('status', [VendorPayment::STATUS_SUBMITTED, VendorPayment::STATUS_VERIFIED, VendorPayment::STATUS_APPROVED])->count(),
            'draft_count' => VendorPayment::where('status', VendorPayment::STATUS_DRAFT)->count(),
        ];

        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();

        return view('procurement.payments.index', compact('summary', 'vendors'));
    }

    /**
     * Show payment creation form.
     */
    public function create(Request $request): View
    {
        $this->authorizePermission('manage_vendor_payments');

        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();

        $assetAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_ASSET)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $selectedVendorId = $request->query('vendor_id') ? (int) $request->query('vendor_id') : null;
        $selectedBillId = $request->query('bill_id') ? (int) $request->query('bill_id') : null;

        $payableBills = collect();
        if ($selectedVendorId) {
            $vendor = Vendor::find($selectedVendorId);
            if ($vendor) {
                $payableBills = $this->paymentService->getPayableBills($vendor);
            }
        }

        $paymentMethods = VendorPayment::supportedPaymentMethods();

        return view('procurement.payments.create', compact('vendors', 'assetAccounts', 'paymentMethods', 'selectedVendorId', 'selectedBillId', 'payableBills'));
    }

    /**
     * Store newly created vendor payment.
     */
    public function store(StoreVendorPaymentRequest $request): RedirectResponse
    {
        $payment = $this->paymentService->createPayment(
            $request->validated(),
            $request->user(),
            $request->file('document')
        );

        session()->flash('success', __('procurement.payment_created_successfully', ['code' => $payment->payment_number]));

        return redirect()->route('procurement.payments.show', $payment);
    }

    /**
     * Display the specified vendor payment.
     */
    public function show(VendorPayment $vendorPayment): View
    {
        $this->authorizePermission('view_vendor_payments');

        $vendorPayment->loadMissing([
            'vendor',
            'financialAccount',
            'allocations.bill',
            'creator',
            'updater',
            'submitter',
            'verifier',
            'approver',
            'poster',
            'rejecter',
            'canceller',
            'reverser',
            'journalEntry.lines.account',
            'reversalJournalEntry.lines.account',
        ]);

        return view('procurement.payments.show', compact('vendorPayment'));
    }

    /**
     * Show form for editing vendor payment.
     */
    public function edit(VendorPayment $vendorPayment): View
    {
        $this->authorizePermission('manage_vendor_payments');

        if (! $vendorPayment->canBeEdited()) {
            abort(403, __('procurement.payment_cannot_be_edited'));
        }

        $vendorPayment->loadMissing(['vendor', 'financialAccount', 'allocations.bill']);

        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();

        $assetAccounts = FinancialAccount::where('account_type', FinancialAccount::TYPE_ASSET)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $paymentMethods = VendorPayment::supportedPaymentMethods();

        $payableBills = $this->paymentService->getPayableBills($vendorPayment->vendor, $vendorPayment->id);

        return view('procurement.payments.edit', compact('vendorPayment', 'vendors', 'assetAccounts', 'paymentMethods', 'payableBills'));
    }

    /**
     * Update specified vendor payment.
     */
    public function update(UpdateVendorPaymentRequest $request, VendorPayment $vendorPayment): RedirectResponse
    {
        $payment = $this->paymentService->updatePayment(
            $vendorPayment,
            $request->validated(),
            $request->user(),
            $request->file('document')
        );

        session()->flash('success', __('procurement.payment_updated_successfully', ['code' => $payment->payment_number]));

        return redirect()->route('procurement.payments.show', $payment);
    }

    /**
     * Submit draft vendor payment.
     */
    public function submit(VendorPayment $vendorPayment, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('submit_vendor_payments');

        $payment = $this->paymentService->submitPayment($vendorPayment, $request->user());

        session()->flash('success', __('procurement.payment_submitted_successfully', ['code' => $payment->payment_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.payment_submitted_successfully', ['code' => $payment->payment_number]),
            ]);
        }

        return redirect()->route('procurement.payments.show', $payment);
    }

    /**
     * Verify submitted vendor payment.
     */
    public function verify(Request $request, VendorPayment $vendorPayment): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('verify_vendor_payments');

        $notes = $request->input('verification_notes');
        $payment = $this->paymentService->verifyPayment($vendorPayment, $notes, $request->user());

        session()->flash('success', __('procurement.payment_verified_successfully', ['code' => $payment->payment_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.payment_verified_successfully', ['code' => $payment->payment_number]),
            ]);
        }

        return redirect()->route('procurement.payments.show', $payment);
    }

    /**
     * Authorize / Approve verified vendor payment.
     */
    public function approve(VendorPayment $vendorPayment, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('approve_vendor_payments');

        $payment = $this->paymentService->approvePayment($vendorPayment, $request->user());

        session()->flash('success', __('procurement.payment_approved_successfully', ['code' => $payment->payment_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.payment_approved_successfully', ['code' => $payment->payment_number]),
            ]);
        }

        return redirect()->route('procurement.payments.show', $payment);
    }

    /**
     * Post approved vendor payment to GL and settle bills.
     */
    public function post(VendorPayment $vendorPayment, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('post_vendor_payments');

        $payment = $this->paymentService->postPayment($vendorPayment, $request->user());

        session()->flash('success', __('procurement.payment_posted_successfully', ['code' => $payment->payment_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.payment_posted_successfully', ['code' => $payment->payment_number]),
            ]);
        }

        return redirect()->route('procurement.payments.show', $payment);
    }

    /**
     * Approve and Post payment in a single step.
     */
    public function approveAndPost(VendorPayment $vendorPayment, Request $request): RedirectResponse|JsonResponse
    {
        if (! $request->user()->hasRole('super_admin')) {
            if (! $request->user()->hasPermission('approve_vendor_payments') || ! $request->user()->hasPermission('post_vendor_payments')) {
                abort(403, __('procurement.unauthorized_action'));
            }
        }

        $payment = $this->paymentService->approveAndPostPayment($vendorPayment, $request->user());

        session()->flash('success', __('procurement.payment_approved_and_posted_successfully', ['code' => $payment->payment_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.payment_approved_and_posted_successfully', ['code' => $payment->payment_number]),
            ]);
        }

        return redirect()->route('procurement.payments.show', $payment);
    }

    /**
     * Reject vendor payment with mandatory reason.
     */
    public function reject(Request $request, VendorPayment $vendorPayment): RedirectResponse|JsonResponse
    {
        if (! $request->user()->hasRole('super_admin')) {
            if (! $request->user()->hasPermission('approve_vendor_payments') && ! $request->user()->hasPermission('verify_vendor_payments')) {
                abort(403, __('procurement.unauthorized_action'));
            }
        }

        $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ], [
            'rejection_reason.required' => __('procurement.rejection_reason_required'),
        ]);

        $payment = $this->paymentService->rejectPayment($vendorPayment, $request->input('rejection_reason'), $request->user());

        session()->flash('success', __('procurement.payment_rejected_successfully', ['code' => $payment->payment_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.payment_rejected_successfully', ['code' => $payment->payment_number]),
            ]);
        }

        return redirect()->route('procurement.payments.show', $payment);
    }

    /**
     * Cancel vendor payment with mandatory reason.
     */
    public function cancel(Request $request, VendorPayment $vendorPayment): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('cancel_vendor_payments');

        $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ], [
            'cancellation_reason.required' => __('procurement.cancellation_reason_required'),
        ]);

        $payment = $this->paymentService->cancelPayment($vendorPayment, $request->input('cancellation_reason'), $request->user());

        session()->flash('success', __('procurement.payment_cancelled_successfully', ['code' => $payment->payment_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.payment_cancelled_successfully', ['code' => $payment->payment_number]),
            ]);
        }

        return redirect()->route('procurement.payments.show', $payment);
    }

    /**
     * Reverse posted vendor payment in General Ledger.
     */
    public function reverse(Request $request, VendorPayment $vendorPayment): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('reverse_vendor_payments');

        $request->validate([
            'reversal_reason' => ['required', 'string', 'max:1000'],
        ], [
            'reversal_reason.required' => __('procurement.reversal_reason_required'),
        ]);

        $payment = $this->paymentService->reversePayment($vendorPayment, $request->input('reversal_reason'), $request->user());

        session()->flash('success', __('procurement.payment_reversed_successfully', ['code' => $payment->payment_number]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.payment_reversed_successfully', ['code' => $payment->payment_number]),
            ]);
        }

        return redirect()->route('procurement.payments.show', $payment);
    }

    /**
     * Print official payment voucher.
     */
    public function printPayment(VendorPayment $vendorPayment): View
    {
        $this->authorizePermission('view_vendor_payments');

        $vendorPayment->loadMissing([
            'vendor',
            'financialAccount',
            'allocations.bill',
            'creator',
            'verifier',
            'approver',
            'poster',
        ]);

        return view('procurement.payments.print', compact('vendorPayment'));
    }

    /**
     * AJAX endpoint to fetch payable bills for a vendor.
     */
    public function getPayableBills(Vendor $vendor, Request $request): JsonResponse
    {
        $this->authorizePermission('view_vendor_payments');

        $paymentId = $request->query('payment_id') ? (int) $request->query('payment_id') : null;
        $bills = $this->paymentService->getPayableBills($vendor, $paymentId);

        $mapped = $bills->map(function ($bill) {
            return [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'vendor_invoice_number' => $bill->vendor_invoice_number,
                'bill_date' => $bill->bill_date?->format('d M Y'),
                'due_date' => $bill->due_date?->format('d M Y') ?? '-',
                'total_amount' => (float) $bill->total_amount,
                'paid_amount' => (float) $bill->paid_amount,
                'outstanding_amount' => round((float) $bill->total_amount - (float) $bill->paid_amount, 2),
            ];
        });

        return response()->json([
            'success' => true,
            'bills' => $mapped,
        ]);
    }

    /**
     * Server-side DataTables response.
     * Strictly NO row number / # column.
     */
    protected function getDataTableResponse(Request $request): JsonResponse
    {
        $query = VendorPayment::with(['vendor', 'financialAccount']);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('vendor_id') && $request->input('vendor_id') !== 'all') {
            $query->where('vendor_id', $request->input('vendor_id'));
        }

        if ($request->filled('payment_method') && $request->input('payment_method') !== 'all') {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($vq) use ($search) {
                        $vq->where('name', 'like', "%{$search}%")
                            ->orWhere('vendor_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('financialAccount', function ($aq) use ($search) {
                        $aq->where('account_name_en', 'like', "%{$search}%")
                            ->orWhere('account_name_bn', 'like', "%{$search}%")
                            ->orWhere('account_code', 'like', "%{$search}%");
                    });
            });
        }

        $recordsTotal = VendorPayment::count();
        $recordsFiltered = (clone $query)->count();

        $columns = [
            0 => 'payment_number',
            1 => 'vendor_id',
            2 => 'payment_date',
            3 => 'payment_method',
            4 => 'financial_account_id',
            5 => 'amount',
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

        $payments = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $canManage = auth()->user()->hasPermission('manage_vendor_payments');

        $data = [];
        foreach ($payments as $p) {
            $codeHtml = '<a href="'.route('procurement.payments.show', $p).'" class="fw-bold font-monospace text-primary text-decoration-none">'.e($p->payment_number).'</a>';

            $vendorHtml = '<a href="'.route('procurement.vendors.show', $p->vendor).'" class="fw-semibold text-dark text-decoration-none">'.e($p->vendor?->name).'</a>';

            $dateHtml = '<span class="text-nowrap">'.$p->payment_date?->format('d M Y').'</span>';

            $methodName = VendorPayment::supportedPaymentMethods()[$p->payment_method] ?? ucfirst(str_replace('_', ' ', $p->payment_method));
            $methodHtml = '<span class="badge bg-light text-dark border">'.e($methodName).'</span>';
            if ($p->reference_number) {
                $methodHtml .= '<br><span class="font-monospace small text-muted">'.e($p->reference_number).'</span>';
            }

            $accountName = app()->getLocale() === 'bn' ? ($p->financialAccount?->account_name_bn ?: $p->financialAccount?->account_name_en) : $p->financialAccount?->account_name_en;
            $accountHtml = '<span class="text-dark small">'.e($accountName).'</span>';

            $amountHtml = '<span class="fw-bold font-monospace text-success">৳'.number_format((float) $p->amount, 2).'</span>';

            $actions = '<div class="btn-group btn-group-sm" role="group">';
            $actions .= '<a href="'.route('procurement.payments.show', $p).'" class="btn btn-outline-info" title="'.__('common.view').'"><i class="bi bi-eye"></i></a>';

            if ($canManage && $p->canBeEdited()) {
                $actions .= '<a href="'.route('procurement.payments.edit', $p).'" class="btn btn-outline-primary" title="'.__('common.edit').'"><i class="bi bi-pencil"></i></a>';
            }

            $actions .= '<a href="'.route('procurement.payments.print', $p).'" target="_blank" class="btn btn-outline-dark" title="'.__('procurement.print_payment_voucher').'"><i class="bi bi-printer"></i></a>';
            $actions .= '</div>';

            $data[] = [
                'payment_number' => $codeHtml,
                'vendor' => $vendorHtml,
                'payment_date' => $dateHtml,
                'payment_method' => $methodHtml,
                'financial_account' => $accountHtml,
                'amount' => $amountHtml,
                'status' => $p->status_badge,
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
     * Check user permission or throw 403.
     */
    protected function authorizePermission(string $permission): void
    {
        $user = auth()->user();
        if (! $user || (! $user->hasPermission($permission) && ! $user->hasRole('super_admin'))) {
            abort(403, __('procurement.unauthorized_action'));
        }
    }
}
