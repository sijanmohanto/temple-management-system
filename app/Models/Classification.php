<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Classification extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name_en',
        'name_bn',
        'description_en',
        'description_bn',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    /**
     * People belonging to this classification.
     */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'person_classifications')
            ->withPivot(['assigned_at', 'notes'])
            ->withTimestamps();
    }

    /**
     * Localized name accessor.
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'bn' ? $this->name_bn : $this->name_en;
    }

    /**
     * Localized description accessor.
     */
    public function getDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'bn' ? ($this->description_bn ?? $this->description_en) : $this->description_en;
    }
}
