<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Invoice;
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
                TextEntry::make('customer.full_name')
                    ->label('نام مشتری'),
                TextEntry::make('employee.name')
                    ->label('کارمند ثبت‌کننده'),
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
                RepeatableEntry::make('discountRequests')
                    ->label('درخواست‌های تخفیف')
                    ->schema([
                        TextEntry::make('proposed_amount')
                            ->label('مبلغ پیشنهادی')
                            ->numeric(decimalPlaces: 0),
                        TextEntry::make('final_amount')
                            ->label('مبلغ نهایی')
                            ->numeric(decimalPlaces: 0)
                            ->placeholder('-'),
                        TextEntry::make('status')
                            ->label('وضعیت')
                            ->badge(),
                        TextEntry::make('requester.name')
                            ->label('ثبت‌کننده'),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->visible(fn (?Invoice $record): bool => (bool) $record?->discountRequests?->isNotEmpty()),
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
