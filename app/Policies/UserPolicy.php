<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * A super admin may manage every other account but must not be able to
     * lock themselves out by deleting or deactivating their own account.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->isNot($model);
    }
}
