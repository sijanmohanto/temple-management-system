<?php

namespace App\Policies;

use App\Models\Committee;
use App\Models\User;

class CommitteePolicy
{
    /**
     * Determine whether the user can view any committees.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_people');
    }

    /**
     * Determine whether the user can view the committee details.
     */
    public function view(User $user, Committee $committee): bool
    {
        return $user->hasPermission('view_people');
    }

    /**
     * Determine whether the user can create committees.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_committee');
    }

    /**
     * Determine whether the user can update the committee.
     */
    public function update(User $user, Committee $committee): bool
    {
        return $user->hasPermission('manage_committee');
    }

    /**
     * Determine whether the user can manage memberships.
     */
    public function manageMembers(User $user, Committee $committee): bool
    {
        return $user->hasPermission('manage_committee');
    }
}
