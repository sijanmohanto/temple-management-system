<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model
{
    use HasFactory;

    public const TARGET_PLEDGE = 'pledge';

    public const TARGET_MONTHLY_CONTRIBUTION = 'monthly_contribution';

    public const TARGET_VOLUNTARY_EVENT = 'voluntary_event';

    public const TARGET_VOLUNTARY_GENERAL = 'voluntary_general';

    protected $fillable = [
        'collection_payment_id',
        'target_type',
        'pledge_id',
        'monthly_contribution_id',
        'event_id',
        'amount',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Parent collection payment.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(CollectionPayment::class, 'collection_payment_id');
    }

    /**
     * Target pledge (if allocated to a pledge).
     */
    public function pledge(): BelongsTo
    {
        return $this->belongsTo(Pledge::class);
    }

    /**
     * Target monthly committee contribution (if allocated to monthly dues).
     */
    public function monthlyContribution(): BelongsTo
    {
        return $this->belongsTo(MonthlyCommitteeContribution::class, 'monthly_contribution_id');
    }

    /**
     * Target event (if voluntary event donation or pledge event).
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Scope active allocations.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
