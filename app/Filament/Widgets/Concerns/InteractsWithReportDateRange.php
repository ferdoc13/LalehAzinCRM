<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\CarbonInterface;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

trait InteractsWithReportDateRange
{
    use InteractsWithPageFilters;

    protected function reportFrom(): CarbonInterface
    {
        $from = $this->pageFilters['from'] ?? null;

        return filled($from) ? Carbon::parse($from)->startOfDay() : now()->startOfMonth();
    }

    protected function reportUntil(): CarbonInterface
    {
        $until = $this->pageFilters['until'] ?? null;

        return filled($until) ? Carbon::parse($until)->endOfDay() : now()->endOfDay();
    }
}
