<?php

namespace App\Filament\Customer\Resources\BankAccounts\Pages;

use App\Filament\Customer\Resources\BankAccounts\BankAccountResource;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;

class ManageBankAccounts extends ManageRecords
{
    protected static string $resource = BankAccountResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'پروفایل و حساب‌های بانکی';
    }

    public function getHeading(): string|Htmlable
    {
        return 'حساب‌های بانکی';
    }

    public function getSubheading(): string|Htmlable|null
    {
        $customer = BankAccountResource::currentCustomer();

        return "{$customer->full_name} — {$customer->mobile}";
    }
}
