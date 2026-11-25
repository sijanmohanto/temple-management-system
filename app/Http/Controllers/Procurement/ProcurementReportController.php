<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Models\VendorPayment;
use App\Services\ProcurementReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcurementReportController extends Controller
{
    public function __construct(
        protected ProcurementReportService $reportService
    ) {}

    /**
     * -------------------------------------------------------------------------
     * 1. PURCHASE REQUISITIONS REPORT
     * -------------------------------------------------------------------------
     */

    /**
     * Display purchase requisitions report and handle server-side DataTables AJAX.
     */
    public function purchaseRequests(Request $request): View|JsonResponse
    {
        if (! $request->user()->hasAnyPermission(['view_procurement_reports', 'view_purchase_requisition_reports'])) {
            abort(403, __('common.unauthorized'));
        }

        if ($request->ajax() || $request->wantsJson()) {
            if ($request->boolean('summary_only')) {
                return response()->json($this->reportService->getPurchaseRequestsSummary($request->all()));
            }

            return $this->getPurchaseRequestsDataTable($request);
        }

        $summary = $this->reportService->getPurchaseRequestsSummary($request->all());
        $requesters = User::orderBy('name')->get(['id', 'name', 'email']);
        $approvers = User::orderBy('name')->get(['id', 'name', 'email']);

        return view('procurement.reports.requests', compact('summary', 'requesters', 'approvers'));
    }

    /**
     * Build DataTables server-side payload for purchase requests.
     */
    protected function getPurchaseRequestsDataTable(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length <= 0) {
            $length = 10;
        }

        $totalRecords = PurchaseRequest::count();
        $query = $this->reportService->getPurchaseRequestsQuery($request->all());

        $filteredRecords = (clone $query)->count();
        $summary = $this->reportService->getPurchaseRequestsSummary($request->all());

        // Column ordering mapping (0=title, 1=requested_by, 2=request_date, 3=required_date, 4=total_estimated_amount, 5=status, 6=approved_by, 7=approval_date)
        $orderColIdx = (int) $request->input('order.0.column', 2);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $orderCol = match ($orderColIdx) {
            0 => 'title',
            1 => 'requested_by',
            2 => 'request_date',
            3 => 'required_date',
            4 => 'total_estimated_amount',
            5 => 'status',
            6 => 'approved_by',
            7 => 'approved_at',
            default => 'request_date',
        };

        $records = $query->orderBy($orderCol, $orderDir)
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $records->map(function (PurchaseRequest $pr) {
            $statusBadge = match ($pr->status) {
                PurchaseRequest::STATUS_DRAFT => '<span class="badge bg-secondary-subtle text-secondary border">'.__('procurement.status_draft').'</span>',
                PurchaseRequest::STATUS_SUBMITTED => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">'.__('procurement.status_submitted').'</span>',
                PurchaseRequest::STATUS_APPROVED => '<span class="badge bg-success-subtle text-success border border-success-subtle">'.__('procurement.status_approved').'</span>',
                PurchaseRequest::STATUS_REJECTED => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">'.__('procurement.status_rejected').'</span>',
                PurchaseRequest::STATUS_CANCELLED => '<span class="badge bg-dark-subtle text-dark border">'.__('procurement.status_cancelled').'</span>',
                default => '<span class="badge bg-light text-dark border">'.$pr->status.'</span>',
            };

            $convertedBadge = $pr->is_converted_to_po
                ? ' <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-xs" title="PO Generated"><i class="bi bi-box-arrow-up-right me-1"></i>PO</span>'
                : '';

            $requesterHtml = '<div><span class="fw-semibold text-dark">'.e($pr->requester?->name ?? '—').'</span>'.
                ($pr->requester?->email ? '<br><small class="text-muted font-xs">'.e($pr->requester->email).'</small>' : '').'</div>';

            $approverHtml = $pr->approver
                ? '<div><span class="fw-semibold text-dark">'.e($pr->approver->name).'</span>'.
                  ($pr->approved_at ? '<br><small class="text-muted font-xs">'.e($pr->approved_at->format('d M Y')).'</small>' : '').'</div>'
                : '<span class="text-muted">—</span>';

            $actionHtml = '<button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0 btn-view-request-details" data-id="'.$pr->id.'" title="'.__('procurement.view_requisition_details').'">'.
                '<i class="bi bi-eye me-1"></i>'.__('common.details').'</button>';

            $titleHtml = '<div>'.
                '<span class="font-monospace fw-bold text-primary">'.e($pr->request_number).'</span>'.$convertedBadge.
                '<div class="text-truncate fw-semibold text-dark mt-1" style="max-width: 250px;" title="'.e($pr->title).'">'.e($pr->title).'</div>'.
                ($pr->justification ? '<small class="text-muted text-truncate font-xs d-inline-block" style="max-width: 230px;">'.e($pr->justification).'</small>' : '').
                '</div>';

            return [
                'request_number' => '<span class="font-monospace fw-bold text-primary">'.e($pr->request_number).'</span>'.$convertedBadge,
                'title' => $titleHtml,
                'requested_by' => $requesterHtml,
                'request_date' => '<span class="text-nowrap"><i class="bi bi-calendar3 me-1 text-muted"></i>'.($pr->request_date ? e($pr->request_date->format('d M Y')) : '—').'</span>',
                'required_date' => '<span class="text-nowrap">'.($pr->required_date ? e($pr->required_date->format('d M Y')) : '<span class="text-muted">—</span>').'</span>',
                'total_estimated_amount' => '<span class="fw-bold text-primary text-nowrap">৳'.number_format((float) $pr->total_estimated_amount, 2).'</span>',
                'status' => $statusBadge,
                'approved_by' => $approverHtml,
                'approval_date' => $pr->approved_at ? e($pr->approved_at->format('d M Y')) : '<span class="text-muted">—</span>',
                'action' => $actionHtml,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
            'summary' => $summary,
        ]);
    }

    /**
     * Render purchase request details partial for Bootstrap Offcanvas.
     */
    public function purchaseRequestDetails(Request $request, PurchaseRequest $purchaseRequest): View|JsonResponse
    {
        if (! $request->user()->hasAnyPermission(['view_procurement_reports', 'view_purchase_requisition_reports'])) {
            abort(403, __('common.unauthorized'));
        }

        $purchaseRequest->load([
            'requester',
            'approver',
            'rejecter',
            'event',
            'budget',
            'items.expenseAccount',
            'purchaseOrder.vendor',
        ]);

        if ($request->wantsJson() && ! $request->ajax()) {
            return response()->json($purchaseRequest);
        }

        return view('procurement.reports.partials.request_offcanvas', compact('purchaseRequest'));
    }

    /**
     * Export Purchase Requisitions Report as UTF-8 CSV with BOM for Excel compatibility.
     */
    public function exportPurchaseRequests(Request $request): StreamedResponse
    {
        if (! $request->user()->hasAnyPermission(['export_procurement_reports', 'view_procurement_reports', 'view_purchase_requisition_reports'])) {
            abort(403, __('common.unauthorized'));
        }

        $query = $this->reportService->getPurchaseRequestsQuery($request->all())
            ->orderBy('request_date', 'desc');

        $filename = 'purchase_requisitions_report_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($handle, [
                'Requisition Number',
                'Title',
                'Justification',
                'Requested By',
                'Requester Email',
                'Request Date',
                'Required Date',
                'Estimated Amount (BDT)',
                'Status',
                'Approved By',
                'Approval Date',
                'PO Converted',
                'PO Reference',
            ]);

            $query->chunk(200, function ($records) use ($handle) {
                foreach ($records as $pr) {
                    fputcsv($handle, [
                        $pr->request_number,
                        $pr->title,
                        $pr->justification,
                        $pr->requester?->name ?? '',
                        $pr->requester?->email ?? '',
                        $pr->request_date ? $pr->request_date->format('Y-m-d') : '',
                        $pr->required_date ? $pr->required_date->format('Y-m-d') : '',
                        number_format((float) $pr->total_estimated_amount, 2, '.', ''),
                        ucfirst($pr->status),
                        $pr->approver?->name ?? '',
                        $pr->approved_at ? $pr->approved_at->format('Y-m-d') : '',
                        $pr->is_converted_to_po ? 'Yes' : 'No',
                        $pr->purchaseOrder?->order_number ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Print-friendly Purchase Requisitions Report.
     */
    public function printPurchaseRequests(Request $request): View
    {
        if (! $request->user()->hasAnyPermission(['view_procurement_reports', 'view_purchase_requisition_reports'])) {
            abort(403, __('common.unauthorized'));
        }

        $query = $this->reportService->getPurchaseRequestsQuery($request->all())
            ->orderBy('request_date', 'desc');

        $requests = $query->get();
        $summary = $this->reportService->getPurchaseRequestsSummary($request->all());
        $filters = $request->all();

        return view('procurement.reports.print.requests_print', compact('requests', 'summary', 'filters'));
    }

    /**
     * -------------------------------------------------------------------------
     * 2. PURCHASE ORDERS REPORT
     * -------------------------------------------------------------------------
     */

    /**
     * Display purchase orders report and handle server-side DataTables AJAX.
     */
    public function purchaseOrders(Request $request): View|JsonResponse
    {
        if (! $request->user()->hasAnyPermission(['view_procurement_reports', 'view_purchase_order_reports'])) {
            abort(403, __('common.unauthorized'));
        }

        if ($request->ajax() || $request->wantsJson()) {
            if ($request->boolean('summary_only')) {
                return response()->json($this->reportService->getPurchaseOrdersSummary($request->all()));
            }

            return $this->getPurchaseOrdersDataTable($request);
        }

        $summary = $this->reportService->getPurchaseOrdersSummary($request->all());
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get(['id', 'name', 'vendor_code']);

        return view('procurement.reports.orders', compact('summary', 'vendors'));
    }

    /**
     * Build DataTables server-side payload for purchase orders.
     */
    protected function getPurchaseOrdersDataTable(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length <= 0) {
            $length = 10;
        }

        $totalRecords = PurchaseOrder::count();
        $query = $this->reportService->getPurchaseOrdersQuery($request->all());

        $filteredRecords = (clone $query)->count();
        $summary = $this->reportService->getPurchaseOrdersSummary($request->all());

        // Column ordering mapping
        $orderColIdx = (int) $request->input('order.0.column', 3);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $orderCol = match ($orderColIdx) {
            0 => 'order_number',
            1 => 'vendor_id',
            2 => 'purchase_request_id',
            3 => 'order_date',
            4 => 'expected_delivery_date',
            5 => 'total_amount',
            6 => 'status',
            default => 'order_date',
        };

        $records = $query->orderBy($orderCol, $orderDir)
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $records->map(function (PurchaseOrder $po) {
            $statusBadge = match ($po->status) {
                PurchaseOrder::STATUS_DRAFT => '<span class="badge bg-secondary-subtle text-secondary border">'.__('procurement.status_draft').'</span>',
                PurchaseOrder::STATUS_APPROVED => '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">'.__('procurement.status_approved').'</span>',
                PurchaseOrder::STATUS_SENT => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">'.__('procurement.status_sent').'</span>',
                PurchaseOrder::STATUS_COMPLETED => '<span class="badge bg-success-subtle text-success border border-success-subtle">'.__('procurement.status_completed').'</span>',
                PurchaseOrder::STATUS_CANCELLED => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">'.__('procurement.status_cancelled').'</span>',
                default => '<span class="badge bg-light text-dark border">'.$po->status.'</span>',
            };

            $vendorHtml = '<div><span class="fw-semibold text-dark">'.e($po->vendor?->name ?? '—').'</span>'.
                ($po->vendor?->vendor_code ? '<br><small class="text-muted font-monospace font-xs">'.e($po->vendor->vendor_code).'</small>' : '').'</div>';

            $requisitionHtml = $po->purchaseRequest
                ? '<span class="font-monospace text-secondary">'.e($po->purchaseRequest->request_number).'</span>'
                : '<span class="text-muted">—</span>';

            $actionHtml = '<button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0 btn-view-order-details" data-id="'.$po->id.'" title="'.__('procurement.view_order_details').'">'.
                '<i class="bi bi-eye me-1"></i>'.__('common.details').'</button>';

            return [
                'order_number' => '<span class="font-monospace fw-bold text-primary">'.e($po->order_number).'</span>',
                'vendor' => $vendorHtml,
                'requisition' => $requisitionHtml,
                'order_date' => '<span class="text-nowrap"><i class="bi bi-calendar3 me-1 text-muted"></i>'.($po->order_date ? e($po->order_date->format('d M Y')) : '—').'</span>',
                'expected_delivery_date' => '<span class="text-nowrap">'.($po->expected_delivery_date ? e($po->expected_delivery_date->format('d M Y')) : '<span class="text-muted">—</span>').'</span>',
                'total_amount' => '<span class="fw-bold text-primary text-nowrap">৳'.number_format((float) $po->total_amount, 2).'</span>',
                'status' => $statusBadge,
                'action' => $actionHtml,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
            'summary' => $summary,
        ]);
    }

    /**
     * Render purchase order details partial for Bootstrap Offcanvas.
     */
    public function purchaseOrderDetails(Request $request, PurchaseOrder $purchaseOrder): View|JsonResponse
    {
        if (! $request->user()->hasAnyPermission(['view_procurement_reports', 'view_purchase_order_reports'])) {
            abort(403, __('common.unauthorized'));
        }

        $purchaseOrder->load([
            'vendor',
            'purchaseRequest.requester',
            'event',
            'budget',
            'approver',
            'items.expenseAccount',
            'bills',
        ]);

        if ($request->wantsJson() && ! $request->ajax()) {
            return response()->json($purchaseOrder);
        }

        return view('procurement.reports.partials.order_offcanvas', compact('purchaseOrder'));
    }

    /**
     * Export Purchase Orders Report as UTF-8 CSV with BOM for Excel compatibility.
     */
    public function exportPurchaseOrders(Request $request): StreamedResponse
    {
        if (! $request->user()->hasAnyPermission(['export_procurement_reports', 'view_procurement_reports', 'view_purchase_order_reports'])) {
            abort(403, __('common.unauthorized'));
        }

        $query = $this->reportService->getPurchaseOrdersQuery($request->all())
            ->orderBy('order_date', 'desc');

        $filename = 'purchase_orders_report_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($handle, [
                'PO Number',
                'Vendor Name',
                'Vendor Code',
                'Related Requisition',
                'Order Date',
                'Expected Delivery Date',
                'Total Amount (BDT)',
                'Status',
                'Approved By',
                'Delivery Address',
            ]);

            $query->chunk(200, function ($records) use ($handle) {
                foreach ($records as $po) {
                    fputcsv($handle, [
                        $po->order_number,
                        $po->vendor?->name ?? '',
                        $po->vendor?->vendor_code ?? '',
                        $po->purchaseRequest?->request_number ?? '',
                        $po->order_date ? $po->order_date->format('Y-m-d') : '',
                        $po->expected_delivery_date ? $po->expected_delivery_date->format('Y-m-d') : '',
                        number_format((float) $po->total_amount, 2, '.', ''),
                        ucfirst($po->status),
                        $po->approver?->name ?? '',
                        $po->delivery_address ?? '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Print-friendly Purchase Orders Report.
     */
    public function printPurchaseOrders(Request $request): View
    {
        if (! $request->user()->hasAnyPermission(['view_procurement_reports', 'view_purchase_order_reports'])) {
            abort(403, __('common.unauthorized'));
        }

        $query = $this->reportService->getPurchaseOrdersQuery($request->all())
            ->orderBy('order_date', 'desc');

        $orders = $query->get();
        $summary = $this->reportService->getPurchaseOrdersSummary($request->all());
        $filters = $request->all();

        return view('procurement.reports.print.orders_print', compact('orders', 'summary', 'filters'));
    }

    /**
     * -------------------------------------------------------------------------
     * 3. OUTSTANDING VENDOR BILLS REPORT
     * -------------------------------------------------------------------------
     */

    /**
     * Display outstanding vendor bills report and handle server-side DataTables AJAX.
     */
    public function outstandingBills(Request $request): View|JsonResponse
    {
        if (! $request->user()->hasAnyPermission(['view_procurement_reports', 'view_outstanding_vendor_bill_reports'])) {
            abort(403, __('common.unauthorized'));
        }

        if ($request->ajax() || $request->wantsJson()) {
            if ($request->boolean('summary_only')) {
                return response()->json($this->reportService->getOutstandingBillsSummary($request->all()));
            }

            return $this->getOutstandingBillsDataTable($request);
        }

        $summary = $this->reportService->getOutstandingBillsSummary($request->all());
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get(['id', 'name', 'vendor_code']);

        return view('procurement.reports.outstanding_bills', compact('summary', 'vendors'));
    }

    /**
     * Build DataTables server-side payload for outstanding vendor bills.
     * Uses authoritative net posted payment allocations.
     */
    protected function getOutstandingBillsDataTable(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length <= 0) {
            $length = 10;
        }

        $totalRecords = VendorBill::where('status', VendorBill::STATUS_APPROVED)->count();
        $query = $this->reportService->getOutstandingBillsQuery($request->all());

        $filteredRecords = (clone $query)->count();
        $summary = $this->reportService->getOutstandingBillsSummary($request->all());

        // Column ordering mapping
        $orderColIdx = (int) $request->input('order.0.column', 3);
        $orderDir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $orderCol = match ($orderColIdx) {
            0 => 'bill_number',
            1 => 'vendor_id',
            2 => 'purchase_order_id',
            3 => 'bill_date',
            4 => 'due_date',
            5 => 'total_amount',
            6 => 'authoritative_posted_paid',
            default => 'bill_date',
        };

        $records = $query->orderBy($orderCol, $orderDir)
            ->offset($start)
            ->limit($length)
            ->get();

        $today = now()->toDateString();

        $data = $records->map(function (VendorBill $bill) use ($today) {
            $billTotal = (float) $bill->total_amount;
            $postedPaid = (float) ($bill->authoritative_posted_paid ?? 0.00);
            $outstanding = max(0.00, round($billTotal - $postedPaid, 2));
            $isOverdue = ($outstanding > 0.00 && $bill->due_date && $bill->due_date->toDateString() < $today);

            // Compute status badge
            if ($outstanding <= 0.00) {
                $statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle">'.__('procurement.payment_paid').'</span>';
            } elseif ($isOverdue) {
                $statusBadge = '<span class="badge bg-danger text-white">'.__('procurement.overdue').'</span>';
            } elseif ($postedPaid > 0.00) {
                $statusBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">'.__('procurement.payment_partial').'</span>';
            } else {
                $statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">'.__('procurement.payment_unpaid').'</span>';
            }

            $vendorHtml = '<div><span class="fw-semibold text-dark">'.e($bill->vendor?->name ?? '—').'</span>'.
                ($bill->vendor?->vendor_code ? '<br><small class="text-muted font-monospace font-xs">'.e($bill->vendor->vendor_code).'</small>' : '').'</div>';

            $poHtml = $bill->purchaseOrder
                ? '<span class="font-monospace text-secondary">'.e($bill->purchaseOrder->order_number).'</span>'
                : '<span class="text-muted">—</span>';

            $dueDateHtml = $bill->due_date
                ? '<span class="text-nowrap '.($isOverdue ? 'text-danger fw-bold' : '').'"><i class="bi bi-clock-history me-1"></i>'.e($bill->due_date->format('d M Y')).($isOverdue ? ' <i class="bi bi-exclamation-triangle-fill text-danger" title="Overdue"></i>' : '').'</span>'
                : '<span class="text-muted">—</span>';

            $actionHtml = '<button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0 btn-view-bill-details" data-id="'.$bill->id.'" title="'.__('procurement.view_bill_details').'">'.
                '<i class="bi bi-eye me-1"></i>'.__('common.details').'</button>';

            return [
                'bill_number' => '<div><span class="font-monospace fw-bold text-primary">'.e($bill->bill_number).'</span>'.
                    ($bill->vendor_invoice_number ? '<br><small class="text-muted font-xs">Inv: '.e($bill->vendor_invoice_number).'</small>' : '').'</div>',
                'vendor' => $vendorHtml,
                'purchase_order' => $poHtml,
                'bill_date' => '<span class="text-nowrap"><i class="bi bi-calendar3 me-1 text-muted"></i>'.($bill->bill_date ? e($bill->bill_date->format('d M Y')) : '—').'</span>',
                'due_date' => $dueDateHtml,
                'bill_amount' => '<span class="fw-bold text-dark text-nowrap">৳'.number_format($billTotal, 2).'</span>',
                'paid_amount' => '<span class="fw-bold text-success text-nowrap">৳'.number_format($postedPaid, 2).'</span>',
                'outstanding_amount' => '<span class="fw-bold '.($outstanding > 0.00 ? 'text-danger' : 'text-muted').' text-nowrap">৳'.number_format($outstanding, 2).'</span>',
                'payment_status' => $statusBadge,
                'action' => $actionHtml,
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
            'summary' => $summary,
        ]);
    }

    /**
     * Render vendor bill details partial for Bootstrap Offcanvas.
     * Includes authoritative payment history, reversed payment notes, and line items.
     */
    public function outstandingBillDetails(Request $request, VendorBill $vendorBill): View|JsonResponse
    {
        if (! $request->user()->hasAnyPermission(['view_procurement_reports', 'view_outstanding_vendor_bill_reports'])) {
            abort(403, __('common.unauthorized'));
        }

        $vendorBill->load([
            'vendor',
            'purchaseOrder',
            'items.expenseAccount',
            'allocations.payment.financialAccount',
            'allocations.payment.creator',
            'allocations.payment.approver',
            'allocations.payment.reverser',
        ]);

        // Compute net posted allocations and current outstanding balance
        $postedAllocationsTotal = $vendorBill->allocations
            ->filter(fn ($alloc) => $alloc->payment && $alloc->payment->status === VendorPayment::STATUS_POSTED && is_null($alloc->payment->deleted_at))
            ->sum('allocated_amount');

        $outstandingBalance = max(0.00, round((float) $vendorBill->total_amount - (float) $postedAllocationsTotal, 2));

        if ($request->wantsJson() && ! $request->ajax()) {
            return response()->json([
                'bill' => $vendorBill,
                'net_posted_paid' => $postedAllocationsTotal,
                'outstanding_balance' => $outstandingBalance,
            ]);
        }

        return view('procurement.reports.partials.bill_offcanvas', compact('vendorBill', 'postedAllocationsTotal', 'outstandingBalance'));
    }

    /**
     * Export Outstanding Vendor Bills Report as UTF-8 CSV with BOM for Excel compatibility.
     */
    public function exportOutstandingBills(Request $request): StreamedResponse
    {
        if (! $request->user()->hasAnyPermission(['export_procurement_reports', 'view_procurement_reports', 'view_outstanding_vendor_bill_reports'])) {
            abort(403, __('common.unauthorized'));
        }

        $query = $this->reportService->getOutstandingBillsQuery($request->all())
            ->orderBy('bill_date', 'desc');

        $filename = 'outstanding_vendor_bills_report_'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header
            fputcsv($handle, [
                'Bill Reference',
                'Vendor Invoice Number',
                'Vendor Name',
                'Vendor Code',
                'Related PO Number',
                'Bill Date',
                'Due Date',
                'Bill Amount (BDT)',
                'Net Posted Paid (BDT)',
                'Outstanding Amount (BDT)',
                'Payment Status',
                'Is Overdue',
            ]);

            $today = now()->toDateString();

            $query->chunk(200, function ($records) use ($handle, $today) {
                foreach ($records as $bill) {
                    $billTotal = (float) $bill->total_amount;
                    $postedPaid = (float) ($bill->authoritative_posted_paid ?? 0.00);
                    $outstanding = max(0.00, round($billTotal - $postedPaid, 2));
                    $isOverdue = ($outstanding > 0.00 && $bill->due_date && $bill->due_date->toDateString() < $today);

                    $status = 'Unpaid';
                    if ($outstanding <= 0.00) {
                        $status = 'Paid';
                    } elseif ($isOverdue) {
                        $status = 'Overdue';
                    } elseif ($postedPaid > 0.00) {
                        $status = 'Partially Paid';
                    }

                    fputcsv($handle, [
                        $bill->bill_number,
                        $bill->vendor_invoice_number ?? '',
                        $bill->vendor?->name ?? '',
                        $bill->vendor?->vendor_code ?? '',
                        $bill->purchaseOrder?->order_number ?? '',
                        $bill->bill_date ? $bill->bill_date->format('Y-m-d') : '',
                        $bill->due_date ? $bill->due_date->format('Y-m-d') : '',
                        number_format($billTotal, 2, '.', ''),
                        number_format($postedPaid, 2, '.', ''),
                        number_format($outstanding, 2, '.', ''),
                        $status,
                        $isOverdue ? 'Yes' : 'No',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Print-friendly Outstanding Vendor Bills Report.
     */
    public function printOutstandingBills(Request $request): View
    {
        if (! $request->user()->hasAnyPermission(['view_procurement_reports', 'view_outstanding_vendor_bill_reports'])) {
            abort(403, __('common.unauthorized'));
        }

        $query = $this->reportService->getOutstandingBillsQuery($request->all())
            ->orderBy('bill_date', 'desc');

        $bills = $query->get();
        $summary = $this->reportService->getOutstandingBillsSummary($request->all());
        $filters = $request->all();

        return view('procurement.reports.print.outstanding_bills_print', compact('bills', 'summary', 'filters'));
    }
}
