<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->canAccessAllRecords() || $invoice->employee_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->canAccessAllRecords() || $invoice->employee_id === $user->id;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->canAccessAllRecords() || $invoice->employee_id === $user->id;
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->canAccessAllRecords();
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->canAccessAllRecords();
    }
}
