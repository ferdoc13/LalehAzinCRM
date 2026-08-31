<?php

namespace App\Filament\Customer\Resources\Invoices;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice_number')
                    ->label('شماره فاکتور'),
                TextEntry::make('invoice_date')
                    ->label('تاریخ فاکتور')
                    ->jalaliDate('Y/m/d'),
                TextEntry::make('payment_status')
                    ->label('وضعیت پرداخت')
                    ->badge(),
                TextEntry::make('total_amount')
                    ->label('مبلغ کل')
                    ->numeric(decimalPlaces: 0),
                TextEntry::make('discount_amount')
                    ->label('تخفیف از اعتبار')
                    ->numeric(decimalPlaces: 0)
                    ->visible(fn ($record): bool => (float) ($record?->discount_amount ?? 0) > 0),
                RepeatableEntry::make('items')
                    ->label('اقلام فاکتور')
                    ->schema([
                        TextEntry::make('description')
                            ->label('شرح'),
                        TextEntry::make('quantity')
                            ->label('تعداد'),
                        TextEntry::make('unit_price')
                            ->label('قیمت واحد')
                            ->numeric(decimalPlaces: 0),
                        TextEntry::make('total_amount')
                            ->label('مبلغ کل')
                            ->numeric(decimalPlaces: 0),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }
}
