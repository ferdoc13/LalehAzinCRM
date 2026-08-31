<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isManager();
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->isManager();
    }

    public function create(User $actor): bool
    {
        return $actor->isManager();
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->isManager();
    }

    public function delete(User $actor, User $user): bool
    {
        return false;
    }

    public function restore(User $actor, User $user): bool
    {
        return false;
    }

    public function forceDelete(User $actor, User $user): bool
    {
        return false;
    }

    public function block(User $actor, User $user): bool
    {
        return $actor->isManager() && $actor->isNot($user);
    }

    public function unblock(User $actor, User $user): bool
    {
        return $actor->isManager();
    }
}
