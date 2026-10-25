<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;

class PersonPolicy
{
    /**
     * Determine whether the user can view any people.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_people');
    }

    /**
     * Determine whether the user can view the person profile.
     */
    public function view(User $user, Person $person): bool
    {
        return $user->hasPermission('view_people');
    }

    /**
     * Determine whether the user can create people.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_people');
    }

    /**
     * Determine whether the user can update the person.
     */
    public function update(User $user, Person $person): bool
    {
        return $user->hasPermission('manage_people');
    }

    /**
     * Determine whether the user can deactivate/activate or delete the person.
     */
    public function delete(User $user, Person $person): bool
    {
        return $user->hasPermission('manage_people');
    }

    /**
     * Determine whether the user can view sensitive private notes.
     */
    public function viewPrivateNotes(User $user, Person $person): bool
    {
        return $user->hasRole('super_admin') || $user->hasPermission('manage_people');
    }
}
