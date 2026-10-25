<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Committee extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name_en',
        'name_bn',
        'description_en',
        'description_bn',
        'term_start_date',
        'term_end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'term_start_date' => 'date',
            'term_end_date' => 'date',
        ];
    }

    /**
     * All memberships in this committee (current & historical).
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(CommitteeMembership::class)->orderByDesc('start_date');
    }

    /**
     * Active memberships in this committee.
     */
    public function activeMemberships(): HasMany
    {
        return $this->hasMany(CommitteeMembership::class)->where('status', 'active')->orderByDesc('start_date');
    }

    /**
     * Historical / past memberships in this committee.
     */
    public function pastMemberships(): HasMany
    {
        return $this->hasMany(CommitteeMembership::class)->where('status', '!=', 'active')->orderByDesc('end_date');
    }

    /**
     * Distinct people who have served on this committee.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'committee_memberships')
            ->withPivot(['id', 'designation_en', 'designation_bn', 'start_date', 'end_date', 'status', 'notes'])
            ->withTimestamps();
    }

    /**
     * Localized name accessor.
     */
    public function getNameAttribute(): string
    {
        if (app()->getLocale() === 'bn' && ! empty($this->name_bn)) {
            return $this->name_bn;
        }

        return $this->name_en;
    }

    /**
     * Determine if committee is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope for active committees.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Generate unique committee code: COM-YYYY-01
     */
    public static function generateCommitteeCode(?string $prefix = null): string
    {
        $year = date('Y');
        $basePrefix = $prefix ? strtoupper(trim($prefix)) : "COM-{$year}";

        return DB::transaction(function () use ($basePrefix) {
            $count = static::where('code', 'like', "{$basePrefix}-%")->count();

            return sprintf('%s-%02d', $basePrefix, $count + 1);
        });
    }
}
