<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
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

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role')->withTimestamps();
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

    /**
     * Attach a permission.
     */
    public function givePermission(Permission|string $permission): void
    {
        $permModel = is_string($permission) ? Permission::where('slug', $permission)->firstOrFail() : $permission;

        if (!$this->permissions->contains('id', $permModel->id)) {
            $this->permissions()->attach($permModel->id, ['created_at' => now()]);
            $this->load('permissions');
        }
    }
}
