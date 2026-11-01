<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class VendorBill extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'bill_number',
        'vendor_id',
        'purchase_order_id',
        'event_id',
        'budget_id',
        'vendor_invoice_number',
        'bill_date',
        'due_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'paid_amount',
        'payment_status',
        'notes',
        'document_path',
        'journal_entry_id',
        'reversal_journal_entry_id',
        'submitted_by',
        'submitted_at',
        'verified_by',
        'verified_at',
        'verification_notes',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Concurrency-safe unique bill number generator (BIL-YYYY-0001).
     */
    public static function generateBillNumber(?int $year = null): string
    {
        $year = $year ?? (int) now()->year;
        $prefix = "BIL-{$year}-";

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($prefix) {
                $last = self::withTrashed()
                    ->where('bill_number', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('bill_number')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/BIL-\d{4}-(\d+)/', $last->bill_number, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::withTrashed()->where('bill_number', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) (self::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Vendor of the bill.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Optional linked Purchase Order.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    /**
     * Optional linked Event.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Optional linked Budget.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    /**
     * Line items in this bill.
     */
    public function items(): HasMany
    {
        return $this->hasMany(VendorBillItem::class, 'vendor_bill_id');
    }

    /**
     * Payment allocations applied to this bill.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(VendorPaymentAllocation::class, 'vendor_bill_id');
    }

    /**
     * Payments associated with this bill.
     */
    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(VendorPayment::class, 'vendor_payment_allocations')
            ->withPivot('allocated_amount', 'notes')
            ->withTimestamps();
    }

    /**
     * Remaining unpaid amount of this bill.
     */
    public function getOutstandingAmountAttribute(): float
    {
        return max(0.00, round((float) $this->total_amount - (float) ($this->paid_amount ?? 0), 2));
    }

    public function isFullyPaid(): bool
    {
        return $this->outstanding_amount <= 0.00;
    }

    public function isPartiallyPaid(): bool
    {
        return (float) $this->paid_amount > 0.00 && $this->outstanding_amount > 0.00;
    }

    public function isUnpaid(): bool
    {
        return (float) $this->paid_amount <= 0.00;
    }

    /**
     * Linked Journal Entry (once approved).
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /**
     * Linked Reversal Journal Entry (if cancelled after approval).
     */
    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Status transition eligibility checks.
     */
    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->items()->count() > 0;
    }

    public function canBeVerified(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_VERIFIED], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SUBMITTED,
            self::STATUS_VERIFIED,
            self::STATUS_APPROVED,
        ], true);
    }

    /**
     * Render Bootstrap 5 status badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-pencil me-1"></i>'.__('procurement.status_draft').'</span>',
            self::STATUS_SUBMITTED => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-clock-history me-1"></i>'.__('procurement.status_submitted').'</span>',
            self::STATUS_VERIFIED => '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-shield-check me-1"></i>'.__('procurement.status_verified').'</span>',
            self::STATUS_APPROVED => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>'.__('procurement.status_approved').'</span>',
            self::STATUS_REJECTED => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>'.__('procurement.status_rejected').'</span>',
            self::STATUS_CANCELLED => '<span class="badge bg-dark-subtle text-dark border border-dark-subtle"><i class="bi bi-slash-circle me-1"></i>'.__('procurement.status_cancelled').'</span>',
            default => '<span class="badge bg-light text-dark border">'.$this->status.'</span>',
        };
    }
}
