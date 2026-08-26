<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Enums\CustomerType;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['employee_id'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->type !== CustomerType::Company) {
            $this->record->companyProfile?->delete();
        }
    }
}
