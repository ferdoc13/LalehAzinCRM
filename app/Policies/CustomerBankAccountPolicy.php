<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\CustomerBankAccount;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerBankAccountPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof Customer) {
            return true;
        }

        return $actor instanceof User && $actor->isStaff();
    }

    public function view(Authenticatable $actor, CustomerBankAccount $customerBankAccount): bool
    {
        if ($actor instanceof Customer) {
            return $customerBankAccount->customer_id === $actor->id;
        }

        return $this->staffOwnsCustomer($actor, $customerBankAccount);
    }

    public function create(Authenticatable $actor): bool
    {
        if ($actor instanceof Customer) {
            return true;
        }

        return $actor instanceof User && $actor->isStaff();
    }

    public function update(Authenticatable $actor, CustomerBankAccount $customerBankAccount): bool
    {
        if ($actor instanceof Customer) {
            return $customerBankAccount->customer_id === $actor->id;
        }

        return $this->staffOwnsCustomer($actor, $customerBankAccount);
    }

    public function delete(Authenticatable $actor, CustomerBankAccount $customerBankAccount): bool
    {
        if ($actor instanceof Customer) {
            return $customerBankAccount->customer_id === $actor->id;
        }

        return $this->staffOwnsCustomer($actor, $customerBankAccount);
    }

    public function restore(Authenticatable $actor, CustomerBankAccount $customerBankAccount): bool
    {
        return $actor instanceof User && $actor->canAccessAllRecords();
    }

    public function forceDelete(Authenticatable $actor, CustomerBankAccount $customerBankAccount): bool
    {
        return $actor instanceof User && $actor->canAccessAllRecords();
    }

    private function staffOwnsCustomer(Authenticatable $actor, CustomerBankAccount $customerBankAccount): bool
    {
        if (! $actor instanceof User) {
            return false;
        }

        if ($actor->canAccessAllRecords()) {
            return true;
        }

        return $customerBankAccount->customer?->employee_id === $actor->id;
    }
}
