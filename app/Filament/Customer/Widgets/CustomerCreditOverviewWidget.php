<?php

namespace App\Filament\Customer\Widgets;

use App\Models\Customer;
use App\Services\CustomerCreditService;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class CustomerCreditOverviewWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'موجودی اعتبار';

    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $customer = Filament::auth()->user();

        if (! $customer instanceof Customer) {
            return [];
        }

        $balance = app(CustomerCreditService::class)->getBalance($customer);

        return [
            Stat::make('موجودی فعلی', Number::format($balance, precision: 0).' ریال')
                ->description($customer->apply_credit_to_next_invoice
                    ? 'درخواست اعمال روی فاکتور بعدی ثبت شده است'
                    : 'قابل استفاده در فاکتور بعدی یا واریز به حساب')
                ->color($balance > 0 ? 'success' : 'gray'),
        ];
    }
}
