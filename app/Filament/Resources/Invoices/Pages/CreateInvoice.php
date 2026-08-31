<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Customer;
use App\Services\CustomerCreditService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected bool $applyCustomerCredit = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $customer = Customer::query()->findOrFail($data['customer_id']);
        Gate::authorize('view', $customer);

        $this->applyCustomerCredit = (bool) ($data['apply_customer_credit'] ?? false);
        unset($data['apply_customer_credit']);

        $data['employee_id'] = auth()->id();
        $data['discount_amount'] = 0;
        $data['invoice_number'] = filled($data['invoice_number'] ?? null)
            ? $data['invoice_number']
            : 'INV-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncTotalFromItems();

        if (! $this->applyCustomerCredit) {
            return;
        }

        app(CustomerCreditService::class)->applyAvailableCreditToInvoice($this->record);
        $this->record->refresh();
        $this->record->customer()->update(['apply_credit_to_next_invoice' => false]);
    }
}
