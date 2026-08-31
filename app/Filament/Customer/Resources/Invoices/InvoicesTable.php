<?php

namespace App\Filament\Customer\Resources\Invoices;

use App\Enums\InvoicePaymentStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('شماره فاکتور')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->label('تاریخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('مبلغ کل')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->label('تخفیف اعتبار')
                    ->numeric(decimalPlaces: 0)
                    ->toggleable(),
                TextColumn::make('payment_status')
                    ->label('وضعیت پرداخت')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('وضعیت پرداخت')
                    ->options(InvoicePaymentStatus::class),
            ])
            ->recordActions([
                ViewAction::make()->label('مشاهده'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
