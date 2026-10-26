<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommitteeMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'committee_id',
        'person_id',
        'designation_en',
        'designation_bn',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Associated committee.
     */
    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    /**
     * Associated person.
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Monthly contributions for this membership tenure.
     */
    public function monthlyContributions(): HasMany
    {
        return $this->hasMany(MonthlyCommitteeContribution::class, 'committee_membership_id');
    }

    /**
     * Localized designation accessor.
     */
    public function getDesignationAttribute(): string
    {
        if (app()->getLocale() === 'bn' && ! empty($this->designation_bn)) {
            return $this->designation_bn;
        }

        return $this->designation_en;
    }

    /**
     * Check if membership is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * End a membership safely, preserving the historical record.
     */
    public function endMembership(?string $endDate = null, string $status = 'ended', ?string $notes = null): void
    {
        $this->update([
            'end_date' => $endDate ?? now()->toDateString(),
            'status' => $status,
            'notes' => $notes ? trim(($this->notes ? $this->notes."\n" : '').$notes) : $this->notes,
        ]);
    }

    /**
     * Scope for active memberships.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for historical / past memberships.
     */
    public function scopeHistorical(Builder $query): Builder
    {
        return $query->where('status', '!=', 'active');
    }
}
