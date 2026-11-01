<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Models\VendorBillItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VendorBillService
{
    public function __construct(
        protected FinancePostingService $financePostingService
    ) {}

    /**
     * Create a new vendor bill.
     *
     * @param  array<string, mixed>  $data
     */
    public function createBill(array $data, User $creator, ?UploadedFile $document = null): VendorBill
    {
        return DB::transaction(function () use ($data, $creator, $document) {
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

            // Check linked Purchase Order
            $po = null;
            if (! empty($data['purchase_order_id'])) {
                $po = PurchaseOrder::with(['items', 'vendor'])->find((int) $data['purchase_order_id']);

                if (! $po) {
                    throw ValidationException::withMessages([
                        'purchase_order_id' => [__('procurement.order_not_found')],
                    ]);
                }

                if (! in_array($po->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_COMPLETED], true)) {
                    throw ValidationException::withMessages([
                        'purchase_order_id' => [__('procurement.order_status_ineligible_for_billing')],
                    ]);
                }

                if ($po->vendor_id !== $vendor->id) {
                    throw ValidationException::withMessages([
                        'purchase_order_id' => [__('procurement.po_vendor_mismatch')],
                    ]);
                }
            }

            // Validate vendor invoice number uniqueness for this vendor
            $invoiceNumber = trim($data['vendor_invoice_number'] ?? '');
            $this->validateInvoiceUniqueness($vendor->id, $invoiceNumber);

            $billDate = $data['bill_date'] ?? now()->toDateString();
            $year = (int) date('Y', strtotime($billDate));
            $billNumber = VendorBill::generateBillNumber($year);

            // Handle optional document upload
            $documentPath = null;
            if ($document && $document->isValid()) {
                $documentPath = $document->store('vendor_bills', 'public');
            }

            $eventId = ! empty($data['event_id']) ? (int) $data['event_id'] : ($po?->event_id ?? null);
            $budgetId = ! empty($data['budget_id']) ? (int) $data['budget_id'] : ($po?->budget_id ?? null);

            $bill = VendorBill::create([
                'bill_number' => $billNumber,
                'vendor_id' => $vendor->id,
                'purchase_order_id' => $po?->id,
                'event_id' => $eventId,
                'budget_id' => $budgetId,
                'vendor_invoice_number' => $invoiceNumber,
                'bill_date' => $billDate,
                'due_date' => $data['due_date'] ?? null,
                'status' => VendorBill::STATUS_DRAFT,
                'subtotal' => 0.00,
                'discount_amount' => max(0, round((float) ($data['discount_amount'] ?? 0), 2)),
                'tax_amount' => max(0, round((float) ($data['tax_amount'] ?? 0), 2)),
                'total_amount' => 0.00,
                'notes' => $data['notes'] ?? null,
                'document_path' => $documentPath,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ]);

            $subtotal = $this->syncBillItems($bill, $data['items'] ?? [], $po);
            $discount = max(0, round((float) ($data['discount_amount'] ?? 0), 2));
            $tax = max(0, round((float) ($data['tax_amount'] ?? 0), 2));
            $totalAmount = max(0, round($subtotal - $discount + $tax, 2));

            $bill->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $totalAmount,
            ]);

            AuditLogger::log(
                'vendor_bill_created',
                "Vendor Bill {$bill->bill_number} (Invoice: {$bill->vendor_invoice_number}) created for vendor {$vendor->name}.",
                $bill,
                ['subtotal' => $subtotal, 'total' => $totalAmount]
            );

            return $bill->load(['items.expenseAccount', 'vendor', 'purchaseOrder', 'event', 'budget']);
        });
    }

    /**
     * Update an existing draft vendor bill.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBill(VendorBill $bill, array $data, User $updater, ?UploadedFile $document = null): VendorBill
    {
        if (! $bill->canBeEdited()) {
            throw ValidationException::withMessages([
                'bill' => [__('procurement.bill_cannot_be_edited')],
            ]);
        }

        return DB::transaction(function () use ($bill, $data, $updater, $document) {
            $vendorId = (int) ($data['vendor_id'] ?? $bill->vendor_id);
            $vendor = Vendor::where('id', $vendorId)
                ->where('is_active', true)
                ->first();

            if (! $vendor) {
                throw ValidationException::withMessages([
                    'vendor_id' => [__('procurement.vendor_not_active_or_not_found')],
                ]);
            }

            // Check linked Purchase Order
            $poId = array_key_exists('purchase_order_id', $data) ? (! empty($data['purchase_order_id']) ? (int) $data['purchase_order_id'] : null) : $bill->purchase_order_id;
            $po = null;
            if ($poId) {
                $po = PurchaseOrder::with(['items', 'vendor'])->find($poId);

                if (! $po) {
                    throw ValidationException::withMessages([
                        'purchase_order_id' => [__('procurement.order_not_found')],
                    ]);
                }

                if (! in_array($po->status, [PurchaseOrder::STATUS_APPROVED, PurchaseOrder::STATUS_SENT, PurchaseOrder::STATUS_COMPLETED], true)) {
                    throw ValidationException::withMessages([
                        'purchase_order_id' => [__('procurement.order_status_ineligible_for_billing')],
                    ]);
                }

                if ($po->vendor_id !== $vendor->id) {
                    throw ValidationException::withMessages([
                        'purchase_order_id' => [__('procurement.po_vendor_mismatch')],
                    ]);
                }
            }

            $invoiceNumber = trim($data['vendor_invoice_number'] ?? $bill->vendor_invoice_number);
            $this->validateInvoiceUniqueness($vendor->id, $invoiceNumber, $bill->id);

            // Handle optional new document upload
            $documentPath = $bill->document_path;
            if ($document && $document->isValid()) {
                if ($bill->document_path) {
                    Storage::disk('public')->delete($bill->document_path);
                }
                $documentPath = $document->store('vendor_bills', 'public');
            }

            $eventId = array_key_exists('event_id', $data)
                ? (! empty($data['event_id']) ? (int) $data['event_id'] : null)
                : $bill->event_id;

            $budgetId = array_key_exists('budget_id', $data)
                ? (! empty($data['budget_id']) ? (int) $data['budget_id'] : null)
                : $bill->budget_id;

            $bill->update([
                'vendor_id' => $vendor->id,
                'purchase_order_id' => $po?->id,
                'event_id' => $eventId,
                'budget_id' => $budgetId,
                'vendor_invoice_number' => $invoiceNumber,
                'bill_date' => $data['bill_date'] ?? $bill->bill_date,
                'due_date' => $data['due_date'] ?? $bill->due_date,
                'notes' => $data['notes'] ?? $bill->notes,
                'document_path' => $documentPath,
                'updated_by' => $updater->id,
            ]);

            // Re-sync line items
            $bill->items()->delete();
            $subtotal = $this->syncBillItems($bill, $data['items'] ?? [], $po, $bill);
            $discount = max(0, round((float) ($data['discount_amount'] ?? $bill->discount_amount), 2));
            $tax = max(0, round((float) ($data['tax_amount'] ?? $bill->tax_amount), 2));
            $totalAmount = max(0, round($subtotal - $discount + $tax, 2));

            $bill->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'total_amount' => $totalAmount,
            ]);

            AuditLogger::log(
                'vendor_bill_updated',
                "Vendor Bill {$bill->bill_number} was updated.",
                $bill,
                ['total' => $totalAmount]
            );

            return $bill->load(['items.expenseAccount', 'vendor', 'purchaseOrder', 'event', 'budget']);
        });
    }

    /**
     * Submit draft bill for verification.
     */
    public function submitBill(VendorBill $bill, User $submitter): VendorBill
    {
        if (! $bill->canBeSubmitted()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.bill_cannot_be_submitted')],
            ]);
        }

        $bill->update([
            'status' => VendorBill::STATUS_SUBMITTED,
            'submitted_by' => $submitter->id,
            'submitted_at' => now(),
            'updated_by' => $submitter->id,
        ]);

        AuditLogger::log(
            'vendor_bill_submitted',
            "Vendor Bill {$bill->bill_number} was submitted for verification.",
            $bill
        );

        return $bill;
    }

    /**
     * Verify submitted vendor bill.
     */
    public function verifyBill(VendorBill $bill, ?string $notes, User $verifier): VendorBill
    {
        if (! $bill->canBeVerified()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.bill_cannot_be_verified')],
            ]);
        }

        $bill->update([
            'status' => VendorBill::STATUS_VERIFIED,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'verification_notes' => $notes,
            'updated_by' => $verifier->id,
        ]);

        AuditLogger::log(
            'vendor_bill_verified',
            "Vendor Bill {$bill->bill_number} was verified by {$verifier->name}.",
            $bill,
            ['notes' => $notes]
        );

        return $bill;
    }

    /**
     * Authorize / Approve verified vendor bill.
     * Enforces separation of duties and posts journal entry to Accounts Payable.
     */
    public function approveBill(VendorBill $bill, User $approver): VendorBill
    {
        if (! $bill->canBeApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.bill_cannot_be_approved')],
            ]);
        }

        // Enforce separation of duties: Creator cannot self-approve unless Super Admin
        if (! $approver->hasRole('super_admin')) {
            if ($bill->created_by === $approver->id) {
                throw ValidationException::withMessages([
                    'approver' => [__('procurement.unauthorized_to_approve_own_bill')],
                ]);
            }
        }

        return DB::transaction(function () use ($bill, $approver) {
            $bill->update([
                'status' => VendorBill::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'updated_by' => $approver->id,
            ]);

            // Post to General Ledger as Accounts Payable obligation
            $this->financePostingService->postVendorBill($bill, $approver->id);

            AuditLogger::log(
                'vendor_bill_approved',
                "Vendor Bill {$bill->bill_number} was authorized by {$approver->name} and posted to Accounts Payable.",
                $bill,
                ['total_amount' => $bill->total_amount]
            );

            return $bill;
        });
    }

    /**
     * Reject a vendor bill with mandatory recorded reason.
     */
    public function rejectBill(VendorBill $bill, string $reason, User $rejecter): VendorBill
    {
        if (! $bill->canBeRejected()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.bill_cannot_be_rejected')],
            ]);
        }

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => [__('procurement.rejection_reason_required')],
            ]);
        }

        $bill->update([
            'status' => VendorBill::STATUS_REJECTED,
            'rejected_by' => $rejecter->id,
            'rejected_at' => now(),
            'rejection_reason' => $trimmedReason,
            'updated_by' => $rejecter->id,
        ]);

        AuditLogger::log(
            'vendor_bill_rejected',
            "Vendor Bill {$bill->bill_number} was rejected. Reason: {$trimmedReason}",
            $bill,
            ['reason' => $trimmedReason]
        );

        return $bill;
    }

    /**
     * Cancel a vendor bill with mandatory recorded reason.
     * Safely reverses posted journal entry if bill was previously approved.
     */
    public function cancelBill(VendorBill $bill, string $reason, User $canceller): VendorBill
    {
        if (! $bill->canBeCancelled()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.bill_cannot_be_cancelled')],
            ]);
        }

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw ValidationException::withMessages([
                'cancellation_reason' => [__('procurement.cancellation_reason_required')],
            ]);
        }

        return DB::transaction(function () use ($bill, $trimmedReason, $canceller) {
            $reversalEntry = null;
            // Reverse finance posting if previously approved and posted
            if ($bill->status === VendorBill::STATUS_APPROVED) {
                $reversalEntry = $this->financePostingService->reverseVendorBill($bill, $canceller->id, $trimmedReason);
            }

            $bill->update([
                'status' => VendorBill::STATUS_CANCELLED,
                'reversal_journal_entry_id' => $reversalEntry?->id,
                'cancelled_by' => $canceller->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $trimmedReason,
                'updated_by' => $canceller->id,
            ]);

            AuditLogger::log(
                'vendor_bill_cancelled',
                "Vendor Bill {$bill->bill_number} was cancelled. Reason: {$trimmedReason}",
                $bill,
                ['reason' => $trimmedReason]
            );

            return $bill;
        });
    }

    /**
     * Sync line items for a bill.
     * Validates positive quantities, non-negative prices, active expense accounts,
     * and cumulative PO quantities to prevent over-billing.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function syncBillItems(VendorBill $bill, array $items, ?PurchaseOrder $linkedPo = null, ?VendorBill $currentBill = null): float
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
            $itemDiscount = max(0, round((float) ($item['discount_amount'] ?? 0), 2));

            if ($qty <= 0 || $unitPrice < 0) {
                throw ValidationException::withMessages([
                    'items' => [__('procurement.invalid_item_quantity_or_price')],
                ]);
            }

            $lineBase = round($qty * $unitPrice, 2);
            $lineTotal = max(0, round($lineBase - $itemDiscount, 2));

            // Validate Expense Account from Chart of Accounts
            $accountId = (int) ($item['financial_account_id'] ?? 0);
            $account = FinancialAccount::where('id', $accountId)
                ->where('account_type', FinancialAccount::TYPE_EXPENSE)
                ->where('is_active', true)
                ->first();

            if (! $account) {
                throw ValidationException::withMessages([
                    'items' => [__('procurement.invalid_expense_account')],
                ]);
            }

            // PO Item linkage and cumulative limit check
            $poItemId = ! empty($item['purchase_order_item_id']) ? (int) $item['purchase_order_item_id'] : null;
            if ($poItemId && $linkedPo) {
                $poItem = PurchaseOrderItem::where('id', $poItemId)
                    ->where('purchase_order_id', $linkedPo->id)
                    ->first();

                if (! $poItem) {
                    throw ValidationException::withMessages([
                        'items' => [__('procurement.invalid_po_item_selected')],
                    ]);
                }

                // Calculate cumulative billed quantity across other active bills
                $alreadyBilledQtyQuery = VendorBillItem::where('purchase_order_item_id', $poItem->id)
                    ->whereHas('vendorBill', function ($q) use ($currentBill) {
                        $q->whereNotIn('status', [VendorBill::STATUS_REJECTED, VendorBill::STATUS_CANCELLED]);
                        if ($currentBill) {
                            $q->where('id', '!=', $currentBill->id);
                        }
                    });

                $alreadyBilledQty = (float) $alreadyBilledQtyQuery->sum('quantity');
                $maxRemainingQty = max(0, round((float) $poItem->quantity - $alreadyBilledQty, 2));

                if ($qty > $maxRemainingQty) {
                    throw ValidationException::withMessages([
                        'items' => [__('procurement.quantity_exceeds_po_remaining', [
                            'item' => $desc,
                            'remaining' => $maxRemainingQty,
                            'requested' => $qty,
                        ])],
                    ]);
                }
            }

            VendorBillItem::create([
                'vendor_bill_id' => $bill->id,
                'purchase_order_item_id' => $poItemId,
                'financial_account_id' => $accountId,
                'budget_item_id' => ! empty($item['budget_item_id']) ? (int) $item['budget_item_id'] : null,
                'description' => $desc,
                'quantity' => $qty,
                'unit' => trim($item['unit'] ?? 'pcs') ?: 'pcs',
                'unit_price' => $unitPrice,
                'discount_amount' => $itemDiscount,
                'line_total' => $lineTotal,
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

    /**
     * Validate that invoice number is unique for this vendor.
     */
    protected function validateInvoiceUniqueness(int $vendorId, string $invoiceNumber, ?int $ignoreBillId = null): void
    {
        if ($invoiceNumber === '') {
            throw ValidationException::withMessages([
                'vendor_invoice_number' => [__('procurement.vendor_invoice_number_required')],
            ]);
        }

        $query = VendorBill::where('vendor_id', $vendorId)
            ->where('vendor_invoice_number', $invoiceNumber)
            ->whereNotIn('status', [VendorBill::STATUS_CANCELLED]);

        if ($ignoreBillId) {
            $query->where('id', '!=', $ignoreBillId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'vendor_invoice_number' => [__('procurement.duplicate_vendor_invoice_number')],
            ]);
        }
    }

    /**
     * Fetch billable line items for a given Purchase Order with remaining unbilled quantities.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBillablePoItems(PurchaseOrder $order, ?VendorBill $currentBill = null): array
    {
        $order->loadMissing('items.expenseAccount');

        $billableItems = [];
        foreach ($order->items as $item) {
            $alreadyBilledQuery = VendorBillItem::where('purchase_order_item_id', $item->id)
                ->whereHas('vendorBill', function ($q) use ($currentBill) {
                    $q->whereNotIn('status', [VendorBill::STATUS_REJECTED, VendorBill::STATUS_CANCELLED]);
                    if ($currentBill) {
                        $q->where('id', '!=', $currentBill->id);
                    }
                });

            $alreadyBilledQty = (float) $alreadyBilledQuery->sum('quantity');
            $remainingQty = max(0.00, round((float) $item->quantity - $alreadyBilledQty, 2));

            $billableItems[] = [
                'id' => $item->id,
                'purchase_order_item_id' => $item->id,
                'description' => $item->description,
                'item_description' => $item->description,
                'po_quantity' => (float) $item->quantity,
                'ordered_quantity' => (float) $item->quantity,
                'already_billed_quantity' => $alreadyBilledQty,
                'billed_quantity' => $alreadyBilledQty,
                'remaining_quantity' => $remainingQty,
                'unit' => $item->unit,
                'unit_price' => (float) $item->unit_price,
                'financial_account_id' => $item->financial_account_id,
                'account_name' => $item->expenseAccount?->account_name_en,
                'budget_item_id' => $item->budget_item_id,
                'notes' => $item->notes,
                'is_fully_billed' => $remainingQty <= 0.00,
            ];
        }

        return $billableItems;
    }

    /**
     * Calculate vendor bill metrics.
     *
     * @return array<string, mixed>
     */
    public function getVendorBillMetrics(Vendor $vendor): array
    {
        return $vendor->getBillMetrics();
    }
}
