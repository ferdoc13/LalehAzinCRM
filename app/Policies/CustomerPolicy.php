<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->can('ViewAny:Customer');
    }

    public function view(Authenticatable $actor, Customer $customer): bool
    {
        if ($actor instanceof Customer) {
            return $actor->is($customer);
        }

        return $actor instanceof User
            && $actor->can('View:Customer')
            && ($actor->canAccessAllRecords() || $customer->employee_id === $actor->id);
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->can('Create:Customer');
    }

    public function update(Authenticatable $actor, Customer $customer): bool
    {
        if ($actor instanceof Customer) {
            return $actor->is($customer);
        }

        return $actor instanceof User
            && $actor->can('Update:Customer')
            && ($actor->canAccessAllRecords() || $customer->employee_id === $actor->id);
    }

    public function delete(Authenticatable $actor, Customer $customer): bool
    {
        return $actor instanceof User
            && $actor->can('Delete:Customer')
            && ($actor->canAccessAllRecords() || $customer->employee_id === $actor->id);
    }

    public function restore(Authenticatable $actor, Customer $customer): bool
    {
        return $actor instanceof User && $actor->can('Restore:Customer');
    }

    public function forceDelete(Authenticatable $actor, Customer $customer): bool
    {
        return $actor instanceof User && $actor->can('ForceDelete:Customer');
    }
}
