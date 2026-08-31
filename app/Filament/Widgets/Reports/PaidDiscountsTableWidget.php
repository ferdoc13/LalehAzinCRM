<?php

namespace App\Filament\Widgets\Reports;

use App\Enums\WithdrawalRequestStatus;
use App\Filament\Concerns\AuthorizesManagers;
use App\Filament\Widgets\Concerns\InteractsWithReportDateRange;
use App\Models\WithdrawalRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PaidDiscountsTableWidget extends TableWidget
{
    use AuthorizesManagers;
    use InteractsWithReportDateRange;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('تخفیف‌های پرداخت‌شده (واریز شده)')
            ->description('درخواست‌های واریز انجام‌شده در بازه انتخابی')
            ->query(
                fn (): Builder => WithdrawalRequest::query()
                    ->with(['customer', 'bankAccount', 'discountRequest'])
                    ->where('status', WithdrawalRequestStatus::Done)
                    ->whereBetween('updated_at', [
                        $this->reportFrom(),
                        $this->reportUntil(),
                    ])
                    ->latest('updated_at'),
            )
            ->columns([
                TextColumn::make('customer.full_name')
                    ->label('نام مشتری'),
                TextColumn::make('amount')
                    ->label('مبلغ واریز')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('bankAccount.bank_name')
                    ->label('بانک'),
                TextColumn::make('bankAccount.sheba_number')
                    ->label('شبا')
                    ->toggleable(),
                TextColumn::make('discountRequest.final_amount')
                    ->label('مبلغ تخفیف مرتبط')
                    ->numeric(decimalPlaces: 0)
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('تاریخ واریز')
                    ->jalaliDateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([10, 25])
            ->emptyStateHeading('واریز انجام‌شده‌ای در این بازه نیست');
    }
}
