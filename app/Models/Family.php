<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Family extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_code',
        'family_name',
        'primary_contact_person_id',
        'phone',
        'address',
        'area',
        'status',
        'notes',
    ];

    /**
     * Members belonging to this family.
     */
    public function members(): HasMany
    {
        return $this->hasMany(Person::class, 'family_id');
    }

    /**
     * Primary contact person for this family.
     */
    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'primary_contact_person_id');
    }

    /**
     * Pledges made by this family.
     */
    public function pledges(): HasMany
    {
        return $this->hasMany(Pledge::class);
    }

    /**
     * Payments made by this family.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CollectionPayment::class, 'payer_family_id');
    }

    /**
     * Determine if family is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Determine if family can be safely deleted.
     * Prevents deletion if any person is assigned to this family.
     */
    public function canBeSafelyDeleted(): bool
    {
        return $this->members()->count() === 0;
    }

    /**
     * Scope for active families.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for search.
     */
    public function scopeSearch(Builder $query, string|array|null $term): Builder
    {
        if (is_array($term)) {
            $term = $term['value'] ?? '';
        }

        if (empty($term)) {
            return $query;
        }

        $clean = trim((string) $term);

        return $query->where(function (Builder $q) use ($clean) {
            $q->where('family_code', 'like', "%{$clean}%")
                ->orWhere('family_name', 'like', "%{$clean}%")
                ->orWhere('phone', 'like', "%{$clean}%")
                ->orWhere('area', 'like', "%{$clean}%");
        });
    }

    /**
     * Generate unique family code: FAM-0001
     */
    public static function generateFamilyCode(): string
    {
        return DB::transaction(function () {
            $last = static::orderByDesc('id')->lockForUpdate()->first();
            $nextId = $last ? ($last->id + 1) : 1;

            return sprintf('FAM-%04d', $nextId);
        });
    }
}
