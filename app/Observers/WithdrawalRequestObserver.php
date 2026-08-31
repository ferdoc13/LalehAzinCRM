<?php

namespace App\Observers;

use App\Enums\WithdrawalRequestStatus;
use App\Models\WithdrawalRequest;
use App\Notifications\WithdrawalCompletedNotification;
use App\Notifications\WithdrawalRequestCreatedNotification;

class WithdrawalRequestObserver
{
    public function created(WithdrawalRequest $withdrawalRequest): void
    {
        $withdrawalRequest->loadMissing('customer');
        $withdrawalRequest->customer?->notify(new WithdrawalRequestCreatedNotification($withdrawalRequest));
    }

    public function updated(WithdrawalRequest $withdrawalRequest): void
    {
        if (! $withdrawalRequest->wasChanged('status')) {
            return;
        }

        if ($withdrawalRequest->status !== WithdrawalRequestStatus::Done) {
            return;
        }

        $withdrawalRequest->loadMissing('customer');
        $withdrawalRequest->customer?->notify(new WithdrawalCompletedNotification($withdrawalRequest));
    }
}
