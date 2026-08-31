<?php

namespace App\Observers;

use App\Models\Customer;
use App\Notifications\CustomerRegisteredNotification;

class CustomerObserver
{
    public function created(Customer $customer): void
    {
        $customer->notify(new CustomerRegisteredNotification);
    }
}
