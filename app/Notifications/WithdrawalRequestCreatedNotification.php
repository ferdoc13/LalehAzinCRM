<?php

namespace App\Notifications;

use App\Enums\SmsEventType;
use App\Models\Customer;
use App\Models\WithdrawalRequest;
use App\Notifications\Messages\MelipayamakMessage;

class WithdrawalRequestCreatedNotification extends MelipayamakNotification
{
    public function __construct(public WithdrawalRequest $withdrawalRequest) {}

    public function toMelipayamak(object $notifiable): MelipayamakMessage
    {
        $request = $this->withdrawalRequest->fresh() ?? $this->withdrawalRequest;
        $name = $notifiable instanceof Customer ? $notifiable->full_name : '';
        $amount = $this->formatAmount($request->amount);

        return new MelipayamakMessage(
            eventType: SmsEventType::Withdrawal,
            patternKey: 'withdrawal_request_created',
            params: [$name, $amount],
        );
    }
}
