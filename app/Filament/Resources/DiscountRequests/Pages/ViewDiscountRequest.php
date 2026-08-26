<?php

namespace App\Filament\Resources\DiscountRequests\Pages;

use App\Filament\Resources\DiscountRequests\Actions\ReviewDiscountRequestActions;
use App\Filament\Resources\DiscountRequests\DiscountRequestResource;
use Filament\Resources\Pages\ViewRecord;

class ViewDiscountRequest extends ViewRecord
{
    protected static string $resource = DiscountRequestResource::class;

    protected function getHeaderActions(): array
    {
        return ReviewDiscountRequestActions::make();
    }
}
