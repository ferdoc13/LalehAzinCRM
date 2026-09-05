<?php

namespace App\Filament\Resources\DiscountRequests\Pages;

use App\Actions\CreateDiscountRequest as CreateDiscountRequestAction;
use App\Filament\Resources\DiscountRequests\DiscountRequestResource;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class CreateDiscountRequest extends CreateRecord
{
    protected static string $resource = DiscountRequestResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $invoice = Invoice::query()->findOrFail($data['invoice_id']);
        Gate::authorize('view', $invoice);

        /** @var DiscountRequest $request */
        $request = app(CreateDiscountRequestAction::class)->handle(
            invoice: $invoice,
            requester: auth()->user(),
            proposedAmount: (float) $data['proposed_amount'],
        );

        return $request;
    }
}
