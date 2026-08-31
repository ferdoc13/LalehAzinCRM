<?php

namespace App\Notifications;

use App\Enums\SmsEventType;
use App\Models\Customer;
use App\Models\DiscountRequest;
use App\Notifications\Messages\MelipayamakMessage;

class DiscountReviewedNotification extends MelipayamakNotification
{
    public function __construct(public DiscountRequest $discountRequest) {}

    public function toMelipayamak(object $notifiable): MelipayamakMessage
    {
        $request = $this->discountRequest->fresh() ?? $this->discountRequest;
        $name = $notifiable instanceof Customer ? $notifiable->full_name : '';
        $status = $request->status->getLabel();
        $amount = $this->formatAmount($request->final_amount);

        return new MelipayamakMessage(
            eventType: SmsEventType::Discount,
            text: $this->messageText('discount_reviewed', [
                'name' => $name,
                'status' => $status,
                'amount' => $amount,
            ]),
        );
    }
}
