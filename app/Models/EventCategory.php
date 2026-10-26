<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_bn',
        'slug',
        'description_en',
        'description_bn',
        'status',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Events belonging to this category.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Scope for active categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
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
            $q->where('name_en', 'like', "%{$clean}%")
                ->orWhere('name_bn', 'like', "%{$clean}%")
                ->orWhere('slug', 'like', "%{$clean}%");
        });
    }

    /**
     * Determine if category can be safely deleted.
     * Blocked if is_system or has existing events.
     */
    public function canBeSafelyDeleted(): bool
    {
        return ! $this->is_system && $this->events()->count() === 0;
    }

    /**
     * Check if active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
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
     * Localized description accessor.
     */
    public function getDescriptionAttribute(): ?string
    {
        if (app()->getLocale() === 'bn' && ! empty($this->description_bn)) {
            return $this->description_bn;
        }

        return $this->description_en;
    }
}
