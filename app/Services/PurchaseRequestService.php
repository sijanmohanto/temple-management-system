<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\FinancialAccount;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseRequestService
{
    /**
     * Create a new purchase request with line items.
     *
     * @param  array<string, mixed>  $data
     */
    public function createRequest(array $data, User $creator): PurchaseRequest
    {
        $this->validateBudgetAndScope($data);

        return DB::transaction(function () use ($data, $creator) {
            $requestDate = $data['request_date'] ?? now()->toDateString();
            $year = (int) date('Y', strtotime($requestDate));
            $requestNumber = PurchaseRequest::generateRequestNumber($year);

            $requestedBy = ! empty($data['requested_by']) ? (int) $data['requested_by'] : $creator->id;

            $purchaseRequest = PurchaseRequest::create([
                'request_number' => $requestNumber,
                'title' => $data['title'],
                'request_date' => $requestDate,
                'requested_by' => $requestedBy,
                'event_id' => ! empty($data['event_id']) ? (int) $data['event_id'] : null,
                'budget_id' => ! empty($data['budget_id']) ? (int) $data['budget_id'] : null,
                'status' => PurchaseRequest::STATUS_DRAFT,
                'total_estimated_amount' => 0.00,
                'justification' => $data['justification'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $creator->id,
                'updated_by' => $creator->id,
            ]);

            $totalAmount = $this->syncLineItems($purchaseRequest, $data['items'] ?? []);

            $purchaseRequest->update(['total_estimated_amount' => $totalAmount]);

            AuditLogger::log(
                'purchase_request_created',
                "Purchase Request {$purchaseRequest->request_number} was created as draft.",
                $purchaseRequest,
                ['total' => $totalAmount]
            );

            return $purchaseRequest->load(['items.expenseAccount', 'requester', 'event', 'budget']);
        });
    }

    /**
     * Update an existing draft or rejected purchase request.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateRequest(PurchaseRequest $request, array $data, User $updater): PurchaseRequest
    {
        if (! $request->canBeEdited()) {
            throw ValidationException::withMessages([
                'request' => [__('procurement.request_cannot_be_edited')],
            ]);
        }

        $this->validateBudgetAndScope($data);

        return DB::transaction(function () use ($request, $data, $updater) {
            $requestedBy = ! empty($data['requested_by']) ? (int) $data['requested_by'] : $request->requested_by;

            $request->update([
                'title' => $data['title'],
                'request_date' => $data['request_date'] ?? $request->request_date,
                'requested_by' => $requestedBy,
                'event_id' => ! empty($data['event_id']) ? (int) $data['event_id'] : null,
                'budget_id' => ! empty($data['budget_id']) ? (int) $data['budget_id'] : null,
                'justification' => $data['justification'] ?? null,
                'notes' => $data['notes'] ?? null,
                'updated_by' => $updater->id,
            ]);

            // Clear old items and recreate
            $request->items()->delete();
            $totalAmount = $this->syncLineItems($request, $data['items'] ?? []);

            $request->update(['total_estimated_amount' => $totalAmount]);

            AuditLogger::log(
                'purchase_request_updated',
                "Purchase Request {$request->request_number} was updated.",
                $request,
                ['total' => $totalAmount]
            );

            return $request->load(['items.expenseAccount', 'requester', 'event', 'budget']);
        });
    }

    /**
     * Submit request for authorization.
     */
    public function submitRequest(PurchaseRequest $request, User $user): PurchaseRequest
    {
        if (! $request->canBeSubmitted()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.request_cannot_be_submitted')],
            ]);
        }

        $request->update([
            'status' => PurchaseRequest::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'updated_by' => $user->id,
        ]);

        AuditLogger::log(
            'purchase_request_submitted',
            "Purchase Request {$request->request_number} was submitted for approval.",
            $request
        );

        return $request;
    }

    /**
     * Approve purchase request with separation of duties enforcement.
     */
    public function approveRequest(PurchaseRequest $request, User $approver): PurchaseRequest
    {
        if (! $request->canBeApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.request_cannot_be_approved')],
            ]);
        }

        // Enforce separation of duties: cannot approve own submission unless Super Admin
        if (! $approver->hasRole('super_admin')) {
            if ($request->created_by === $approver->id || $request->requested_by === $approver->id) {
                throw ValidationException::withMessages([
                    'approver' => [__('procurement.unauthorized_to_approve_own_request')],
                ]);
            }
        }

        return DB::transaction(function () use ($request, $approver) {
            $request->update([
                'status' => PurchaseRequest::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'updated_by' => $approver->id,
            ]);

            AuditLogger::log(
                'purchase_request_approved',
                "Purchase Request {$request->request_number} was approved by {$approver->name}.",
                $request
            );

            return $request;
        });
    }

    /**
     * Reject purchase request with mandatory reason.
     */
    public function rejectRequest(PurchaseRequest $request, string $reason, User $rejecter): PurchaseRequest
    {
        if (! $request->canBeApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.request_cannot_be_rejected')],
            ]);
        }

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => [__('procurement.rejection_reason_required')],
            ]);
        }

        return DB::transaction(function () use ($request, $trimmedReason, $rejecter) {
            $request->update([
                'status' => PurchaseRequest::STATUS_REJECTED,
                'rejected_by' => $rejecter->id,
                'rejected_at' => now(),
                'rejection_reason' => $trimmedReason,
                'updated_by' => $rejecter->id,
            ]);

            AuditLogger::log(
                'purchase_request_rejected',
                "Purchase Request {$request->request_number} was rejected by {$rejecter->name}. Reason: {$trimmedReason}",
                $request,
                ['reason' => $trimmedReason]
            );

            return $request;
        });
    }

    /**
     * Cancel purchase request.
     */
    public function cancelRequest(PurchaseRequest $request, ?string $reason, User $user): PurchaseRequest
    {
        if (! $request->canBeCancelled()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.request_cannot_be_cancelled')],
            ]);
        }

        $request->update([
            'status' => PurchaseRequest::STATUS_CANCELLED,
            'notes' => $reason ? ($request->notes."\n[Cancelled]: ".$reason) : $request->notes,
            'updated_by' => $user->id,
        ]);

        AuditLogger::log(
            'purchase_request_cancelled',
            "Purchase Request {$request->request_number} was cancelled.",
            $request
        );

        return $request;
    }

    /**
     * Check budget allocation and variance for a purchase request.
     */
    public function checkBudgetUtilization(PurchaseRequest $request): array
    {
        if (! $request->budget_id || ! $request->budget) {
            return [
                'has_budget' => false,
                'is_over_budget' => false,
                'items' => [],
            ];
        }

        $budget = $request->budget->load('items');
        $budgetItemsByAccount = $budget->items->keyBy('financial_account_id');

        $isOverBudget = false;
        $itemsAnalysis = [];

        foreach ($request->items as $item) {
            $budgetItem = $budgetItemsByAccount->get($item->financial_account_id);
            $allocated = $budgetItem ? (float) $budgetItem->allocated_amount : 0.00;
            $requested = (float) $item->estimated_total;

            $exceeds = $allocated > 0 ? ($requested > $allocated) : true;
            if ($exceeds) {
                $isOverBudget = true;
            }

            $itemsAnalysis[] = [
                'item_id' => $item->id,
                'description' => $item->description,
                'account_name' => $item->expenseAccount?->account_name_en,
                'allocated' => $allocated,
                'requested' => $requested,
                'is_exceeded' => $exceeds,
            ];
        }

        return [
            'has_budget' => true,
            'budget_code' => $budget->budget_code,
            'total_budget' => (float) $budget->total_amount,
            'is_over_budget' => $isOverBudget,
            'items' => $itemsAnalysis,
        ];
    }

    /**
     * Helper to validate and sync multi-item line inputs.
     * Computes line totals strictly on the server.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function syncLineItems(PurchaseRequest $request, array $items): float
    {
        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => [__('procurement.at_least_one_item_required')],
            ]);
        }

        $budget = $request->budget_id ? Budget::with('items')->find($request->budget_id) : null;
        $budgetItemsMap = $budget ? $budget->items->keyBy('financial_account_id') : collect();

        $total = 0.00;

        foreach ($items as $item) {
            $desc = trim($item['description'] ?? '');
            if ($desc === '') {
                continue;
            }

            $qty = round((float) ($item['quantity'] ?? 0), 2);
            $unitPrice = round((float) ($item['estimated_unit_price'] ?? 0), 2);

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

            $budgetItem = $budgetItemsMap->get($accountId);

            PurchaseRequestItem::create([
                'purchase_request_id' => $request->id,
                'description' => $desc,
                'quantity' => $qty,
                'unit' => trim($item['unit'] ?? 'pcs') ?: 'pcs',
                'estimated_unit_price' => $unitPrice,
                'estimated_total' => $lineTotal,
                'financial_account_id' => $accountId,
                'budget_item_id' => $budgetItem?->id,
                'notes' => $item['notes'] ?? null,
            ]);

            $total += $lineTotal;
        }

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'items' => [__('procurement.at_least_one_item_required')],
            ]);
        }

        return round($total, 2);
    }

    /**
     * Validate budget existence, approved status, and scope matching.
     *
     * @param  array<string, mixed>  $data
     */
    protected function validateBudgetAndScope(array $data): void
    {
        if (! empty($data['budget_id'])) {
            $budget = Budget::find((int) $data['budget_id']);

            if (! $budget) {
                throw ValidationException::withMessages([
                    'budget_id' => [__('procurement.budget_not_found')],
                ]);
            }

            if ($budget->status !== Budget::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'budget_id' => [__('procurement.only_approved_budget_can_be_linked')],
                ]);
            }

            // If an event is selected, verify budget matches event or is general/annual
            if (! empty($data['event_id'])) {
                $eventId = (int) $data['event_id'];
                if ($budget->budget_type === Budget::TYPE_EVENT && $budget->event_id !== $eventId) {
                    throw ValidationException::withMessages([
                        'budget_id' => [__('procurement.budget_event_mismatch')],
                    ]);
                }
            }
        }
    }
}
