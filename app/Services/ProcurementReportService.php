<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\VendorBill;
use App\Models\VendorPayment;
use App\Models\VendorPaymentAllocation;
use Illuminate\Database\Eloquent\Builder;

class ProcurementReportService
{
    /**
     * -------------------------------------------------------------------------
     * 1. PURCHASE REQUISITION REPORT
     * -------------------------------------------------------------------------
     */

    /**
     * Build the query for purchase requests report.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getPurchaseRequestsQuery(array $filters = []): Builder
    {
        $query = PurchaseRequest::with([
            'requester',
            'approver',
            'rejecter',
            'event',
            'budget',
            'purchaseOrder.vendor',
            'items.expenseAccount',
        ]);

        if (! empty($filters['start_date'])) {
            $query->whereDate('request_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('request_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['requested_by']) && $filters['requested_by'] !== 'all') {
            $query->where('requested_by', (int) $filters['requested_by']);
        }

        if (! empty($filters['approved_by']) && $filters['approved_by'] !== 'all') {
            $query->where('approved_by', (int) $filters['approved_by']);
        }

        if (isset($filters['min_amount']) && is_numeric($filters['min_amount']) && (float) $filters['min_amount'] > 0) {
            $query->where('total_estimated_amount', '>=', (float) $filters['min_amount']);
        }

        if (isset($filters['max_amount']) && is_numeric($filters['max_amount']) && (float) $filters['max_amount'] > 0) {
            $query->where('total_estimated_amount', '<=', (float) $filters['max_amount']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('justification', 'like', "%{$search}%")
                    ->orWhereHas('requester', function (Builder $rq) use ($search) {
                        $rq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('approver', function (Builder $aq) use ($search) {
                        $aq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Compute aggregate metrics for purchase requests report respecting active filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getPurchaseRequestsSummary(array $filters = []): array
    {
        $query = $this->getPurchaseRequestsQuery($filters);

        $requests = (clone $query)->select([
            'id',
            'status',
            'total_estimated_amount',
            'is_converted_to_po',
        ])->get();

        $totalCount = $requests->count();
        $totalAmount = 0.00;
        $draftCount = 0;
        $pendingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $cancelledCount = 0;
        $convertedCount = 0;

        foreach ($requests as $req) {
            $amount = (float) $req->total_estimated_amount;
            $totalAmount += $amount;

            match ($req->status) {
                PurchaseRequest::STATUS_DRAFT => $draftCount++,
                PurchaseRequest::STATUS_SUBMITTED => $pendingCount++,
                PurchaseRequest::STATUS_APPROVED => $approvedCount++,
                PurchaseRequest::STATUS_REJECTED => $rejectedCount++,
                PurchaseRequest::STATUS_CANCELLED => $cancelledCount++,
                default => null,
            };

            if ($req->is_converted_to_po) {
                $convertedCount++;
            }
        }

        return [
            'total_count' => $totalCount,
            'total_amount' => round($totalAmount, 2),
            'total_amount_formatted' => '৳'.number_format(round($totalAmount, 2), 2),
            'draft_count' => $draftCount,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
            'cancelled_count' => $cancelledCount,
            'rejected_or_cancelled_count' => $rejectedCount + $cancelledCount,
            'converted_count' => $convertedCount,
        ];
    }

    /**
     * -------------------------------------------------------------------------
     * 2. PURCHASE ORDER REPORT
     * -------------------------------------------------------------------------
     */

    /**
     * Build the query for purchase orders report.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getPurchaseOrdersQuery(array $filters = []): Builder
    {
        $query = PurchaseOrder::with([
            'vendor',
            'purchaseRequest.requester',
            'event',
            'budget',
            'approver',
            'items.expenseAccount',
            'bills',
        ]);

        if (! empty($filters['start_date'])) {
            $query->whereDate('order_date', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->whereDate('order_date', '<=', $filters['end_date']);
        }

        if (! empty($filters['vendor_id']) && $filters['vendor_id'] !== 'all') {
            $query->where('vendor_id', (int) $filters['vendor_id']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['purchase_request_id']) && $filters['purchase_request_id'] !== 'all') {
            $query->where('purchase_request_id', (int) $filters['purchase_request_id']);
        }

        if (isset($filters['min_amount']) && is_numeric($filters['min_amount']) && (float) $filters['min_amount'] > 0) {
            $query->where('total_amount', '>=', (float) $filters['min_amount']);
        }

        if (isset($filters['max_amount']) && is_numeric($filters['max_amount']) && (float) $filters['max_amount'] > 0) {
            $query->where('total_amount', '<=', (float) $filters['max_amount']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('delivery_address', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function (Builder $vq) use ($search) {
                        $vq->where('name', 'like', "%{$search}%")
                            ->orWhere('vendor_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseRequest', function (Builder $prq) use ($search) {
                        $prq->where('request_number', 'like', "%{$search}%")
                            ->orWhere('title', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Compute aggregate metrics for purchase orders report respecting active filters.
     * Clearly distinguishes historical, active committed, completed, draft, and cancelled.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getPurchaseOrdersSummary(array $filters = []): array
    {
        $query = $this->getPurchaseOrdersQuery($filters);

        $orders = (clone $query)->select([
            'id',
            'status',
            'total_amount',
        ])->get();

        $totalCount = $orders->count();
        $totalHistoricalAmount = 0.00;
        $activeCommittedAmount = 0.00;
        $completedAmount = 0.00;
        $draftAmount = 0.00;
        $cancelledAmount = 0.00;

        $draftCount = 0;
        $approvedCount = 0;
        $sentCount = 0;
        $completedCount = 0;
        $cancelledCount = 0;

        foreach ($orders as $order) {
            $amount = (float) $order->total_amount;
            $totalHistoricalAmount += $amount;

            match ($order->status) {
                PurchaseOrder::STATUS_DRAFT => [
                    $draftCount++,
                    $draftAmount += $amount,
                ],
                PurchaseOrder::STATUS_APPROVED => [
                    $approvedCount++,
                    $activeCommittedAmount += $amount,
                ],
                PurchaseOrder::STATUS_SENT => [
                    $sentCount++,
                    $activeCommittedAmount += $amount,
                ],
                PurchaseOrder::STATUS_COMPLETED => [
                    $completedCount++,
                    $completedAmount += $amount,
                ],
                PurchaseOrder::STATUS_CANCELLED => [
                    $cancelledCount++,
                    $cancelledAmount += $amount,
                ],
                default => null,
            };
        }

        return [
            'total_count' => $totalCount,
            'total_historical_amount' => round($totalHistoricalAmount, 2),
            'total_historical_amount_formatted' => '৳'.number_format(round($totalHistoricalAmount, 2), 2),

            // Active committed procurement (Approved + Sent, excluding draft and cancelled)
            'active_committed_count' => $approvedCount + $sentCount,
            'active_committed_amount' => round($activeCommittedAmount, 2),
            'active_committed_amount_formatted' => '৳'.number_format(round($activeCommittedAmount, 2), 2),

            // Completed orders
            'completed_count' => $completedCount,
            'completed_amount' => round($completedAmount, 2),
            'completed_amount_formatted' => '৳'.number_format(round($completedAmount, 2), 2),

            // Status counts
            'draft_count' => $draftCount,
            'draft_amount' => round($draftAmount, 2),
            'approved_count' => $approvedCount,
            'sent_count' => $sentCount,

            // Cancelled orders (strictly segregated)
            'cancelled_count' => $cancelledCount,
            'cancelled_amount' => round($cancelledAmount, 2),
            'cancelled_amount_formatted' => '৳'.number_format(round($cancelledAmount, 2), 2),
        ];
    }

    /**
     * -------------------------------------------------------------------------
     * 3. OUTSTANDING VENDOR BILLS REPORT
     * -------------------------------------------------------------------------
     */

    /**
     * Build the query for outstanding vendor bills report.
     * Authoritative calculation:
     *   net_posted_paid = sum of allocations from vendor_payments with status = 'posted'
     *   outstanding_amount = total_amount - net_posted_paid
     *
     * @param  array<string, mixed>  $filters
     */
    public function getOutstandingBillsQuery(array $filters = []): Builder
    {
        // Correlated subquery for authoritative posted payments sum
        $postedAllocationsSubquery = VendorPaymentAllocation::query()
            ->join('vendor_payments', 'vendor_payments.id', '=', 'vendor_payment_allocations.vendor_payment_id')
            ->whereColumn('vendor_payment_allocations.vendor_bill_id', 'vendor_bills.id')
            ->where('vendor_payments.status', VendorPayment::STATUS_POSTED)
            ->whereNull('vendor_payments.deleted_at')
            ->selectRaw('COALESCE(SUM(vendor_payment_allocations.allocated_amount), 0)');

        $query = VendorBill::query()
            ->select('vendor_bills.*')
            ->selectSub($postedAllocationsSubquery, 'authoritative_posted_paid')
            ->with([
                'vendor',
                'purchaseOrder',
                'items.expenseAccount',
                'allocations.payment',
            ]);

        // By default, only approved bills constitute active approved payable obligations
        if (! empty($filters['workflow_status']) && $filters['workflow_status'] !== 'all') {
            $query->where('vendor_bills.status', $filters['workflow_status']);
        } else {
            // Default: Only Approved bills (Draft, rejected, and cancelled are not approved payables)
            $query->where('vendor_bills.status', VendorBill::STATUS_APPROVED);
        }

        if (! empty($filters['vendor_id']) && $filters['vendor_id'] !== 'all') {
            $query->where('vendor_bills.vendor_id', (int) $filters['vendor_id']);
        }

        if (! empty($filters['bill_date_from'])) {
            $query->whereDate('vendor_bills.bill_date', '>=', $filters['bill_date_from']);
        }

        if (! empty($filters['bill_date_to'])) {
            $query->whereDate('vendor_bills.bill_date', '<=', $filters['bill_date_to']);
        }

        if (! empty($filters['due_date_from'])) {
            $query->whereDate('vendor_bills.due_date', '>=', $filters['due_date_from']);
        }

        if (! empty($filters['due_date_to'])) {
            $query->whereDate('vendor_bills.due_date', '<=', $filters['due_date_to']);
        }

        $today = now()->toDateString();

        // Payment status filter (unpaid, partially_paid, paid, overdue)
        if (! empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            match ($filters['payment_status']) {
                'unpaid' => $query->whereRaw('(vendor_bills.total_amount - ('.self::getRawPostedAllocationsSql().')) >= vendor_bills.total_amount'),
                'partially_paid' => $query->whereRaw('('.self::getRawPostedAllocationsSql().') > 0.00 AND (vendor_bills.total_amount - ('.self::getRawPostedAllocationsSql().')) > 0.00'),
                'paid' => $query->whereRaw('(vendor_bills.total_amount - ('.self::getRawPostedAllocationsSql().')) <= 0.00'),
                'overdue' => $query->where('vendor_bills.due_date', '<', $today)
                    ->whereRaw('(vendor_bills.total_amount - ('.self::getRawPostedAllocationsSql().')) > 0.00'),
                default => null,
            };
        }

        // Overdue toggle filter
        if (! empty($filters['overdue_only']) && in_array($filters['overdue_only'], ['1', 'yes', 'true', true], true)) {
            $query->where('vendor_bills.due_date', '<', $today)
                ->whereRaw('(vendor_bills.total_amount - ('.self::getRawPostedAllocationsSql().')) > 0.00');
        } elseif (! empty($filters['is_overdue']) && $filters['is_overdue'] !== 'all') {
            if ($filters['is_overdue'] === 'overdue') {
                $query->where('vendor_bills.due_date', '<', $today)
                    ->whereRaw('(vendor_bills.total_amount - ('.self::getRawPostedAllocationsSql().')) > 0.00');
            } elseif ($filters['is_overdue'] === 'not_overdue') {
                $query->where(function (Builder $q) use ($today) {
                    $q->whereNull('vendor_bills.due_date')
                        ->orWhere('vendor_bills.due_date', '>=', $today)
                        ->orWhereRaw('(vendor_bills.total_amount - ('.self::getRawPostedAllocationsSql().')) <= 0.00');
                });
            }
        }

        // Outstanding Amount Range
        if (isset($filters['min_amount']) && is_numeric($filters['min_amount']) && (float) $filters['min_amount'] > 0) {
            $min = (float) $filters['min_amount'];
            $query->whereRaw('(vendor_bills.total_amount - ('.self::getRawPostedAllocationsSql().')) >= ?', [$min]);
        }

        if (isset($filters['max_amount']) && is_numeric($filters['max_amount']) && (float) $filters['max_amount'] > 0) {
            $max = (float) $filters['max_amount'];
            $query->whereRaw('(vendor_bills.total_amount - ('.self::getRawPostedAllocationsSql().')) <= ?', [$max]);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('vendor_bills.bill_number', 'like', "%{$search}%")
                    ->orWhere('vendor_bills.vendor_invoice_number', 'like', "%{$search}%")
                    ->orWhere('vendor_bills.notes', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function (Builder $vq) use ($search) {
                        $vq->where('name', 'like', "%{$search}%")
                            ->orWhere('vendor_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseOrder', function (Builder $poq) use ($search) {
                        $poq->where('order_number', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * Compute aggregate financial metrics for outstanding bills report respecting active filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getOutstandingBillsSummary(array $filters = []): array
    {
        $query = $this->getOutstandingBillsQuery($filters);

        $bills = (clone $query)->get();
        $today = now()->toDateString();

        $totalApprovedAmount = 0.00;
        $totalPaidAmount = 0.00;
        $totalOutstandingAmount = 0.00;

        $unpaidCount = 0;
        $partiallyPaidCount = 0;
        $paidCount = 0;
        $overdueCount = 0;
        $overdueAmount = 0.00;

        foreach ($bills as $bill) {
            $total = (float) $bill->total_amount;
            // Use the authoritative net posted paid subquery result
            $paid = (float) ($bill->authoritative_posted_paid ?? 0.00);
            $outstanding = max(0.00, round($total - $paid, 2));

            $totalApprovedAmount += $total;
            $totalPaidAmount += $paid;
            $totalOutstandingAmount += $outstanding;

            if ($outstanding <= 0.00) {
                $paidCount++;
            } elseif ($paid > 0.00) {
                $partiallyPaidCount++;
            } else {
                $unpaidCount++;
            }

            if ($outstanding > 0.00 && $bill->due_date && $bill->due_date->toDateString() < $today) {
                $overdueCount++;
                $overdueAmount += $outstanding;
            }
        }

        return [
            'total_bills_count' => $bills->count(),
            'total_approved_amount' => round($totalApprovedAmount, 2),
            'total_approved_amount_formatted' => '৳'.number_format(round($totalApprovedAmount, 2), 2),

            'total_paid_amount' => round($totalPaidAmount, 2),
            'total_paid_amount_formatted' => '৳'.number_format(round($totalPaidAmount, 2), 2),

            'total_outstanding_amount' => round($totalOutstandingAmount, 2),
            'total_outstanding_amount_formatted' => '৳'.number_format(round($totalOutstandingAmount, 2), 2),

            'unpaid_bills_count' => $unpaidCount,
            'partially_paid_bills_count' => $partiallyPaidCount,
            'fully_paid_bills_count' => $paidCount,

            'overdue_bills_count' => $overdueCount,
            'overdue_amount' => round($overdueAmount, 2),
            'overdue_amount_formatted' => '৳'.number_format(round($overdueAmount, 2), 2),
        ];
    }

    /**
     * Reusable raw SQL for the posted allocations subquery in whereRaw clauses.
     */
    protected static function getRawPostedAllocationsSql(): string
    {
        return "SELECT COALESCE(SUM(vpa.allocated_amount), 0) FROM vendor_payment_allocations vpa INNER JOIN vendor_payments vp ON vp.id = vpa.vendor_payment_id WHERE vpa.vendor_bill_id = vendor_bills.id AND vp.status = 'posted' AND vp.deleted_at IS NULL";
    }
}
