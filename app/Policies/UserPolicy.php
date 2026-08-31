<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('ViewAny:User');
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->can('View:User');
    }

    public function create(User $actor): bool
    {
        return $actor->can('Create:User');
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->can('Update:User');
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
        return $actor->can('Block:User') && $actor->isNot($user);
    }

    public function unblock(User $actor, User $user): bool
    {
        return $actor->can('Unblock:User');
    }
}
