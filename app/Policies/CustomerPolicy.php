<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isStaff();
    }

    public function view(Authenticatable $actor, Customer $customer): bool
    {
        if ($actor instanceof Customer) {
            return $actor->is($customer);
        }

        return $actor instanceof User
            && ($actor->canAccessAllRecords() || $customer->employee_id === $actor->id);
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isStaff();
    }

    public function update(Authenticatable $actor, Customer $customer): bool
    {
        if ($actor instanceof Customer) {
            return $actor->is($customer);
        }

        return $actor instanceof User
            && ($actor->canAccessAllRecords() || $customer->employee_id === $actor->id);
    }

    public function delete(Authenticatable $actor, Customer $customer): bool
    {
        return $actor instanceof User
            && ($actor->canAccessAllRecords() || $customer->employee_id === $actor->id);
    }

    public function restore(Authenticatable $actor, Customer $customer): bool
    {
        return $actor instanceof User && $actor->canAccessAllRecords();
    }

    public function forceDelete(Authenticatable $actor, Customer $customer): bool
    {
        return $actor instanceof User && $actor->canAccessAllRecords();
    }
}
