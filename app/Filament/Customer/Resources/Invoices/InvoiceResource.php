<?php

namespace App\Filament\Customer\Resources\Invoices;

use App\Filament\Customer\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Customer\Resources\Invoices\Pages\ViewInvoice;
use App\Models\Customer;
use App\Models\Invoice;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    protected static ?string $modelLabel = 'فاکتور';

    protected static ?string $pluralModelLabel = 'فاکتورهای من';

    protected static ?string $navigationLabel = 'فاکتورهای من';

    protected static ?int $navigationSort = 2;

    protected static bool $hasTitleCaseModelLabel = false;

    public static function infolist(Schema $schema): Schema
    {
        return InvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvoicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'view' => ViewInvoice::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $customer = Filament::auth()->user();

        abort_unless($customer instanceof Customer, 403);

        return parent::getEloquentQuery()
            ->where('customer_id', $customer->id)
            ->with(['items']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['invoice_number'];
    }
}
