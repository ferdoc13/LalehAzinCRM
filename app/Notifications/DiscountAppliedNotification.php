<?php

namespace App\Notifications;

use App\Enums\SmsEventType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Notifications\Messages\MelipayamakMessage;

class DiscountAppliedNotification extends MelipayamakNotification
{
    public function __construct(public Invoice $invoice) {}

    public function toMelipayamak(object $notifiable): MelipayamakMessage
    {
        $invoice = $this->invoice->fresh() ?? $this->invoice;
        $name = $notifiable instanceof Customer ? $notifiable->full_name : '';
        $discount = $this->formatAmount($invoice->discount_amount);
        $total = $this->formatAmount($invoice->total_amount);

        return new MelipayamakMessage(
            eventType: SmsEventType::Discount,
            patternKey: 'discount_applied',
            params: [$name, $invoice->invoice_number, $discount, $total],
        );
    }
}
