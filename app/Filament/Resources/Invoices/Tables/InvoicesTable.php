<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoicePaymentStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                TextColumn::make('employee.name')
                    ->label('کارمند ثبت‌کننده')
                    ->toggleable(),
                TextColumn::make('total_amount')
                    ->label('مبلغ کل')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->label('تخفیف اعتبار')
                    ->numeric(decimalPlaces: 0)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('invoice_date')
                    ->label('تاریخ')
                    ->jalaliDate('Y/m/d')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('وضعیت پرداخت')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('وضعیت پرداخت')
                    ->options(InvoicePaymentStatus::class),
            ])
            ->recordActions([
                ViewAction::make()->label('مشاهده'),
                EditAction::make()->label('ویرایش'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
