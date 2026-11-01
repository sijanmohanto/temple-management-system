<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PurchaseRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'request_number',
        'title',
        'request_date',
        'requested_by',
        'event_id',
        'budget_id',
        'status',
        'total_estimated_amount',
        'justification',
        'notes',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'is_converted_to_po',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'request_date' => 'date',
        'total_estimated_amount' => 'decimal:2',
        'is_converted_to_po' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Concurrency-safe unique request number generator (PRQ-YYYY-0001).
     */
    public static function generateRequestNumber(?int $year = null): string
    {
        $year = $year ?? (int) now()->year;
        $prefix = "PRQ-{$year}-";

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($prefix) {
                $last = self::withTrashed()
                    ->where('request_number', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('request_number')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/PRQ-\d{4}-(\d+)/', $last->request_number, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::withTrashed()->where('request_number', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) (self::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Line items in requisition.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseRequestItem::class, 'purchase_request_id');
    }

    /**
     * Requesting user.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * User who created the record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated the record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * User who authorized approval.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * User who rejected the request.
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Optional event/festival link.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Optional budget spending plan link.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    /**
     * Converted Purchase Order (if converted).
     */
    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, 'purchase_request_id');
    }

    /**
     * Can requisition be modified.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    /**
     * Can requisition be submitted for approval.
     */
    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_DRAFT && (float) $this->total_estimated_amount > 0;
    }

    /**
     * Can requisition be approved or rejected.
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    /**
     * Can requisition be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED], true);
    }

    /**
     * Can requisition be converted to Purchase Order.
     */
    public function canBeConvertedToPo(): bool
    {
        return $this->status === self::STATUS_APPROVED && ! $this->is_converted_to_po;
    }

    /**
     * Render Bootstrap 5 status badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-pencil me-1"></i>'.__('procurement.status_draft').'</span>',
            self::STATUS_SUBMITTED => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-hourglass-split me-1"></i>'.__('procurement.status_submitted').'</span>',
            self::STATUS_APPROVED => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>'.__('procurement.status_approved').'</span>',
            self::STATUS_REJECTED => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>'.__('procurement.status_rejected').'</span>',
            self::STATUS_CANCELLED => '<span class="badge bg-dark-subtle text-dark border border-dark-subtle"><i class="bi bi-slash-circle me-1"></i>'.__('procurement.status_cancelled').'</span>',
            default => '<span class="badge bg-light text-dark border">'.$this->status.'</span>',
        };
    }
}
