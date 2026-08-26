<?php

namespace App\Policies;

use App\Enums\DiscountRequestStatus;
use App\Models\DiscountRequest;
use App\Models\User;

class DiscountRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, DiscountRequest $discountRequest): bool
    {
        return $user->canAccessAllRecords() || $discountRequest->requested_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, DiscountRequest $discountRequest): bool
    {
        return false;
    }

    public function delete(User $user, DiscountRequest $discountRequest): bool
    {
        return $user->canAccessAllRecords();
    }

    public function restore(User $user, DiscountRequest $discountRequest): bool
    {
        return $user->canAccessAllRecords();
    }

    public function forceDelete(User $user, DiscountRequest $discountRequest): bool
    {
        return $user->canAccessAllRecords();
    }

    public function review(User $user, DiscountRequest $discountRequest): bool
    {
        return $user->canAccessAllRecords()
            && $discountRequest->status === DiscountRequestStatus::Pending;
    }
}
