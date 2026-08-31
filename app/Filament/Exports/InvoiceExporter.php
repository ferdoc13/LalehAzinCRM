<?php

namespace App\Filament\Exports;

use App\Models\Invoice;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class InvoiceExporter extends Exporter
{
    protected static ?string $model = Invoice::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('invoice_number')
                ->label('شماره فاکتور'),
            ExportColumn::make('customer.full_name')
                ->label('نام مشتری'),
            ExportColumn::make('employee.name')
                ->label('کارمند ثبت‌کننده'),
            ExportColumn::make('total_amount')
                ->label('مبلغ کل'),
            ExportColumn::make('discount_amount')
                ->label('تخفیف اعتبار'),
            ExportColumn::make('invoice_date')
                ->label('تاریخ فاکتور'),
            ExportColumn::make('payment_status')
                ->label('وضعیت پرداخت')
                ->formatStateUsing(fn ($state) => $state?->getLabel() ?? $state),
            ExportColumn::make('created_at')
                ->label('تاریخ ثبت'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'خروجی فاکتورها آماده شد. '.Number::format($export->successful_rows).' ردیف صادر شد.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' ردیف ناموفق بود.';
        }

        return $body;
    }
}
