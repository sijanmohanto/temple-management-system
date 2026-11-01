<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
use App\Models\VendorPayment;
use App\Models\VendorPaymentAllocation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class VendorPaymentService
{
    public function __construct(
        protected FinancePostingService $financePostingService
    ) {}

    /**
     * Create a new vendor payment with allocations in draft status.
     *
     * @param  array<string, mixed>  $data
     */
    public function createPayment(array $data, User $creator, ?UploadedFile $document = null): VendorPayment
    {
        return DB::transaction(function () use ($data, $creator, $document) {
            $vendor = Vendor::where('id', (int) ($data['vendor_id'] ?? 0))
                ->where('is_active', true)
                ->first();

            if (! $vendor) {
                throw ValidationException::withMessages([
                    'vendor_id' => [__('procurement.vendor_not_active_or_not_found')],
                ]);
            }

            $financialAccountId = (int) ($data['financial_account_id'] ?? 0);
            $account = FinancialAccount::where('id', $financialAccountId)
                ->where('account_type', FinancialAccount::TYPE_ASSET)
                ->where('is_active', true)
                ->first();

            if (! $account) {
                throw ValidationException::withMessages([
                    'financial_account_id' => [__('procurement.invalid_payment_account')],
                ]);
            }

            $amount = round((float) ($data['amount'] ?? 0), 2);
            if ($amount <= 0.00) {
                throw ValidationException::withMessages([
                    'amount' => [__('procurement.payment_amount_must_be_positive')],
                ]);
            }

            $allocationsData = $data['allocations'] ?? [];
            $validatedAllocations = $this->validateAllocations($vendor->id, $amount, $allocationsData);

            $paymentDate = $data['payment_date'] ?? now()->toDateString();
            $year = (int) date('Y', strtotime($paymentDate));
            $paymentNumber = VendorPayment::generatePaymentNumber($year);

            $documentPath = null;
            if ($document && $document->isValid()) {
                $documentPath = $document->store('vendor_payments', 'public');
            }

            $payment = VendorPayment::create([
                'payment_number' => $paymentNumber,
                'vendor_id' => $vendor->id,
                'financial_account_id' => $account->id,
                'payment_date' => $paymentDate,
                'payment_method' => $data['payment_method'],
                'amount' => $amount,
                'reference_number' => ! empty($data['reference_number']) ? trim($data['reference_number']) : null,
                'status' => VendorPayment::STATUS_DRAFT,
                'notes' => ! empty($data['notes']) ? trim($data['notes']) : null,
                'document_path' => $documentPath,
                'created_by' => $creator->id,
            ]);

            foreach ($validatedAllocations as $alloc) {
                VendorPaymentAllocation::create([
                    'vendor_payment_id' => $payment->id,
                    'vendor_bill_id' => $alloc['vendor_bill_id'],
                    'allocated_amount' => $alloc['allocated_amount'],
                    'notes' => $alloc['notes'] ?? null,
                ]);
            }

            AuditLogger::log(
                'vendor_payment_created',
                "Vendor Payment {$payment->payment_number} created for vendor {$vendor->name} (৳{$amount}).",
                $payment,
                ['amount' => $amount, 'vendor_id' => $vendor->id]
            );

            return $payment->load(['vendor', 'financialAccount', 'allocations.bill']);
        });
    }

    /**
     * Update a draft or rejected vendor payment.
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePayment(VendorPayment $payment, array $data, User $updater, ?UploadedFile $document = null): VendorPayment
    {
        if (! $payment->canBeEdited()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.payment_cannot_be_edited')],
            ]);
        }

        return DB::transaction(function () use ($payment, $data, $updater, $document) {
            $vendor = Vendor::where('id', (int) ($data['vendor_id'] ?? $payment->vendor_id))
                ->where('is_active', true)
                ->first();

            if (! $vendor) {
                throw ValidationException::withMessages([
                    'vendor_id' => [__('procurement.vendor_not_active_or_not_found')],
                ]);
            }

            $financialAccountId = (int) ($data['financial_account_id'] ?? $payment->financial_account_id);
            $account = FinancialAccount::where('id', $financialAccountId)
                ->where('account_type', FinancialAccount::TYPE_ASSET)
                ->where('is_active', true)
                ->first();

            if (! $account) {
                throw ValidationException::withMessages([
                    'financial_account_id' => [__('procurement.invalid_payment_account')],
                ]);
            }

            $amount = round((float) ($data['amount'] ?? $payment->amount), 2);
            if ($amount <= 0.00) {
                throw ValidationException::withMessages([
                    'amount' => [__('procurement.payment_amount_must_be_positive')],
                ]);
            }

            $allocationsData = $data['allocations'] ?? [];
            $validatedAllocations = $this->validateAllocations($vendor->id, $amount, $allocationsData, $payment->id);

            $documentPath = $payment->document_path;
            if ($document && $document->isValid()) {
                if ($payment->document_path && Storage::disk('public')->exists($payment->document_path)) {
                    Storage::disk('public')->delete($payment->document_path);
                }
                $documentPath = $document->store('vendor_payments', 'public');
            }

            $payment->update([
                'vendor_id' => $vendor->id,
                'financial_account_id' => $account->id,
                'payment_date' => $data['payment_date'] ?? $payment->payment_date,
                'payment_method' => $data['payment_method'] ?? $payment->payment_method,
                'amount' => $amount,
                'reference_number' => array_key_exists('reference_number', $data) ? (! empty($data['reference_number']) ? trim($data['reference_number']) : null) : $payment->reference_number,
                'notes' => array_key_exists('notes', $data) ? (! empty($data['notes']) ? trim($data['notes']) : null) : $payment->notes,
                'document_path' => $documentPath,
                'updated_by' => $updater->id,
            ]);

            // Recreate allocations
            $payment->allocations()->delete();
            foreach ($validatedAllocations as $alloc) {
                VendorPaymentAllocation::create([
                    'vendor_payment_id' => $payment->id,
                    'vendor_bill_id' => $alloc['vendor_bill_id'],
                    'allocated_amount' => $alloc['allocated_amount'],
                    'notes' => $alloc['notes'] ?? null,
                ]);
            }

            AuditLogger::log(
                'vendor_payment_updated',
                "Vendor Payment {$payment->payment_number} was updated by {$updater->name}.",
                $payment,
                ['amount' => $amount]
            );

            return $payment->load(['vendor', 'financialAccount', 'allocations.bill']);
        });
    }

    /**
     * Submit draft vendor payment for verification.
     */
    public function submitPayment(VendorPayment $payment, User $submitter): VendorPayment
    {
        if (! $payment->canBeSubmitted()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.payment_cannot_be_submitted')],
            ]);
        }

        $payment->update([
            'status' => VendorPayment::STATUS_SUBMITTED,
            'submitted_by' => $submitter->id,
            'submitted_at' => now(),
            'updated_by' => $submitter->id,
        ]);

        AuditLogger::log(
            'vendor_payment_submitted',
            "Vendor Payment {$payment->payment_number} submitted for verification by {$submitter->name}.",
            $payment
        );

        return $payment;
    }

    /**
     * Verify submitted vendor payment.
     */
    public function verifyPayment(VendorPayment $payment, ?string $notes, User $verifier): VendorPayment
    {
        if (! $payment->canBeVerified()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.payment_cannot_be_verified')],
            ]);
        }

        $payment->update([
            'status' => VendorPayment::STATUS_VERIFIED,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
            'verification_notes' => $notes,
            'updated_by' => $verifier->id,
        ]);

        AuditLogger::log(
            'vendor_payment_verified',
            "Vendor Payment {$payment->payment_number} verified by {$verifier->name}.",
            $payment,
            ['notes' => $notes]
        );

        return $payment;
    }

    /**
     * Authorize / Approve verified vendor payment.
     * Enforces separation of duties (creator cannot self-approve unless Super Admin).
     */
    public function approvePayment(VendorPayment $payment, User $approver): VendorPayment
    {
        if (! $payment->canBeApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.payment_cannot_be_approved')],
            ]);
        }

        // Separation of duties
        if (! $approver->hasRole('super_admin') && $payment->created_by === $approver->id) {
            throw ValidationException::withMessages([
                'approver' => [__('procurement.unauthorized_to_approve_own_payment')],
            ]);
        }

        $payment->update([
            'status' => VendorPayment::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'updated_by' => $approver->id,
        ]);

        AuditLogger::log(
            'vendor_payment_approved',
            "Vendor Payment {$payment->payment_number} authorized by {$approver->name}.",
            $payment
        );

        return $payment;
    }

    /**
     * Post approved vendor payment to General Ledger and settle allocated bill balances.
     */
    public function postPayment(VendorPayment $payment, User $poster): VendorPayment
    {
        if (! $payment->canBePosted()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.payment_cannot_be_posted')],
            ]);
        }

        return DB::transaction(function () use ($payment, $poster) {
            $payment->loadMissing(['allocations.bill', 'financialAccount', 'vendor']);

            // Re-validate that bill outstanding balances haven't been exceeded by competing postings
            foreach ($payment->allocations as $alloc) {
                $bill = VendorBill::lockForUpdate()->find($alloc->vendor_bill_id);
                if (! $bill) {
                    throw ValidationException::withMessages([
                        'allocations' => [__('procurement.bill_not_found')],
                    ]);
                }

                $availableOutstanding = round((float) $bill->total_amount - (float) $bill->paid_amount, 2);
                if (round((float) $alloc->allocated_amount, 2) > $availableOutstanding) {
                    throw ValidationException::withMessages([
                        'allocations' => [__('procurement.allocation_exceeds_outstanding_at_posting', [
                            'bill' => $bill->bill_number,
                            'outstanding' => number_format($availableOutstanding, 2),
                        ])],
                    ]);
                }
            }

            // Post to General Ledger via FinancePostingService
            $this->financePostingService->postVendorPayment($payment, $poster->id);

            AuditLogger::log(
                'vendor_payment_posted',
                "Vendor Payment {$payment->payment_number} posted to GL and settled against allocated bills by {$poster->name}.",
                $payment,
                ['amount' => $payment->amount]
            );

            return $payment->fresh(['vendor', 'financialAccount', 'allocations.bill', 'journalEntry']);
        });
    }

    /**
     * Convenience method: Approve and Post in a single action if authorized.
     */
    public function approveAndPostPayment(VendorPayment $payment, User $user): VendorPayment
    {
        if (! $payment->canBeApproved()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.payment_cannot_be_approved')],
            ]);
        }

        // Separation of duties check
        if (! $user->hasRole('super_admin') && $payment->created_by === $user->id) {
            throw ValidationException::withMessages([
                'approver' => [__('procurement.unauthorized_to_approve_own_payment')],
            ]);
        }

        $this->approvePayment($payment, $user);

        return $this->postPayment($payment, $user);
    }

    /**
     * Reject a submitted or verified payment.
     */
    public function rejectPayment(VendorPayment $payment, string $reason, User $rejecter): VendorPayment
    {
        if (! $payment->canBeRejected()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.payment_cannot_be_rejected')],
            ]);
        }

        $reason = trim($reason);
        if (empty($reason)) {
            throw ValidationException::withMessages([
                'reason' => [__('procurement.rejection_reason_required')],
            ]);
        }

        $payment->update([
            'status' => VendorPayment::STATUS_REJECTED,
            'rejected_by' => $rejecter->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
            'updated_by' => $rejecter->id,
        ]);

        AuditLogger::log(
            'vendor_payment_rejected',
            "Vendor Payment {$payment->payment_number} was rejected by {$rejecter->name}. Reason: {$reason}",
            $payment,
            ['reason' => $reason]
        );

        return $payment;
    }

    /**
     * Cancel an unposted payment (draft, submitted, verified, approved).
     */
    public function cancelPayment(VendorPayment $payment, string $reason, User $canceller): VendorPayment
    {
        if (! $payment->canBeCancelled()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.payment_cannot_be_cancelled')],
            ]);
        }

        $reason = trim($reason);
        if (empty($reason)) {
            throw ValidationException::withMessages([
                'reason' => [__('procurement.cancellation_reason_required')],
            ]);
        }

        $payment->update([
            'status' => VendorPayment::STATUS_CANCELLED,
            'cancelled_by' => $canceller->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'updated_by' => $canceller->id,
        ]);

        AuditLogger::log(
            'vendor_payment_cancelled',
            "Vendor Payment {$payment->payment_number} was cancelled by {$canceller->name}. Reason: {$reason}",
            $payment,
            ['reason' => $reason]
        );

        return $payment;
    }

    /**
     * Reverse an authorized posted payment in the General Ledger.
     */
    public function reversePayment(VendorPayment $payment, string $reason, User $reverser): VendorPayment
    {
        if (! $payment->canBeReversed()) {
            throw ValidationException::withMessages([
                'status' => [__('procurement.payment_cannot_be_reversed')],
            ]);
        }

        $reason = trim($reason);
        if (empty($reason)) {
            throw ValidationException::withMessages([
                'reason' => [__('procurement.reversal_reason_required')],
            ]);
        }

        return DB::transaction(function () use ($payment, $reason, $reverser) {
            $this->financePostingService->reverseVendorPayment($payment, $reverser->id, $reason);

            AuditLogger::log(
                'vendor_payment_reversed',
                "Vendor Payment {$payment->payment_number} was reversed in General Ledger by {$reverser->name}. Reason: {$reason}",
                $payment,
                ['reason' => $reason]
            );

            return $payment->fresh(['vendor', 'financialAccount', 'allocations.bill', 'journalEntry', 'reversalJournalEntry']);
        });
    }

    /**
     * Get payable (approved, unpaid or partially paid) bills for a vendor.
     *
     * @return Collection<int, VendorBill>
     */
    public function getPayableBills(Vendor $vendor, ?int $excludePaymentId = null): Collection
    {
        return VendorBill::where('vendor_id', $vendor->id)
            ->where('status', VendorBill::STATUS_APPROVED)
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'paid');
            })
            ->whereRaw('(total_amount - paid_amount) > 0.001')
            ->orderBy('bill_date', 'asc')
            ->get();
    }

    /**
     * Validate and normalize allocation data against vendor bills.
     *
     * @param  array<int, array<string, mixed>>  $allocationsData
     * @return array<int, array{vendor_bill_id: int, allocated_amount: float, notes: ?string}>
     */
    protected function validateAllocations(int $vendorId, float $totalPaymentAmount, array $allocationsData, ?int $excludePaymentId = null): array
    {
        $filtered = [];
        $billIds = [];

        foreach ($allocationsData as $item) {
            $billId = (int) ($item['vendor_bill_id'] ?? 0);
            $allocated = round((float) ($item['allocated_amount'] ?? 0), 2);

            if ($billId > 0 && $allocated > 0.00) {
                if (isset($filtered[$billId])) {
                    $filtered[$billId]['allocated_amount'] += $allocated;
                } else {
                    $filtered[$billId] = [
                        'vendor_bill_id' => $billId,
                        'allocated_amount' => $allocated,
                        'notes' => ! empty($item['notes']) ? trim($item['notes']) : null,
                    ];
                }
                $billIds[] = $billId;
            }
        }

        if (empty($filtered)) {
            throw ValidationException::withMessages([
                'allocations' => [__('procurement.at_least_one_bill_allocation_required')],
            ]);
        }

        // Check sum of allocations matches total payment amount
        $totalAllocated = round(array_sum(array_column($filtered, 'allocated_amount')), 2);
        if (abs($totalAllocated - $totalPaymentAmount) > 0.01) {
            throw ValidationException::withMessages([
                'allocations' => [__('procurement.allocations_sum_must_equal_payment_amount', [
                    'allocated' => number_format($totalAllocated, 2),
                    'total' => number_format($totalPaymentAmount, 2),
                ])],
            ]);
        }

        // Fetch bills and lock/check
        $bills = VendorBill::whereIn('id', array_keys($filtered))
            ->where('vendor_id', $vendorId)
            ->get()
            ->keyBy('id');

        if ($bills->count() !== count($filtered)) {
            throw ValidationException::withMessages([
                'allocations' => [__('procurement.one_or_more_bills_invalid_for_vendor')],
            ]);
        }

        foreach ($filtered as $billId => $entry) {
            /** @var VendorBill $bill */
            $bill = $bills->get($billId);

            if ($bill->status !== VendorBill::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'allocations' => [__('procurement.bill_must_be_approved_to_pay', ['bill' => $bill->bill_number])],
                ]);
            }

            // Calculate outstanding balance
            $currentOutstanding = round((float) $bill->total_amount - (float) $bill->paid_amount, 2);

            // If updating an existing payment, give back whatever this payment already allocated to this bill
            if ($excludePaymentId) {
                $existingAllocation = VendorPaymentAllocation::where('vendor_payment_id', $excludePaymentId)
                    ->where('vendor_bill_id', $billId)
                    ->first();
                if ($existingAllocation) {
                    $currentOutstanding += (float) $existingAllocation->allocated_amount;
                }
            }

            if ($currentOutstanding <= 0.00) {
                throw ValidationException::withMessages([
                    'allocations' => [__('procurement.bill_already_fully_paid', ['bill' => $bill->bill_number])],
                ]);
            }

            if ($entry['allocated_amount'] > ($currentOutstanding + 0.01)) {
                throw ValidationException::withMessages([
                    'allocations' => [__('procurement.allocation_exceeds_bill_outstanding', [
                        'bill' => $bill->bill_number,
                        'outstanding' => number_format($currentOutstanding, 2),
                        'allocated' => number_format($entry['allocated_amount'], 2),
                    ])],
                ]);
            }
        }

        return array_values($filtered);
    }
}
