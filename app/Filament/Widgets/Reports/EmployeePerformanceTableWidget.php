<?php

namespace App\Filament\Widgets\Reports;

use App\Enums\InvoicePaymentStatus;
use App\Filament\Widgets\Concerns\AuthorizesWidgetPermission;
use App\Filament\Widgets\Concerns\InteractsWithReportDateRange;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class EmployeePerformanceTableWidget extends TableWidget
{
    use AuthorizesWidgetPermission;
    use InteractsWithReportDateRange;

    protected static function widgetPermission(): string
    {
        return 'ViewReports';
    }

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $from = $this->reportFrom();
        $until = $this->reportUntil();

        return $table
            ->heading('عملکرد کارمندان')
            ->description('تعداد مشتری و فاکتور ثبت‌شده در بازه انتخابی')
            ->query(
                fn (): Builder => User::query()
                    ->role(['employee', 'manager', 'admin'])
                    ->withCount([
                        'registeredCustomers as customers_count' => fn (Builder $query) => $query
                            ->whereBetween('created_at', [$from, $until]),
                        'invoices as invoices_count' => fn (Builder $query) => $query
                            ->where('payment_status', '!=', InvoicePaymentStatus::Cancelled)
                            ->whereDate('invoice_date', '>=', $from->toDateString())
                            ->whereDate('invoice_date', '<=', $until->toDateString()),
                    ])
                    ->withSum([
                        'invoices as invoices_total' => fn (Builder $query) => $query
                            ->where('payment_status', '!=', InvoicePaymentStatus::Cancelled)
                            ->whereDate('invoice_date', '>=', $from->toDateString())
                            ->whereDate('invoice_date', '<=', $until->toDateString()),
                    ], 'total_amount'),
            )
            ->columns([
                TextColumn::make('name')
                    ->label('کارمند')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customers_count')
                    ->label('مشتریان ثبت‌شده')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('invoices_count')
                    ->label('تعداد فاکتور')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('invoices_total')
                    ->label('مجموع مبلغ فاکتور')
                    ->numeric(decimalPlaces: 0)
                    ->placeholder('0')
                    ->sortable(),
            ])
            ->defaultSort('invoices_count', 'desc')
            ->paginated([10, 25])
            ->emptyStateHeading('کارمندی برای نمایش وجود ندارد');
    }
}
