<?php

namespace App\Filament\Resources\DiscountRequests\Tables;

use App\Enums\DiscountRequestStatus;
use App\Filament\Resources\DiscountRequests\Actions\ReviewDiscountRequestActions;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\DiscountRequest;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DiscountRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice.invoice_number')
                    ->label('شماره فاکتور')
                    ->searchable()
                    ->url(fn (DiscountRequest $record): string => InvoiceResource::getUrl('view', ['record' => $record->invoice_id])),
                TextColumn::make('customer.full_name')
                    ->label('نام مشتری')
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('customer', function ($customerQuery) use ($search): void {
                            $customerQuery->where(function ($nameQuery) use ($search): void {
                                $nameQuery
                                    ->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%");
                            });
                        });
                    }),
                TextColumn::make('requester.name')
                    ->label('ثبت‌کننده')
                    ->toggleable(),
                TextColumn::make('proposed_amount')
                    ->label('مبلغ پیشنهادی')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('final_amount')
                    ->label('مبلغ نهایی')
                    ->numeric(decimalPlaces: 0)
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge(),
                TextColumn::make('reviewer.name')
                    ->label('بررسی‌کننده')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('reviewed_at')
                    ->label('تاریخ بررسی')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(DiscountRequestStatus::class),
            ])
            ->recordActions([
                ViewAction::make()->label('مشاهده'),
                ...ReviewDiscountRequestActions::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
