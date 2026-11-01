<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class VendorPayment extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REVERSED = 'reversed';

    public const METHOD_CASH = 'cash';

    public const METHOD_BANK = 'bank_transfer';

    public const METHOD_CHEQUE = 'cheque';

    public const METHOD_BKASH = 'bkash';

    public const METHOD_NAGAD = 'nagad';

    public const METHOD_ROCKET = 'rocket';

    public const METHOD_OTHER = 'other';

    protected $fillable = [
        'payment_number',
        'vendor_id',
        'financial_account_id',
        'payment_date',
        'payment_method',
        'amount',
        'reference_number',
        'status',
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
        'posted_by',
        'posted_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    /**
     * Supported payment methods.
     *
     * @return array<string, string>
     */
    public static function supportedPaymentMethods(): array
    {
        return [
            self::METHOD_CASH => __('procurement.method_cash'),
            self::METHOD_BANK => __('procurement.method_bank_transfer'),
            self::METHOD_CHEQUE => __('procurement.method_cheque'),
            self::METHOD_BKASH => __('procurement.method_bkash'),
            self::METHOD_NAGAD => __('procurement.method_nagad'),
            self::METHOD_ROCKET => __('procurement.method_rocket'),
            self::METHOD_OTHER => __('procurement.method_other'),
        ];
    }

    /**
     * Concurrency-safe unique payment number in VPY-YYYY-0001 format.
     */
    public static function generatePaymentNumber(?int $year = null): string
    {
        $year = $year ?: (int) date('Y');
        $prefix = "VPY-{$year}-";

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = DB::transaction(function () use ($prefix) {
                $last = self::withTrashed()
                    ->where('payment_number', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('payment_number')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/VPY-\d{4}-(\d+)/', $last->payment_number, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::withTrashed()->where('payment_number', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) (self::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    // Status transition helpers
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED]);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->allocations()->count() > 0 && $this->amount > 0;
    }

    public function canBeVerified(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function canBePosted(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canBeRejected(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_VERIFIED]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED, self::STATUS_VERIFIED, self::STATUS_APPROVED]);
    }

    public function canBeReversed(): bool
    {
        return $this->status === self::STATUS_POSTED && $this->journal_entry_id !== null;
    }

    // Status badge UI
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => '<span class="badge bg-secondary">'.__('procurement.status_draft').'</span>',
            self::STATUS_SUBMITTED => '<span class="badge bg-warning text-dark">'.__('procurement.status_submitted').'</span>',
            self::STATUS_VERIFIED => '<span class="badge bg-info text-dark">'.__('procurement.status_verified').'</span>',
            self::STATUS_APPROVED => '<span class="badge bg-primary">'.__('procurement.status_approved').'</span>',
            self::STATUS_POSTED => '<span class="badge bg-success">'.__('procurement.status_posted').'</span>',
            self::STATUS_REJECTED => '<span class="badge bg-danger">'.__('procurement.status_rejected').'</span>',
            self::STATUS_CANCELLED => '<span class="badge bg-dark">'.__('procurement.status_cancelled').'</span>',
            self::STATUS_REVERSED => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">'.__('procurement.status_reversed').'</span>',
            default => '<span class="badge bg-light text-dark">'.e($this->status).'</span>',
        };
    }

    // Relationships
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(VendorPaymentAllocation::class, 'vendor_payment_id');
    }

    public function bills(): BelongsToMany
    {
        return $this->belongsToMany(VendorBill::class, 'vendor_payment_allocations')
            ->withPivot('allocated_amount', 'notes')
            ->withTimestamps();
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

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

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function reverser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
