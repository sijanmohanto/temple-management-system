<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Pledge extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'pledge_code',
        'event_id',
        'person_id',
        'family_id',
        'pledge_year',
        'pledged_amount',
        'pledged_on',
        'purpose',
        'notes',
        'status',
        'created_by',
        'updated_by',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'pledged_amount' => 'decimal:2',
            'pledged_on' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Associated event.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Associated person (if individual pledge).
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Associated family (if family-level pledge).
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * User who created the pledge.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Allocations received against this pledge.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * Concurrency-safe unique pledge code generator (PLG-YYYY-0001).
     */
    public static function generatePledgeCode(int $year): string
    {
        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($year) {
                $prefix = "PLG-{$year}-";

                $lastPledge = self::withTrashed()
                    ->where('pledge_year', $year)
                    ->lockForUpdate()
                    ->orderByDesc('pledge_code')
                    ->first();

                $nextNumber = 1;
                if ($lastPledge && preg_match('/PLG-\d{4}-(\d+)/', $lastPledge->pledge_code, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::withTrashed()->where('pledge_code', $code)->exists()) {
                return $code;
            }
        }

        return "PLG-{$year}-".str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate verified paid amount from active allocations of verified payments towards pledge fulfillment.
     */
    public function getVerifiedPaidAmountAttribute(): float
    {
        return (float) $this->allocations()
            ->where('is_active', true)
            ->where('target_type', PaymentAllocation::TARGET_PLEDGE)
            ->whereHas('payment', function (Builder $query) {
                $query->where('status', CollectionPayment::STATUS_VERIFIED);
            })
            ->sum('amount');
    }

    /**
     * Calculate excess voluntary contributions made against this pledge.
     */
    public function getExcessContributedAmountAttribute(): float
    {
        return (float) $this->allocations()
            ->where('is_active', true)
            ->whereIn('target_type', [
                PaymentAllocation::TARGET_VOLUNTARY_EVENT,
                PaymentAllocation::TARGET_VOLUNTARY_GENERAL,
            ])
            ->whereHas('payment', function (Builder $query) {
                $query->where('status', CollectionPayment::STATUS_VERIFIED);
            })
            ->sum('amount');
    }

    /**
     * Calculate total amount contributed by devotee (pledge + excess).
     */
    public function getTotalContributedAmountAttribute(): float
    {
        return round($this->verified_paid_amount + $this->excess_contributed_amount, 2);
    }

    /**
     * Determine if there is any verified contribution exceeding the pledge.
     */
    public function getHasExcessContributionAttribute(): bool
    {
        return $this->excess_contributed_amount > 0.009;
    }

    /**
     * Calculate outstanding balance.
     */
    public function getOutstandingAmountAttribute(): float
    {
        return max(0.0, round((float) $this->pledged_amount - $this->verified_paid_amount, 2));
    }

    /**
     * Payer/Owner display name.
     */
    public function getOwnerNameAttribute(): string
    {
        if ($this->person) {
            return $this->person->name_en;
        }

        if ($this->family) {
            return $this->family->family_name.' (Family)';
        }

        return '—';
    }

    /**
     * Recalculate and update the pledge status based on verified active allocations.
     */
    public function recalculateStatus(): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return self::STATUS_CANCELLED;
        }

        $paid = $this->verified_paid_amount;
        $pledged = (float) $this->pledged_amount;

        $newStatus = match (true) {
            $paid >= $pledged => self::STATUS_PAID,
            $paid > 0.0 => self::STATUS_PARTIAL,
            default => self::STATUS_UNPAID,
        };

        if ($this->status !== $newStatus) {
            $this->update(['status' => $newStatus]);
        }

        return $newStatus;
    }

    /**
     * Filter scope.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (! empty($filters['pledge_year'])) {
            $query->where('pledge_year', $filters['pledge_year']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['person_id'])) {
            $query->where('person_id', $filters['person_id']);
        }

        if (! empty($filters['family_id'])) {
            $query->where('family_id', $filters['family_id']);
        }

        return $query;
    }

    /**
     * Search scope.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $clean = trim($term);

        return $query->where(function (Builder $q) use ($clean) {
            $q->where('pledge_code', 'like', "%{$clean}%")
                ->orWhere('purpose', 'like', "%{$clean}%")
                ->orWhereHas('person', function (Builder $pq) use ($clean) {
                    $pq->where('name_en', 'like', "%{$clean}%")
                        ->orWhere('name_bn', 'like', "%{$clean}%")
                        ->orWhere('phone', 'like', "%{$clean}%")
                        ->orWhere('person_code', 'like', "%{$clean}%");
                })
                ->orWhereHas('family', function (Builder $fq) use ($clean) {
                    $fq->where('family_name', 'like', "%{$clean}%")
                        ->orWhere('family_code', 'like', "%{$clean}%");
                })
                ->orWhereHas('event', function (Builder $eq) use ($clean) {
                    $eq->where('name_en', 'like', "%{$clean}%")
                        ->orWhere('event_code', 'like', "%{$clean}%");
                });
        });
    }
}
