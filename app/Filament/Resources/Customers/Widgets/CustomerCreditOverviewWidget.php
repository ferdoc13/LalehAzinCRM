<?php

namespace App\Filament\Resources\Customers\Widgets;

use App\Models\Customer;
use App\Services\CustomerCreditService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class CustomerCreditOverviewWidget extends StatsOverviewWidget
{
    public ?Customer $record = null;

    protected static bool $isLazy = false;

    protected ?string $heading = 'اعتبار مشتری';

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $balance = app(CustomerCreditService::class)->getBalance($this->record);

        return [
            Stat::make('موجودی فعلی', Number::format($balance, precision: 0).' ریال')
                ->description('قابل استفاده در فاکتور یا برداشت')
                ->color($balance > 0 ? 'success' : 'gray'),
        ];
    }
}
