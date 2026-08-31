<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class InvoicePolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof Customer) {
            return true;
        }

        return $actor instanceof User && $actor->isStaff();
    }

    public function view(Authenticatable $actor, Invoice $invoice): bool
    {
        if ($actor instanceof Customer) {
            return $invoice->customer_id === $actor->id;
        }

        return $actor instanceof User
            && ($actor->canAccessAllRecords() || $invoice->employee_id === $actor->id);
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->isStaff();
    }

    public function update(Authenticatable $actor, Invoice $invoice): bool
    {
        return $actor instanceof User
            && ($actor->canAccessAllRecords() || $invoice->employee_id === $actor->id);
    }

    public function delete(Authenticatable $actor, Invoice $invoice): bool
    {
        return $actor instanceof User
            && ($actor->canAccessAllRecords() || $invoice->employee_id === $actor->id);
    }

    public function restore(Authenticatable $actor, Invoice $invoice): bool
    {
        return $actor instanceof User && $actor->canAccessAllRecords();
    }

    public function forceDelete(Authenticatable $actor, Invoice $invoice): bool
    {
        return $actor instanceof User && $actor->canAccessAllRecords();
    }
}
