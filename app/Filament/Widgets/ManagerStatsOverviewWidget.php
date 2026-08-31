<?php

namespace App\Filament\Widgets;

use App\Enums\CustomerType;
use App\Enums\DiscountRequestStatus;
use App\Enums\InvoicePaymentStatus;
use App\Enums\WithdrawalRequestStatus;
use App\Filament\Concerns\AuthorizesManagers;
use App\Models\Customer;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Models\WithdrawalRequest;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ManagerStatsOverviewWidget extends StatsOverviewWidget
{
    use AuthorizesManagers;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $heading = 'نمای کلی';

    protected function getStats(): array
    {
        $customersByType = Customer::query()
            ->toBase()
            ->selectRaw('type, count(*) as aggregate')
            ->groupBy('type')
            ->pluck('aggregate', 'type');

        $individualCount = (int) ($customersByType[CustomerType::Individual->value] ?? 0);
        $companyCount = (int) ($customersByType[CustomerType::Company->value] ?? 0);
        $customerTotal = $individualCount + $companyCount;

        $invoiceStats = Invoice::query()
            ->toBase()
            ->where('payment_status', '!=', InvoicePaymentStatus::Cancelled)
            ->whereDate('invoice_date', '>=', now()->startOfMonth()->toDateString())
            ->whereDate('invoice_date', '<=', now()->endOfMonth()->toDateString())
            ->selectRaw('count(*) as invoices_count')
            ->selectRaw('coalesce(sum(total_amount), 0) as invoices_total')
            ->first();

        $approvedDiscounts = (float) DiscountRequest::query()
            ->whereIn('status', [
                DiscountRequestStatus::Approved,
                DiscountRequestStatus::Edited,
            ])
            ->sum('final_amount');

        $pendingWithdrawals = WithdrawalRequest::query()
            ->whereIn('status', [
                WithdrawalRequestStatus::Pending,
                WithdrawalRequestStatus::Processing,
            ])
            ->count();

        return [
            Stat::make('مشتریان', Number::format($customerTotal, precision: 0))
                ->description('حقیقی '.$individualCount.' · حقوقی '.$companyCount)
                ->icon(Heroicon::OutlinedUsers)
                ->color('info'),
            Stat::make('فاکتورهای این ماه', Number::format((float) ($invoiceStats->invoices_total ?? 0), precision: 0).' ریال')
                ->description(Number::format((int) ($invoiceStats->invoices_count ?? 0), precision: 0).' فاکتور')
                ->icon(Heroicon::OutlinedDocumentText)
                ->color('success'),
            Stat::make('تخفیف‌های تأییدشده', Number::format($approvedDiscounts, precision: 0).' ریال')
                ->description('مجموع مبلغ نهایی تأیید یا ویرایش‌شده')
                ->icon(Heroicon::OutlinedPercentBadge)
                ->color('warning'),
            Stat::make('واریز در انتظار پردازش', Number::format($pendingWithdrawals, precision: 0))
                ->description('درخواست‌های در انتظار یا در حال پردازش')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('danger'),
        ];
    }
}
