<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->canAccessAllRecords() || $customer->employee_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->canAccessAllRecords() || $customer->employee_id === $user->id;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->canAccessAllRecords() || $customer->employee_id === $user->id;
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->canAccessAllRecords();
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->canAccessAllRecords();
    }
}
