<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $relatedResource = InvoiceResource::class;

    protected static ?string $title = 'فاکتورها';

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make()->label('ثبت فاکتور'),
            ]);
    }
}
