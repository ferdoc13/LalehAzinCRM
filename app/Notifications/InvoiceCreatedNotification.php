<?php

namespace App\Notifications;

use App\Enums\SmsEventType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Notifications\Messages\MelipayamakMessage;

class InvoiceCreatedNotification extends MelipayamakNotification
{
    public function __construct(public Invoice $invoice) {}

    public function toMelipayamak(object $notifiable): MelipayamakMessage
    {
        $invoice = $this->invoice->fresh() ?? $this->invoice;
        $name = $notifiable instanceof Customer ? $notifiable->full_name : '';
        $total = $this->formatAmount($invoice->total_amount);

        return new MelipayamakMessage(
            eventType: SmsEventType::Invoice,
            patternKey: 'invoice_created',
            params: [$name, $invoice->invoice_number, $total],
            text: $this->fallbackText('invoice_created', [
                'name' => $name,
                'invoice_number' => $invoice->invoice_number,
                'total_amount' => $total,
            ]),
        );
    }
}
