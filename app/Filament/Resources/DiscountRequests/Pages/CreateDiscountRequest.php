<?php

namespace App\Filament\Resources\DiscountRequests\Pages;

use App\Enums\DiscountRequestStatus;
use App\Filament\Resources\DiscountRequests\DiscountRequestResource;
use App\Models\Customer;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Gate;

class CreateDiscountRequest extends CreateRecord
{
    protected static string $resource = DiscountRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $customer = Customer::query()->findOrFail($data['customer_id']);
        Gate::authorize('view', $customer);

        $data['requested_by'] = auth()->id();
        $data['status'] = DiscountRequestStatus::Pending;
        $data['final_amount'] = null;
        $data['reviewed_by'] = null;
        $data['reviewed_at'] = null;

        return $data;
    }
}
