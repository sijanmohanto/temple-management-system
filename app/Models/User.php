<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'preferred_locale',
        'address',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Roles belonging to user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    /**
     * Audit logs associated with this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Check if user has a specific role by slug.
     */
    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return $this->hasAnyRole($roles);
        }

        return $this->roles->contains('slug', $roles);
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('slug', $roles)->isNotEmpty();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Super admin has all permissions implicitly
        if ($this->hasRole('super_admin')) {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role->permissions->contains('slug', $permissionSlug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assign a role to user.
     */
    public function assignRole(Role|string $role): void
    {
        $roleModel = is_string($role) ? Role::where('slug', $role)->firstOrFail() : $role;

        if (! $this->roles->contains('id', $roleModel->id)) {
            $this->roles()->attach($roleModel->id, ['created_at' => now()]);
            $this->load('roles');
        }
    }

    /**
     * Remove a role from user.
     */
    public function removeRole(Role|string $role): void
    {
        $roleModel = is_string($role) ? Role::where('slug', $role)->first() : $role;

        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
            $this->load('roles');
        }
    }

    /**
     * Determine if account is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get user primary role display title according to locale.
     */
    public function getPrimaryRoleTitleAttribute(): string
    {
        $firstRole = $this->roles->first();
        if (! $firstRole) {
            return app()->getLocale() === 'bn' ? 'ব্যবহারকারী' : 'User';
        }

        return app()->getLocale() === 'bn' ? $firstRole->name_bn : $firstRole->name_en;
    }

    /**
     * Generate normalized phone number variants (raw, 11-digit BD, +880, 880).
     *
     * @return array<int, string>
     */
    public static function getPhoneVariants(?string $phone): array
    {
        if (empty($phone)) {
            return [];
        }

        $trimmed = trim($phone);
        $variants = [$trimmed];

        $digits = preg_replace('/\D+/', '', $trimmed);
        if (! empty($digits)) {
            if (str_starts_with($digits, '8801') && strlen($digits) === 13) {
                $std = substr($digits, 2); // 01XXXXXXXXX
                $variants[] = $std;
                $variants[] = '+88'.$std;
                $variants[] = '88'.$std;
            } elseif (str_starts_with($digits, '01') && strlen($digits) === 11) {
                $variants[] = $digits;
                $variants[] = '+88'.$digits;
                $variants[] = '88'.$digits;
            } else {
                $variants[] = $digits;
            }
        }

        return array_values(array_unique($variants));
    }

    /**
     * Resolve the linked registered Person profile in the temple directory.
     * Enforces active status, unambiguous candidate matching, and prevents
     * conflicting phone/email matches across different people.
     */
    public function person(): ?Person
    {
        if (empty($this->phone) && empty($this->email)) {
            return null;
        }

        $phoneMatches = collect();
        if (! empty($this->phone)) {
            $phoneVariants = self::getPhoneVariants($this->phone);
            $phoneMatches = Person::where('status', 'active')
                ->whereIn('phone', $phoneVariants)
                ->get();
        }

        $emailMatches = collect();
        if (! empty($this->email)) {
            $normalizedEmail = strtolower(trim($this->email));
            $emailMatches = Person::where('status', 'active')
                ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->get();
        }

        // If multiple active Person records share the same phone number, deny ambiguous resolution
        if ($phoneMatches->count() > 1) {
            Log::warning("Ambiguous devotee match: multiple active Person profiles matched phone for User ID {$this->id}");

            return null;
        }

        // If multiple active Person records share the same email, deny ambiguous resolution
        if ($emailMatches->count() > 1) {
            Log::warning("Ambiguous devotee match: multiple active Person profiles matched email for User ID {$this->id}");

            return null;
        }

        // If both matched, ensure they point to the exact same Person record
        if ($phoneMatches->isNotEmpty() && $emailMatches->isNotEmpty()) {
            if ($phoneMatches->first()->id !== $emailMatches->first()->id) {
                Log::warning("Conflicting devotee match: phone and email matched different Person profiles for User ID {$this->id}");

                return null;
            }

            return $phoneMatches->first();
        }

        // If only phone matched exactly one active Person
        if ($phoneMatches->isNotEmpty()) {
            return $phoneMatches->first();
        }

        // If only email matched exactly one active Person
        if ($emailMatches->isNotEmpty()) {
            return $emailMatches->first();
        }

        return null;
    }

    /**
     * Accessor for linked Person profile.
     */
    public function getPersonAttribute(): ?Person
    {
        return $this->person();
    }
}
