<?php

use App\Enums\CreditTransactionType;
use App\Enums\DiscountRequestStatus;
use App\Enums\InvoicePaymentStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Resources\Invoices\Pages\ViewInvoice;
use App\Models\Customer;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Services\CustomerCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
});

it('lets an employee see only their own invoices', function () {
    $employee = staffUser();
    $other = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);

    $own = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
    ]);
    $foreign = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'employee_id' => $other->id,
    ]);

    $this->actingAs($employee);

    Livewire::test(ListInvoices::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('lets a manager see all invoices', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);

    $invoices = Invoice::factory()->count(2)->create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
    ]);

    $this->actingAs($manager);

    Livewire::test(ListInvoices::class)
        ->assertOk()
        ->assertCanSeeTableRecords($invoices);
});

it('forbids an employee from viewing another employees invoice', function () {
    $employee = staffUser();
    $other = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $other->id]);
    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'employee_id' => $other->id,
    ]);

    $this->actingAs($employee)
        ->get(InvoiceResource::getUrl('view', ['record' => $invoice]))
        ->assertNotFound();
});

it('creates an invoice with calculated line and invoice totals', function () {
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);

    $this->actingAs($employee);

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'payment_status' => InvoicePaymentStatus::Pending->value,
            'items' => [
                [
                    'description' => 'خدمات مشاوره فنی',
                    'quantity' => 2,
                    'unit_price' => 150000,
                    'total_amount' => 300000,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invoice = Invoice::query()->with('items')->first();

    expect($invoice)
        ->not->toBeNull()
        ->employee_id->toBe($employee->id)
        ->total_amount->toEqual(300000)
        ->and($invoice->items)->toHaveCount(1)
        ->and((float) $invoice->items->first()->total_amount)->toEqual(300000);
});

it('applies customer credit to a new invoice when requested', function () {
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    app(CustomerCreditService::class)->addCredit($customer, 100_000, 'موجودی اولیه');

    $this->actingAs($employee);

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'payment_status' => InvoicePaymentStatus::Pending->value,
            'apply_customer_credit' => true,
            'items' => [
                [
                    'description' => 'خدمات مشاوره فنی',
                    'quantity' => 2,
                    'unit_price' => 150000,
                    'total_amount' => 300000,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invoice = Invoice::query()->first();

    expect((float) $invoice->discount_amount)->toBe(100_000.0)
        ->and((float) $invoice->total_amount)->toBe(200_000.0)
        ->and(app(CustomerCreditService::class)->getBalance($customer))->toBe(0.0)
        ->and($invoice->creditLedgers)->toHaveCount(1)
        ->and($invoice->creditLedgers->first()->transaction_type)->toBe(CreditTransactionType::Debit);
});

it('lets an employee request a discount from the invoice page', function () {
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    $invoice = Invoice::factory()->priced(400_000)->create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
    ]);

    $this->actingAs($employee);

    Livewire::test(ViewInvoice::class, ['record' => $invoice->getRouteKey()])
        ->assertOk()
        ->assertActionVisible('requestDiscount')
        ->callAction('requestDiscount', data: [
            'proposed_amount' => 150_000,
        ])
        ->assertHasNoActionErrors();

    $request = DiscountRequest::query()->first();

    expect($request)
        ->not->toBeNull()
        ->invoice_id->toBe($invoice->id)
        ->customer_id->toBe($customer->id)
        ->status->toBe(DiscountRequestStatus::Pending)
        ->and((float) $request->proposed_amount)->toBe(150_000.0)
        ->and((float) $invoice->fresh()->total_amount)->toBe(400_000.0)
        ->and((float) $invoice->fresh()->discount_amount)->toBe(0.0);
});

it('lets a manager review a pending discount request from the invoice page', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    $invoice = Invoice::factory()->priced(400_000)->create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
    ]);
    DiscountRequest::factory()->create([
        'invoice_id' => $invoice->id,
        'requested_by' => $employee->id,
        'proposed_amount' => 80_000,
        'status' => DiscountRequestStatus::Pending,
    ]);

    $this->actingAs($manager);

    Livewire::test(ViewInvoice::class, ['record' => $invoice->getRouteKey()])
        ->assertOk()
        ->assertActionHidden('requestDiscount')
        ->callAction('approve')
        ->assertHasNoActionErrors();

    expect($invoice->fresh()->pendingDiscountRequest)->toBeNull()
        ->and(app(CustomerCreditService::class)->getBalance($customer))->toBe(80_000.0)
        ->and((float) $invoice->fresh()->total_amount)->toBe(400_000.0)
        ->and((float) $invoice->fresh()->discount_amount)->toBe(0.0);
});
