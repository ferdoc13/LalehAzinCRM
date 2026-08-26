<?php

namespace App\Filament\Resources\DiscountRequests\Pages;

use App\Filament\Resources\DiscountRequests\DiscountRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiscountRequests extends ListRecords
{
    protected static string $resource = DiscountRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('درخواست جدید'),
        ];
    }
}
