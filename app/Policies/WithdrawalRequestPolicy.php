<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Contracts\Auth\Authenticatable;

class WithdrawalRequestPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        if ($actor instanceof Customer) {
            return true;
        }

        return $actor instanceof User && $actor->can('ViewAny:WithdrawalRequest');
    }

    public function view(Authenticatable $actor, WithdrawalRequest $withdrawalRequest): bool
    {
        if ($actor instanceof Customer) {
            return $withdrawalRequest->customer_id === $actor->id;
        }

        return $actor instanceof User && $actor->can('View:WithdrawalRequest');
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof Customer;
    }

    public function update(Authenticatable $actor, WithdrawalRequest $withdrawalRequest): bool
    {
        return $actor instanceof User && $actor->can('Update:WithdrawalRequest');
    }

    public function delete(Authenticatable $actor, WithdrawalRequest $withdrawalRequest): bool
    {
        return $actor instanceof User && $actor->can('Delete:WithdrawalRequest');
    }

    public function restore(Authenticatable $actor, WithdrawalRequest $withdrawalRequest): bool
    {
        return $actor instanceof User && $actor->can('Restore:WithdrawalRequest');
    }

    public function forceDelete(Authenticatable $actor, WithdrawalRequest $withdrawalRequest): bool
    {
        return $actor instanceof User && $actor->can('ForceDelete:WithdrawalRequest');
    }
}
