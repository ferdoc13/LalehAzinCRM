<?php

namespace App\Filament\Widgets;

use App\Enums\InvoicePaymentStatus;
use App\Filament\Concerns\AuthorizesManagers;
use App\Models\Invoice;
use Filament\Widgets\ChartWidget;

class InvoiceSalesChartWidget extends ChartWidget
{
    use AuthorizesManagers;

    protected static ?int $sort = 2;

    protected ?string $heading = 'روند فروش ۱۲ ماه اخیر';

    protected ?string $description = 'مبلغ فاکتورها به تفکیک ماه (بدون فاکتورهای لغوشده)';

    protected ?string $maxHeight = '320px';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'amount';

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, string>
     */
    protected function getFilters(): ?array
    {
        return [
            'amount' => 'مبلغ فروش',
            'count' => 'تعداد فاکتور',
        ];
    }

    protected function getData(): array
    {
        $start = now()->subMonths(11)->startOfMonth();
        $monthSql = $this->monthKeySql();

        $rows = Invoice::query()
            ->toBase()
            ->where('payment_status', '!=', InvoicePaymentStatus::Cancelled)
            ->where('invoice_date', '>=', $start->toDateString())
            ->selectRaw("{$monthSql} as month_key")
            ->selectRaw('count(*) as invoices_count')
            ->selectRaw('coalesce(sum(total_amount), 0) as sales_total')
            ->groupByRaw($monthSql)
            ->get()
            ->keyBy('month_key');

        $labels = [];
        $data = [];
        $useCount = $this->filter === 'count';

        foreach (range(0, 11) as $offset) {
            $month = $start->copy()->addMonths($offset);
            $key = $month->format('Y-m');
            $row = $rows->get($key);

            $labels[] = $month->translatedFormat('M Y');
            $data[] = $useCount
                ? (int) ($row->invoices_count ?? 0)
                : (float) ($row->sales_total ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => $useCount ? 'تعداد فاکتور' : 'مبلغ فروش (ریال)',
                    'data' => $data,
                    'fill' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    private function monthKeySql(): string
    {
        $column = (new Invoice)->qualifyColumn('invoice_date');

        return match (Invoice::query()->getConnection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
