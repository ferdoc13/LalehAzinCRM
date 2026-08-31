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

        return $actor instanceof User && $actor->can('ViewAny:Invoice');
    }

    public function view(Authenticatable $actor, Invoice $invoice): bool
    {
        if ($actor instanceof Customer) {
            return $invoice->customer_id === $actor->id;
        }

        return $actor instanceof User
            && $actor->can('View:Invoice')
            && ($actor->canAccessAllRecords() || $invoice->employee_id === $actor->id);
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->can('Create:Invoice');
    }

    public function update(Authenticatable $actor, Invoice $invoice): bool
    {
        return $actor instanceof User
            && $actor->can('Update:Invoice')
            && ($actor->canAccessAllRecords() || $invoice->employee_id === $actor->id);
    }

    public function delete(Authenticatable $actor, Invoice $invoice): bool
    {
        return $actor instanceof User
            && $actor->can('Delete:Invoice')
            && ($actor->canAccessAllRecords() || $invoice->employee_id === $actor->id);
    }

    public function restore(Authenticatable $actor, Invoice $invoice): bool
    {
        return $actor instanceof User && $actor->can('Restore:Invoice');
    }

    public function forceDelete(Authenticatable $actor, Invoice $invoice): bool
    {
        return $actor instanceof User && $actor->can('ForceDelete:Invoice');
    }
}
