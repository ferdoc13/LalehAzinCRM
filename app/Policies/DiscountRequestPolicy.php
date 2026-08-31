<?php

namespace App\Policies;

use App\Enums\DiscountRequestStatus;
use App\Models\DiscountRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class DiscountRequestPolicy
{
    public function viewAny(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->can('ViewAny:DiscountRequest');
    }

    public function view(Authenticatable $actor, DiscountRequest $discountRequest): bool
    {
        return $actor instanceof User
            && $actor->can('View:DiscountRequest')
            && ($actor->canAccessAllRecords() || $discountRequest->requested_by === $actor->id);
    }

    public function create(Authenticatable $actor): bool
    {
        return $actor instanceof User && $actor->can('Create:DiscountRequest');
    }

    public function update(Authenticatable $actor, DiscountRequest $discountRequest): bool
    {
        return false;
    }

    public function delete(Authenticatable $actor, DiscountRequest $discountRequest): bool
    {
        return $actor instanceof User && $actor->can('Delete:DiscountRequest');
    }

    public function restore(Authenticatable $actor, DiscountRequest $discountRequest): bool
    {
        return $actor instanceof User && $actor->can('Restore:DiscountRequest');
    }

    public function forceDelete(Authenticatable $actor, DiscountRequest $discountRequest): bool
    {
        return $actor instanceof User && $actor->can('ForceDelete:DiscountRequest');
    }

    public function review(Authenticatable $actor, DiscountRequest $discountRequest): bool
    {
        return $actor instanceof User
            && $actor->can('Review:DiscountRequest')
            && $discountRequest->status === DiscountRequestStatus::Pending;
    }
}
