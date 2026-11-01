<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    /**
     * Convert an approved Purchase Request safely into a Purchase Order.
     * Enforces concurrency safety and prevents duplicate conversions.
     *
     * @param  array<string, mixed>  $data
     */
    public function convertFromRequest(PurchaseRequest $request, array $data, User $creator): PurchaseOrder
    {
        return DB::transaction(function () use ($request, $data, $creator) {
            // Pessimistic row locking on source purchase request
            $lockedRequest = PurchaseRequest::where('id', $request->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedRequest || $lockedRequest->status !== PurchaseRequest::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'purchase_request_id' => [__('procurement.only_approved_request_can_be_converted')],
                ]);
            }

            if ($lockedRequest->is_converted_to_po) {
                throw ValidationException::withMessages([
                    'purchase_request_id' => [__('procurement.request_already_converted')],
                ]);
            }

            // Validate active vendor
            $vendorId = (int) ($data['vendor_id'] ?? 0);
            $vendor = Vendor::where('id', $vendorId)
                ->where('is_active', true)
                ->first();

            if (! $vendor) {
                throw ValidationException::withMessages([
                    'vendor_id' => [__('procurement.vendor_not_active_or_not_found')],
                ]);
            }

            $orderDate = $data['order_date'] ?? now()->toDateString();
            $year = (int) date('Y', strtotime($orderDate));
            $orderNumber = PurchaseOrder::generateOrderNumber($year);

            $order = PurchaseOrder::create([
                'order_number' => $orderNumber,
                'purchase_request_id' => $lockedRequest->id,
                'vendor_id' => $vendor->id,
                'event_id' => $lockedRequest->event_id,
                'budget_id' => $lockedRequest->budget_id,
                'order_date' => $orderDate,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'subtotal' => 0.00,
                'discount_amount' => max(0, round((float) ($data['discount_amount'] ?? 0), 2)),
                'total_amount' => 0.00,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ]);

            $subtotal = $this->syncOrderItems($order, $data['items'] ?? []);
            $discount = max(0, round((float) ($data['discount_amount'] ?? 0), 2));
            $totalAmount = max(0, round($subtotal - $discount, 2));

            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $totalAmount,
            ]);

            // Mark request as converted
            $lockedRequest->update(['is_converted_to_po' => true]);

            AuditLogger::log(
                'purchase_order_created',
                "Purchase Order {$order->order_number} created from Request {$lockedRequest->request_number} for vendor {$vendor->name}.",
                $order,
                ['subtotal' => $subtotal, 'total' => $totalAmount]
            );

            return $order->load(['items.expenseAccount', 'vendor', 'purchaseRequest', 'event']);
        });
    }

    /**
     * Create a new direct purchase order.
     *
     * @param  array<string, mixed>  $data
     */
    public function createOrder(array $data, User $creator): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $creator) {
            $vendorId = (int) ($data['vendor_id'] ?? 0);
            $vendor = Vendor::where('id', $vendorId)
                ->where('is_active', true)
                ->first();

            if (! $vendor) {
                throw ValidationException::withMessages([
                    'vendor_id' => [__('procurement.vendor_not_active_or_not_found')],
                ]);
            }

            $orderDate = $data['order_date'] ?? now()->toDateString();
            $year = (int) date('Y', strtotime($orderDate));
            $orderNumber = PurchaseOrder::generateOrderNumber($year);

            $order = PurchaseOrder::create([
                'order_number' => $orderNumber,
                'purchase_request_id' => ! empty($data['purchase_request_id']) ? (int) $data['purchase_request_id'] : null,
                'vendor_id' => $vendor->id,
                'event_id' => ! empty($data['event_id']) ? (int) $data['event_id'] : null,
                'budget_id' => ! empty($data['budget_id']) ? (int) $data['budget_id'] : null,
                'order_date' => $orderDate,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'subtotal' => 0.00,
                'discount_amount' => max(0, round((float) ($data['discount_amount'] ?? 0), 2)),
                'total_amount' => 0.00,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ]);

            $subtotal = $this->syncOrderItems($order, $data['items'] ?? []);
            $discount = max(0, round((float) ($data['discount_amount'] ?? 0), 2));
            $totalAmount = max(0, round($subtotal - $discount, 2));

            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $totalAmount,
            ]);

            AuditLogger::log(
                'purchase_order_created',
                "Purchase Order {$order->order_number} created for vendor {$vendor->name}.",
                $order,
                ['total' => $totalAmount]
            );

            return $order->load(['items.expenseAccount', 'vendor', 'event']);
        });
    }

    /**
     * Update an existing draft purchase order.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateOrder(PurchaseOrder $order, array $data, User $updater): PurchaseOrder
    {
        if (! $order->canBeEdited()) {
            throw ValidationException::withMessages([
                'order' => [__('procurement.order_cannot_be_edited')],
            ]);
        }

        return DB::transaction(function () use ($order, $data, $updater) {
            $vendorId = (int) ($data['vendor_id'] ?? $order->vendor_id);
            $vendor = Vendor::where('id', $vendorId)
                ->where('is_active', true)
                ->first();

            if (! $vendor) {
                throw ValidationException::withMessages([
                    'vendor_id' => [__('procurement.vendor_not_active_or_not_found')],
                ]);
            }

            $order->update([
                'vendor_id' => $vendor->id,
                'order_date' => $data['order_date'] ?? $order->order_date,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? $order->expected_delivery_date,
                'event_id' => ! empty($data['event_id']) ? (int) $data['event_id'] : $order->event_id,
                'budget_id' => ! empty($data['budget_id']) ? (int) $data['budget_id'] : $order->budget_id,
                'terms_conditions' => $data['terms_conditions'] ?? $order->terms_conditions,
                'delivery_address' => $data['delivery_address'] ?? $order->delivery_address,
                'notes' => $data['notes'] ?? $order->notes,
                'updated_by' => $updater->id,
            ]);

            $order->items()->delete();
            $subtotal = $this->syncOrderItems($order, $data['items'] ?? []);
            $discount = max(0, round((float) ($data['discount_amount'] ?? 0), 2));
            $totalAmount = max(0, round($subtotal - $discount, 2));

            $order->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'total_amount' => $totalAmount,
            ]);

            AuditLogger::log(
                'purchase_order_updated',
                "Purchase Order {$order->order_number} was updated.",
                $order,
                ['total' => $totalAmount]
            );

            return $order->load(['items.expenseAccount', 'vendor', 'event']);
        });
    }

    /**
     * Authorize / Approve Purchase Order.
     */
    public function approveOrder(PurchaseOrder $order, User $approver): PurchaseOrder
    {
        if (! $order->canBeApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.order_cannot_be_approved')],
            ]);
        }

        // Enforce separation of duties: cannot approve own order unless Super Admin
        if (! $approver->hasRole('super_admin')) {
            if ($order->created_by === $approver->id) {
                throw ValidationException::withMessages([
                    'approver' => [__('procurement.unauthorized_to_approve_own_order')],
                ]);
            }
        }

        return DB::transaction(function () use ($order, $approver) {
            $order->update([
                'status' => PurchaseOrder::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'updated_by' => $approver->id,
            ]);

            AuditLogger::log(
                'purchase_order_approved',
                "Purchase Order {$order->order_number} was authorized by {$approver->name}.",
                $order
            );

            return $order;
        });
    }

    /**
     * Mark Purchase Order as sent to vendor.
     */
    public function sendOrder(PurchaseOrder $order, User $user): PurchaseOrder
    {
        if (! $order->canBeSent()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.order_cannot_be_sent')],
            ]);
        }

        $order->update([
            'status' => PurchaseOrder::STATUS_SENT,
            'updated_by' => $user->id,
        ]);

        AuditLogger::log(
            'purchase_order_sent',
            "Purchase Order {$order->order_number} was issued and marked as sent.",
            $order
        );

        return $order;
    }

    /**
     * Mark Purchase Order as completed / fulfilled.
     */
    public function completeOrder(PurchaseOrder $order, User $user): PurchaseOrder
    {
        if (! $order->canBeCompleted()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.order_cannot_be_completed')],
            ]);
        }

        $order->update([
            'status' => PurchaseOrder::STATUS_COMPLETED,
            'updated_by' => $user->id,
        ]);

        AuditLogger::log(
            'purchase_order_completed',
            "Purchase Order {$order->order_number} was marked as completed.",
            $order
        );

        return $order;
    }

    /**
     * Cancel Purchase Order.
     */
    public function cancelOrder(PurchaseOrder $order, ?string $reason, User $user): PurchaseOrder
    {
        if (! $order->canBeCancelled()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.order_cannot_be_cancelled')],
            ]);
        }

        return DB::transaction(function () use ($order, $reason, $user) {
            $order->update([
                'status' => PurchaseOrder::STATUS_CANCELLED,
                'notes' => $reason ? ($order->notes."\n[Cancelled]: ".$reason) : $order->notes,
                'updated_by' => $user->id,
            ]);

            // If linked to a source request, release conversion lock
            if ($order->purchaseRequest) {
                $order->purchaseRequest->update(['is_converted_to_po' => false]);
            }

            AuditLogger::log(
                'purchase_order_cancelled',
                "Purchase Order {$order->order_number} was cancelled.",
                $order
            );

            return $order;
        });
    }

    /**
     * Sync line items for a purchase order.
     * Computes line totals strictly on the server.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function syncOrderItems(PurchaseOrder $order, array $items): float
    {
        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => [__('procurement.at_least_one_item_required')],
            ]);
        }

        $subtotal = 0.00;

        foreach ($items as $item) {
            $desc = trim($item['description'] ?? '');
            if ($desc === '') {
                continue;
            }

            $qty = round((float) ($item['quantity'] ?? 0), 2);
            $unitPrice = round((float) ($item['unit_price'] ?? 0), 2);

            if ($qty <= 0 || $unitPrice <= 0) {
                throw ValidationException::withMessages([
                    'items' => [__('procurement.invalid_item_quantity_or_price')],
                ]);
            }

            $lineTotal = round($qty * $unitPrice, 2);

            $accountId = (int) ($item['financial_account_id'] ?? 0);
            $account = FinancialAccount::where('id', $accountId)
                ->where('account_type', 'expense')
                ->where('is_active', true)
                ->first();

            if (! $account) {
                throw ValidationException::withMessages([
                    'items' => [__('procurement.invalid_expense_account')],
                ]);
            }

            PurchaseOrderItem::create([
                'purchase_order_id' => $order->id,
                'purchase_request_item_id' => ! empty($item['purchase_request_item_id']) ? (int) $item['purchase_request_item_id'] : null,
                'description' => $desc,
                'quantity' => $qty,
                'unit' => trim($item['unit'] ?? 'pcs') ?: 'pcs',
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'financial_account_id' => $accountId,
                'budget_item_id' => ! empty($item['budget_item_id']) ? (int) $item['budget_item_id'] : null,
                'notes' => $item['notes'] ?? null,
            ]);

            $subtotal += $lineTotal;
        }

        if ($subtotal <= 0) {
            throw ValidationException::withMessages([
                'items' => [__('procurement.at_least_one_item_required')],
            ]);
        }

        return round($subtotal, 2);
    }
}
