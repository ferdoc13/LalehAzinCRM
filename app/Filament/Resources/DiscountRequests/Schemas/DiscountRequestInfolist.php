<?php

namespace App\Filament\Resources\DiscountRequests\Schemas;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DiscountRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('invoice.invoice_number')
                    ->label('شماره فاکتور')
                    ->url(fn ($record): string => InvoiceResource::getUrl('view', ['record' => $record->invoice_id])),
                TextEntry::make('customer.full_name')
                    ->label('نام مشتری'),
                TextEntry::make('requester.name')
                    ->label('ثبت‌کننده'),
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
                TextEntry::make('reviewer.name')
                    ->label('بررسی‌کننده')
                    ->placeholder('-'),
                TextEntry::make('reviewed_at')
                    ->label('تاریخ بررسی')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->placeholder('-'),
            ]);
    }
}
