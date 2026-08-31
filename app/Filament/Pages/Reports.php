<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Reports\EmployeePerformanceTableWidget;
use App\Filament\Widgets\Reports\PaidDiscountsTableWidget;
use App\Filament\Widgets\Reports\ReportInvoicesTableWidget;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

class Reports extends Page
{
    use HasFiltersForm;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'گزارش‌ها';

    protected static ?string $title = 'گزارش جامع';

    protected static ?string $slug = 'reports';

    protected static ?int $navigationSort = 20;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('ViewReports');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mountCanAuthorizeAccess(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function hydrateCanAuthorizeAccess(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('from')
                    ->label('از تاریخ')
                    ->default(now()->startOfMonth())
                    ->jalali(),
                DatePicker::make('until')
                    ->label('تا تاریخ')
                    ->default(now())
                    ->jalali()
                    ->afterOrEqual('from'),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedSchema::make('filtersForm'),
                Grid::make(1)
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents($this->getWidgets())),
            ]);
    }

    /**
     * @return array<class-string<Widget> | WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return [
            ReportInvoicesTableWidget::class,
            EmployeePerformanceTableWidget::class,
            PaidDiscountsTableWidget::class,
        ];
    }
}
