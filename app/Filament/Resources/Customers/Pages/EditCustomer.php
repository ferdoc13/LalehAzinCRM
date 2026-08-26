<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Enums\CustomerType;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('مشاهده'),
            DeleteAction::make()->label('حذف'),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->type !== CustomerType::Company) {
            $this->record->companyProfile?->delete();
        }
    }
}
