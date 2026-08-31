<?php

use App\Enums\CustomerType;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\Customer;
use App\Services\CustomerCreditService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('lets an employee see only their own customers', function () {
    $employee = staffUser();
    $other = staffUser();

    $own = Customer::factory()->create(['employee_id' => $employee->id]);
    $foreign = Customer::factory()->create(['employee_id' => $other->id]);

    $this->actingAs($employee);

    Livewire::test(ListCustomers::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('lets a manager see all customers', function () {
    $manager = staffUser('manager');
    $employee = staffUser();

    $customers = Customer::factory()->count(2)->create(['employee_id' => $employee->id]);

    $this->actingAs($manager);

    Livewire::test(ListCustomers::class)
        ->assertOk()
        ->assertCanSeeTableRecords($customers);
});

it('forbids an employee from viewing another employees customer', function () {
    $employee = staffUser();
    $other = staffUser();
    $foreign = Customer::factory()->create(['employee_id' => $other->id]);

    $this->actingAs($employee)
        ->get(CustomerResource::getUrl('view', ['record' => $foreign]))
        ->assertNotFound();
});

it('assigns the logged-in employee when creating a customer', function () {
    $employee = staffUser();

    $this->actingAs($employee);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'type' => CustomerType::Individual->value,
            'first_name' => 'علی',
            'last_name' => 'محمدی',
            'mobile' => '09121234567',
            'national_code' => '0012345678',
            'address' => 'تهران، خیابان ولیعصر',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $customer = Customer::query()->first();

    expect($customer)
        ->not->toBeNull()
        ->employee_id->toBe($employee->id)
        ->first_name->toBe('علی')
        ->type->toBe(CustomerType::Individual)
        ->and($customer->companyProfile)->toBeNull();
});

it('saves a company profile only when the customer type is company', function () {
    $employee = staffUser();

    $this->actingAs($employee);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'type' => CustomerType::Company->value,
            'first_name' => 'رضا',
            'last_name' => 'کریمی',
            'mobile' => '09129876543',
            'companyProfile' => [
                'company_name' => 'شرکت بازرگانی پارس نوین',
                'national_id' => '10100012345',
                'economic_code' => '411111111111',
                'company_address' => 'تهران، میدان آرژانتین',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $customer = Customer::query()->with('companyProfile')->first();

    expect($customer->type)->toBe(CustomerType::Company)
        ->and($customer->companyProfile?->company_name)->toBe('شرکت بازرگانی پارس نوین');
});

it('shows the current credit balance on the customer profile', function () {
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    app(CustomerCreditService::class)->addCredit($customer, 750_000, 'اعتبار تست');

    $this->actingAs($employee);

    Livewire::test(ViewCustomer::class, ['record' => $customer->getKey()])
        ->assertOk()
        ->assertSee('موجودی اعتبار')
        ->assertSee('تاریخچه اعتبار');
});
