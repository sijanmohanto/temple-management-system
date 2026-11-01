<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Budget extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_ANNUAL = 'annual';

    public const TYPE_EVENT = 'event';

    public const TYPE_GENERAL_FUND = 'general_fund';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_REVISED = 'revised';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'budget_code',
        'title_en',
        'title_bn',
        'budget_type',
        'fiscal_year',
        'event_id',
        'total_amount',
        'status',
        'notes',
        'version',
        'created_by',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'total_amount' => 'decimal:2',
        'version' => 'integer',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class, 'budget_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Concurrency-safe unique budget code generator (BDG-YYYY-0001).
     */
    public static function generateBudgetCode(?int $year = null): string
    {
        $year = $year ?? (int) now()->year;
        $prefix = "BDG-{$year}-";

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($prefix) {
                $last = self::withTrashed()
                    ->where('budget_code', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('budget_code')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/BDG-\d{4}-(\d+)/', $last->budget_code, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::withTrashed()->where('budget_code', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * State checking helpers
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSubmitted(): bool
    {
        return $this->status === self::STATUS_SUBMITTED;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true);
    }

    public function canBeSubmitted(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_REJECTED], true)
            && $this->items()->count() > 0
            && $this->total_amount > 0;
    }

    /**
     * Strict separation of duties: Submitter/Creator cannot approve unless super_admin.
     */
    public function canBeApprovedBy(User $user): bool
    {
        if (! $this->isSubmitted()) {
            return false;
        }

        if (! $user->hasPermission('approve_budgets')) {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Creator cannot approve own budget
        return $this->created_by !== $user->id;
    }

    public function canBeRejectedBy(User $user): bool
    {
        if (! $this->isSubmitted()) {
            return false;
        }

        return $user->hasPermission('approve_budgets');
    }

    /**
     * Localized title attribute.
     */
    public function getTitleAttribute(): string
    {
        if (app()->getLocale() === 'bn' && ! empty($this->title_bn)) {
            return $this->title_bn;
        }

        return $this->title_en;
    }

    /**
     * Localized type label.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->budget_type) {
            self::TYPE_ANNUAL => __('procurement.type_annual'),
            self::TYPE_EVENT => __('procurement.type_event'),
            self::TYPE_GENERAL_FUND => __('procurement.type_general_fund'),
            default => ucfirst((string) $this->budget_type),
        };
    }

    /**
     * Localized status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => '<span class="badge bg-secondary">'.__('procurement.status_draft').'</span>',
            self::STATUS_SUBMITTED => '<span class="badge bg-warning text-dark">'.__('procurement.status_submitted').'</span>',
            self::STATUS_APPROVED => '<span class="badge bg-success">'.__('procurement.status_approved').'</span>',
            self::STATUS_REJECTED => '<span class="badge bg-danger">'.__('procurement.status_rejected').'</span>',
            self::STATUS_REVISED => '<span class="badge bg-info text-dark">'.__('procurement.status_revised').'</span>',
            self::STATUS_CANCELLED => '<span class="badge bg-dark">'.__('procurement.status_cancelled').'</span>',
            default => '<span class="badge bg-secondary">'.e($this->status).'</span>',
        };
    }
}
