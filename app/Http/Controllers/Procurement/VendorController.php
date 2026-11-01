<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\StoreVendorRequest;
use App\Http\Requests\Procurement\UpdateVendorRequest;
use App\Models\Expense;
use App\Models\Vendor;
use App\Services\VendorService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(
        protected VendorService $vendorService
    ) {}

    /**
     * Display vendor directory listing or DataTables JSON.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizePermission('view_vendors');

        if ($request->ajax() || $request->wantsJson()) {
            return $this->getDataTableResponse($request);
        }

        $totalVendors = Vendor::count();
        $activeVendors = Vendor::where('is_active', true)->count();
        $inactiveVendors = Vendor::where('is_active', false)->count();

        // Calculate total expenditure across all linked expenses
        $totalExpenditure = Expense::whereNotNull('vendor_id')
            ->where('status', Expense::STATUS_APPROVED)
            ->sum('amount');

        $summary = [
            'total_vendors' => $totalVendors,
            'active_vendors' => $activeVendors,
            'inactive_vendors' => $inactiveVendors,
            'total_expenditure' => '৳'.number_format((float) $totalExpenditure, 2),
        ];

        $vendorTypes = Vendor::vendorTypes();

        return view('procurement.vendors.index', compact('summary', 'vendorTypes'));
    }

    /**
     * Select2 AJAX search endpoint.
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorizePermission('view_vendors');

        $term = (string) $request->input('q', '');
        $type = $request->input('vendor_type');
        $onlyActive = $request->boolean('active_only', true);

        $results = $this->vendorService->searchVendors($term, $type, $onlyActive);

        return response()->json($results);
    }

    /**
     * Show creation form.
     */
    public function create(): View
    {
        $this->authorizePermission('manage_vendors');

        $vendorTypes = Vendor::vendorTypes();

        return view('procurement.vendors.create', compact('vendorTypes'));
    }

    /**
     * Store newly created vendor.
     */
    public function store(StoreVendorRequest $request): RedirectResponse|JsonResponse
    {
        $vendor = $this->vendorService->createVendor($request->validated(), $request->user());

        session()->flash('success', __('procurement.vendor_created_successfully', ['code' => $vendor->vendor_code]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.vendor_created_successfully', ['code' => $vendor->vendor_code]),
                'vendor' => $vendor,
            ]);
        }

        return redirect()->route('procurement.vendors.show', $vendor);
    }

    /**
     * Display vendor profile and historical expenses.
     */
    public function show(Vendor $vendor): View
    {
        $this->authorizePermission('view_vendors');

        $vendor->load(['creator', 'updater']);

        $expensesQuery = $vendor->allExpenses()->with(['expenseAccount', 'paymentAccount', 'event']);

        $totalExpensesCount = (clone $expensesQuery)->count();
        $totalApprovedAmount = (clone $expensesQuery)->where('status', Expense::STATUS_APPROVED)->sum('amount');
        $totalPendingAmount = (clone $expensesQuery)->where('status', Expense::STATUS_PENDING)->sum('amount');

        $expenses = $expensesQuery->latest('expense_date')->paginate(15);

        $metrics = [
            'total_count' => $totalExpensesCount,
            'approved_amount' => '৳'.number_format((float) $totalApprovedAmount, 2),
            'pending_amount' => '৳'.number_format((float) $totalPendingAmount, 2),
        ];

        return view('procurement.vendors.show', compact('vendor', 'expenses', 'metrics'));
    }

    /**
     * Show edit form.
     */
    public function edit(Vendor $vendor): View
    {
        $this->authorizePermission('manage_vendors');

        $vendorTypes = Vendor::vendorTypes();

        return view('procurement.vendors.edit', compact('vendor', 'vendorTypes'));
    }

    /**
     * Update vendor profile.
     */
    public function update(UpdateVendorRequest $request, Vendor $vendor): RedirectResponse|JsonResponse
    {
        $updated = $this->vendorService->updateVendor($vendor, $request->validated(), $request->user());

        session()->flash('success', __('procurement.vendor_updated_successfully', ['code' => $updated->vendor_code]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('procurement.vendor_updated_successfully', ['code' => $updated->vendor_code]),
                'vendor' => $updated,
            ]);
        }

        return redirect()->route('procurement.vendors.show', $updated);
    }

    /**
     * Toggle vendor active status.
     */
    public function toggleStatus(Vendor $vendor, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('manage_vendors');

        $updated = $this->vendorService->toggleActiveStatus($vendor, $request->user());

        $message = $updated->is_active
            ? __('procurement.vendor_activated_successfully', ['code' => $updated->vendor_code])
            : __('procurement.vendor_deactivated_successfully', ['code' => $updated->vendor_code]);

        session()->flash('success', $message);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $updated->is_active,
                'message' => $message,
            ]);
        }

        return back();
    }

    /**
     * Soft delete vendor if eligible.
     */
    public function destroy(Vendor $vendor, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('manage_vendors');

        try {
            $this->vendorService->deleteVendor($vendor, $request->user());
            $message = __('procurement.vendor_deleted_successfully', ['code' => $vendor->vendor_code]);
            session()->flash('success', $message);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->route('procurement.vendors.index');
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back();
        }
    }

    /**
     * Server-side DataTables response.
     * Strictly NO serial number / row number column.
     */
    protected function getDataTableResponse(Request $request): JsonResponse
    {
        $query = Vendor::withCount('expenses');

        if ($request->filled('vendor_type')) {
            $query->where('vendor_type', $request->input('vendor_type'));
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('vendor_code', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        $recordsTotal = Vendor::count();
        $recordsFiltered = (clone $query)->count();

        $columns = [
            0 => 'vendor_code',
            1 => 'name',
            2 => 'phone',
            3 => 'vendor_type',
            4 => 'expenses_count',
            5 => 'is_active',
        ];

        $orderIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderColumn = $columns[$orderIndex] ?? 'created_at';

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length <= 0) {
            $length = 10;
        }

        $vendors = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $canManage = auth()->user()->hasPermission('manage_vendors');

        $data = [];
        foreach ($vendors as $v) {
            $codeHtml = '<a href="'.route('procurement.vendors.show', $v).'" class="fw-bold font-monospace text-primary text-decoration-none">'.e($v->vendor_code).'</a>';

            $nameHtml = '<div class="fw-semibold">'.e($v->name).'</div>';
            if ($v->contact_person) {
                $nameHtml .= '<small class="text-muted"><i class="bi bi-person me-1"></i>'.e($v->contact_person).'</small>';
            }

            $contactHtml = '';
            if ($v->phone) {
                $contactHtml .= '<span class="font-monospace text-nowrap"><i class="bi bi-telephone me-1 text-muted"></i>'.e($v->phone).'</span>';
            }
            if ($v->email) {
                $contactHtml .= ($contactHtml ? '<br>' : '').'<small class="text-muted"><i class="bi bi-envelope me-1"></i>'.e($v->email).'</small>';
            }
            if (! $contactHtml) {
                $contactHtml = '<span class="text-muted fst-italic">'.__('common.not_applicable').'</span>';
            }

            $typeHtml = '<span class="badge bg-light text-dark border">'.e($v->vendor_type_label).'</span>';

            $hasTransactions = $v->hasProtectedTransactions();
            $txnHtml = '<span class="badge bg-light text-dark border">'.$v->expenses_count.' '.__('procurement.expenses').'</span>';

            $statusBadge = $v->status_badge;

            $actions = '<div class="btn-group btn-group-sm" role="group">';
            $actions .= '<a href="'.route('procurement.vendors.show', $v).'" class="btn btn-outline-info" title="'.__('common.view').'"><i class="bi bi-eye"></i></a>';

            if ($canManage) {
                $actions .= '<a href="'.route('procurement.vendors.edit', $v).'" class="btn btn-outline-secondary" title="'.__('common.edit').'"><i class="bi bi-pencil"></i></a>';

                $toggleIcon = $v->is_active ? 'bi-toggle-on text-success' : 'bi-toggle-off text-muted';
                $toggleTitle = $v->is_active ? __('procurement.deactivate_vendor') : __('procurement.activate_vendor');
                $actions .= '<button type="button" class="btn btn-outline-secondary btn-toggle-status" data-vendor-id="'.$v->id.'" data-url="'.route('procurement.vendors.toggle-status', $v).'" title="'.$toggleTitle.'"><i class="bi '.$toggleIcon.'"></i></button>';

                if (! $hasTransactions) {
                    $actions .= '<button type="button" class="btn btn-outline-danger btn-delete-vendor" data-vendor-id="'.$v->id.'" data-vendor-name="'.e($v->name).'" data-url="'.route('procurement.vendors.destroy', $v).'" title="'.__('common.delete').'"><i class="bi bi-trash"></i></button>';
                }
            }

            $actions .= '</div>';

            $data[] = [
                'vendor_code' => $codeHtml,
                'name' => $nameHtml,
                'contact' => $contactHtml,
                'vendor_type' => $typeHtml,
                'transactions' => $txnHtml,
                'status' => $statusBadge,
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
