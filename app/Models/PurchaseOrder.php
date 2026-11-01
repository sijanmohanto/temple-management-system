<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SENT = 'sent';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'purchase_request_id',
        'vendor_id',
        'event_id',
        'budget_id',
        'order_date',
        'expected_delivery_date',
        'status',
        'subtotal',
        'discount_amount',
        'total_amount',
        'terms_conditions',
        'delivery_address',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Concurrency-safe unique order number generator (PO-YYYY-0001).
     */
    public static function generateOrderNumber(?int $year = null): string
    {
        $year = $year ?? (int) now()->year;
        $prefix = "PO-{$year}-";

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($prefix) {
                $last = self::withTrashed()
                    ->where('order_number', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('order_number')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/PO-\d{4}-(\d+)/', $last->order_number, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::withTrashed()->where('order_number', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) (self::withTrashed()->count() + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Source purchase request.
     */
    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class, 'purchase_request_id');
    }

    /**
     * Selected active vendor.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    /**
     * Optional event linkage.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Optional budget plan linkage.
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    /**
     * Line items in this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    /**
     * Vendor bills linked to this purchase order.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(VendorBill::class, 'purchase_order_id');
    }

    /**
     * Total amount already billed across active/approved bills.
     */
    public function getBilledAmountAttribute(): float
    {
        return (float) $this->bills()
            ->whereNotIn('status', [VendorBill::STATUS_REJECTED, VendorBill::STATUS_CANCELLED])
            ->sum('total_amount');
    }

    /**
     * Remaining unbilled balance on this order.
     */
    public function getRemainingBillableAmountAttribute(): float
    {
        return max(0.00, round((float) $this->total_amount - $this->billed_amount, 2));
    }

    /**
     * Check if this purchase order can receive new bills.
     */
    public function canBeBilled(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_SENT, self::STATUS_COMPLETED], true)
            && $this->remaining_billable_amount > 0;
    }

    /**
     * User who created the order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who last updated the order.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * User who authorized the order.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Can order be modified.
     */
    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Can order be authorized/approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_DRAFT && $this->items()->count() > 0;
    }

    /**
     * Can order be marked as sent/issued to vendor.
     */
    public function canBeSent(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Can order be marked as completed.
     */
    public function canBeCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_SENT], true);
    }

    /**
     * Can order be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_APPROVED, self::STATUS_SENT], true);
    }

    /**
     * Render Bootstrap 5 status badge.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-pencil me-1"></i>'.__('procurement.status_draft').'</span>',
            self::STATUS_APPROVED => '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="bi bi-check-circle me-1"></i>'.__('procurement.status_approved').'</span>',
            self::STATUS_SENT => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-send me-1"></i>'.__('procurement.status_sent').'</span>',
            self::STATUS_COMPLETED => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2-all me-1"></i>'.__('procurement.status_completed').'</span>',
            self::STATUS_CANCELLED => '<span class="badge bg-dark-subtle text-dark border border-dark-subtle"><i class="bi bi-slash-circle me-1"></i>'.__('procurement.status_cancelled').'</span>',
            default => '<span class="badge bg-light text-dark border">'.$this->status.'</span>',
        };
    }
}
