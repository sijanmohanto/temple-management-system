<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'module',
        'name_en',
        'name_bn',
        'description_en',
        'description_bn',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role')->withTimestamps();
    }

    /**
     * Get localized name.
     */
    public function getNameAttribute(): string
    {
        return app()->getLocale() === 'bn' ? $this->name_bn : $this->name_en;
    }

    /**
     * Get localized description.
     */
    public function getDescriptionAttribute(): ?string
    {
        return app()->getLocale() === 'bn' ? $this->description_bn : $this->description_en;
    }
}
