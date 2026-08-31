<?php

namespace App\Filament\Widgets\Reports;

use App\Enums\InvoicePaymentStatus;
use App\Filament\Exports\InvoiceExporter;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Widgets\Concerns\AuthorizesWidgetPermission;
use App\Filament\Widgets\Concerns\InteractsWithReportDateRange;
use App\Models\Invoice;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ReportInvoicesTableWidget extends TableWidget
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
        return $table
            ->heading('فاکتورها')
            ->description('فاکتورهای بازه انتخابی با امکان خروجی Excel')
            ->query(
                fn (): Builder => Invoice::query()
                    ->with(['customer', 'employee'])
                    ->whereDate('invoice_date', '>=', $this->reportFrom()->toDateString())
                    ->whereDate('invoice_date', '<=', $this->reportUntil()->toDateString())
                    ->latest('invoice_date'),
            )
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('شماره فاکتور')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.full_name')
                    ->label('نام مشتری'),
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
                    ->toggleable(),
                TextColumn::make('invoice_date')
                    ->label('تاریخ')
                    ->jalaliDate('Y/m/d')
                    ->sortable(),
                TextColumn::make('payment_status')
                    ->label('وضعیت پرداخت')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->label('وضعیت پرداخت')
                    ->options(InvoicePaymentStatus::class),
                SelectFilter::make('employee_id')
                    ->label('کارمند')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('خروجی Excel')
                    ->exporter(InvoiceExporter::class)
                    ->formats([ExportFormat::Xlsx]),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('مشاهده')
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('invoice_date', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('فاکتوری در این بازه یافت نشد');
    }
}
