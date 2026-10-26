<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class MonthlyCommitteeContribution extends Model
{
    use HasFactory;

    public const STATUS_UNPAID = 'unpaid';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_WAIVED = 'waived';

    protected $fillable = [
        'contribution_code',
        'committee_membership_id',
        'contribution_month',
        'expected_amount',
        'status',
        'notes',
        'created_by',
        'waived_by',
        'waived_at',
        'waiver_reason',
    ];

    protected function casts(): array
    {
        return [
            'expected_amount' => 'decimal:2',
            'contribution_month' => 'date',
            'waived_at' => 'datetime',
        ];
    }

    /**
     * Associated committee membership.
     */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(CommitteeMembership::class, 'committee_membership_id');
    }

    /**
     * User who created the obligation.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Payment allocations applied to this monthly contribution.
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'monthly_contribution_id');
    }

    /**
     * Concurrency-safe unique contribution code generator (MCC-YYYYMM-0001).
     */
    public static function generateContributionCode(string|Carbon $month): string
    {
        $date = $month instanceof Carbon ? $month : Carbon::parse($month);
        $period = $date->format('Ym');
        $prefix = "MCC-{$period}-";

        $maxRetries = 5;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $code = DB::transaction(function () use ($prefix) {
                $last = self::where('contribution_code', 'like', "{$prefix}%")
                    ->lockForUpdate()
                    ->orderByDesc('contribution_code')
                    ->first();

                $nextNumber = 1;
                if ($last && preg_match('/MCC-\d{6}-(\d+)/', $last->contribution_code, $matches)) {
                    $nextNumber = (int) $matches[1] + 1;
                }

                return $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            });

            if (! self::where('contribution_code', $code)->exists()) {
                return $code;
            }
        }

        return $prefix.str_pad((string) rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate verified paid amount from active allocations towards expected dues.
     */
    public function getVerifiedPaidAmountAttribute(): float
    {
        return (float) $this->allocations()
            ->where('is_active', true)
            ->where('target_type', PaymentAllocation::TARGET_MONTHLY_CONTRIBUTION)
            ->whereHas('payment', function (Builder $query) {
                $query->where('status', CollectionPayment::STATUS_VERIFIED);
            })
            ->sum('amount');
    }

    /**
     * Calculate excess voluntary contributions made against this monthly contribution.
     */
    public function getExcessContributedAmountAttribute(): float
    {
        return (float) $this->allocations()
            ->where('is_active', true)
            ->where('target_type', PaymentAllocation::TARGET_VOLUNTARY_GENERAL)
            ->whereHas('payment', function (Builder $query) {
                $query->where('status', CollectionPayment::STATUS_VERIFIED);
            })
            ->sum('amount');
    }

    /**
     * Calculate total contributed amount across dues and excess contribution.
     */
    public function getTotalContributedAmountAttribute(): float
    {
        return round($this->verified_paid_amount + $this->excess_contributed_amount, 2);
    }

    /**
     * Determine if member contributed more than expected dues.
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
        return max(0.0, round((float) $this->expected_amount - $this->verified_paid_amount, 2));
    }

    /**
     * Recalculate and update the contribution status based on verified active allocations.
     */
    public function recalculateStatus(): string
    {
        if ($this->status === self::STATUS_WAIVED) {
            return self::STATUS_WAIVED;
        }

        $paid = $this->verified_paid_amount;
        $expected = (float) $this->expected_amount;

        $newStatus = match (true) {
            $paid >= $expected => self::STATUS_PAID,
            $paid > 0.0 => self::STATUS_PARTIAL,
            default => self::STATUS_UNPAID,
        };

        if ($this->status !== $newStatus) {
            $this->update(['status' => $newStatus]);
        }

        return $newStatus;
    }

    /**
     * Formatted month string (e.g. October 2026).
     */
    public function getMonthNameAttribute(): string
    {
        return $this->contribution_month ? $this->contribution_month->format('F Y') : '—';
    }

    /**
     * Member person display name.
     */
    public function getMemberNameAttribute(): string
    {
        return $this->membership?->person?->name_en ?? '—';
    }

    /**
     * Filter scope.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['committee_id'])) {
            $query->whereHas('membership', function (Builder $mq) use ($filters) {
                $mq->where('committee_id', $filters['committee_id']);
            });
        }

        if (! empty($filters['contribution_month'])) {
            $date = Carbon::parse($filters['contribution_month'])->startOfMonth()->format('Y-m-d');
            $query->where('contribution_month', $date);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['person_id'])) {
            $query->whereHas('membership', function (Builder $mq) use ($filters) {
                $mq->where('person_id', $filters['person_id']);
            });
        }

        return $query;
    }
}
