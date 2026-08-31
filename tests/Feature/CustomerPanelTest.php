<?php

use App\Enums\WithdrawalRequestStatus;
use App\Filament\Customer\Pages\CreditBalance;
use App\Filament\Customer\Resources\BankAccounts\Pages\ManageBankAccounts;
use App\Filament\Customer\Resources\Invoices\InvoiceResource;
use App\Filament\Customer\Resources\Invoices\Pages\ListInvoices;
use App\Models\Customer;
use App\Models\CustomerBankAccount;
use App\Models\Invoice;
use App\Models\WithdrawalRequest;
use App\Services\CustomerCreditService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('customer');
});

it('lets a customer see only their own invoices', function () {
    $customer = customerUser();
    $other = Customer::factory()->create();

    $own = Invoice::factory()->create(['customer_id' => $customer->id]);
    $foreign = Invoice::factory()->create(['customer_id' => $other->id]);

    Livewire::test(ListInvoices::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('forbids a customer from viewing another customers invoice', function () {
    $customer = customerUser();
    $other = Customer::factory()->create();
    $foreign = Invoice::factory()->create(['customer_id' => $other->id]);

    $this->get(InvoiceResource::getUrl('view', ['record' => $foreign], panel: 'customer'))
        ->assertNotFound();
});

it('lets a customer manage only their own bank accounts', function () {
    $customer = customerUser();
    $other = Customer::factory()->create();

    $own = CustomerBankAccount::factory()->create(['customer_id' => $customer->id]);
    $foreign = CustomerBankAccount::factory()->create(['customer_id' => $other->id]);

    Livewire::test(ManageBankAccounts::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('lets a customer add a bank account to their profile', function () {
    customerUser();

    Livewire::test(ManageBankAccounts::class)
        ->callTableAction('create', data: [
            'bank_name' => 'بانک ملت',
            'account_number' => '1234567890',
            'sheba_number' => 'IR'.str_repeat('1', 24),
        ])
        ->assertHasNoTableActionErrors();

    $account = CustomerBankAccount::query()->first();

    expect($account)
        ->not->toBeNull()
        ->bank_name->toBe('بانک ملت')
        ->customer_id->toBe(auth('customer')->id());
});

it('records a request to apply credit on the next invoice', function () {
    $customer = customerUser();
    app(CustomerCreditService::class)->addCredit($customer, 500_000, 'اعتبار تست');

    Livewire::test(CreditBalance::class)
        ->assertOk()
        ->callAction('applyToNextInvoice')
        ->assertNotified();

    expect($customer->fresh()->apply_credit_to_next_invoice)->toBeTrue();
});

it('creates a pending withdrawal request for the registered bank account', function () {
    $customer = customerUser();
    $account = CustomerBankAccount::factory()->create(['customer_id' => $customer->id]);
    app(CustomerCreditService::class)->addCredit($customer, 750_000, 'اعتبار تست');

    Livewire::test(CreditBalance::class)
        ->callAction('requestWithdrawal', data: [
            'bank_account_id' => $account->id,
        ])
        ->assertNotified();

    $request = WithdrawalRequest::query()->first();

    expect($request)
        ->not->toBeNull()
        ->customer_id->toBe($customer->id)
        ->bank_account_id->toBe($account->id)
        ->status->toBe(WithdrawalRequestStatus::Pending)
        ->and((float) $request->amount)->toBe(750_000.0);
});

it('does not let a customer withdraw using another customers bank account', function () {
    $customer = customerUser();
    $foreign = CustomerBankAccount::factory()->create();
    app(CustomerCreditService::class)->addCredit($customer, 750_000, 'اعتبار تست');

    Livewire::test(CreditBalance::class)
        ->callAction('requestWithdrawal', data: [
            'bank_account_id' => $foreign->id,
        ]);

    expect(WithdrawalRequest::query()->count())->toBe(0);
});
