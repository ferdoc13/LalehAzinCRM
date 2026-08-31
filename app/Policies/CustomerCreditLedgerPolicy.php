<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\CustomerCreditLedger;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomerCreditLedgerPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof Customer) {
            return true;
        }

        return $actor instanceof User && $actor->can('View:Customer');
    }

    public function view(Authenticatable $actor, CustomerCreditLedger $customerCreditLedger): bool
    {
        if ($actor instanceof Customer) {
            return $customerCreditLedger->customer_id === $actor->id;
        }

        return $actor instanceof User && $actor->can('View:Customer');
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->can('Update:Customer');
    }

    public function update(Authenticatable $actor, CustomerCreditLedger $customerCreditLedger): bool
    {
        return false;
    }

    public function delete(Authenticatable $actor, CustomerCreditLedger $customerCreditLedger): bool
    {
        return $actor instanceof User && $actor->can('Delete:Customer');
    }
}
