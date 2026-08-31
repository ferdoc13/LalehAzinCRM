<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Notifications\DiscountAppliedNotification;
use App\Notifications\InvoiceCreatedNotification;

class InvoiceObserver
{
    public function created(Invoice $invoice): void
    {
        $invoice->loadMissing('customer');
        $invoice->customer?->notify(new InvoiceCreatedNotification($invoice));
    }

    public function updated(Invoice $invoice): void
    {
        if (! $invoice->wasChanged('discount_amount')) {
            return;
        }

        $previous = (float) $invoice->getOriginal('discount_amount');
        $current = (float) $invoice->discount_amount;

        if ($current <= 0 || $current <= $previous) {
            return;
        }

        $invoice->loadMissing('customer');
        $invoice->customer?->notify(new DiscountAppliedNotification($invoice));
    }
}
