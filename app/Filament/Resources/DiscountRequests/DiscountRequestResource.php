<?php

namespace App\Filament\Resources\DiscountRequests;

use App\Filament\Resources\DiscountRequests\Pages\CreateDiscountRequest;
use App\Filament\Resources\DiscountRequests\Pages\ListDiscountRequests;
use App\Filament\Resources\DiscountRequests\Pages\ViewDiscountRequest;
use App\Filament\Resources\DiscountRequests\Schemas\DiscountRequestForm;
use App\Filament\Resources\DiscountRequests\Schemas\DiscountRequestInfolist;
use App\Filament\Resources\DiscountRequests\Tables\DiscountRequestsTable;
use App\Models\DiscountRequest;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DiscountRequestResource extends Resource
{
    protected static ?string $model = DiscountRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPercentBadge;

    protected static ?string $modelLabel = 'درخواست تخفیف';

    protected static ?string $pluralModelLabel = 'درخواست‌های تخفیف';

    protected static ?string $navigationLabel = 'درخواست‌های تخفیف';

    protected static ?int $navigationSort = 3;

    protected static bool $hasTitleCaseModelLabel = false;

    public static function form(Schema $schema): Schema
    {
        return DiscountRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DiscountRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiscountRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiscountRequests::route('/'),
            'create' => CreateDiscountRequest::route('/create'),
            'view' => ViewDiscountRequest::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var User $user */
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->with(['customer', 'requester', 'reviewer', 'invoice.items'])
            ->visibleTo($user);
    }
}
