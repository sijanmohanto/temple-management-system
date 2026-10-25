<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    /**
     * Determine whether the user can view any families.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_people');
    }

    /**
     * Determine whether the user can view the family.
     */
    public function view(User $user, Family $family): bool
    {
        return $user->hasPermission('view_people');
    }

    /**
     * Determine whether the user can create families.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_people');
    }

    /**
     * Determine whether the user can update the family.
     */
    public function update(User $user, Family $family): bool
    {
        return $user->hasPermission('manage_people');
    }

    /**
     * Determine whether the user can safely delete or deactivate the family.
     */
    public function delete(User $user, Family $family): bool
    {
        return $user->hasPermission('manage_people');
    }
}
