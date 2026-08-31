<?php

use App\Enums\DiscountRequestStatus;
use App\Filament\Resources\DiscountRequests\Pages\ListDiscountRequests;
use App\Models\Customer;
use App\Models\CustomerCreditLedger;
use App\Models\DiscountRequest;
use App\Services\CustomerCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
});

it('lets an employee see only their own discount requests', function () {
    $employee = staffUser();
    $other = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);

    $own = DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $employee->id,
    ]);
    $foreign = DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $other->id,
    ]);

    $this->actingAs($employee);

    Livewire::test(ListDiscountRequests::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('hides review actions from employees', function () {
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    $request = DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $employee->id,
        'status' => DiscountRequestStatus::Pending,
    ]);

    $this->actingAs($employee);

    Livewire::test(ListDiscountRequests::class)
        ->assertOk()
        ->assertTableActionHidden('approve', $request)
        ->assertTableActionHidden('editAndApprove', $request)
        ->assertTableActionHidden('reject', $request);
});

it('lets a manager approve a pending discount request', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    $request = DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $employee->id,
        'proposed_amount' => 2_000_000,
        'status' => DiscountRequestStatus::Pending,
    ]);

    $this->actingAs($manager);

    Livewire::test(ListDiscountRequests::class)
        ->callTableAction('approve', $request)
        ->assertHasNoTableActionErrors();

    expect($request->fresh())
        ->status->toBe(DiscountRequestStatus::Approved)
        ->final_amount->toEqual(2_000_000)
        ->reviewed_by->toBe($manager->id)
        ->reviewed_at->not->toBeNull();

    expect(CustomerCreditLedger::query()->where('discount_request_id', $request->id)->count())->toBe(1)
        ->and(app(CustomerCreditService::class)->getBalance($customer))->toBe(2_000_000.0);
});

it('lets a manager edit and approve a pending discount request', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    $request = DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $employee->id,
        'proposed_amount' => 2_000_000,
        'status' => DiscountRequestStatus::Pending,
    ]);

    $this->actingAs($manager);

    Livewire::test(ListDiscountRequests::class)
        ->callTableAction('editAndApprove', $request, data: [
            'final_amount' => 1_500_000,
        ])
        ->assertHasNoTableActionErrors();

    expect($request->fresh())
        ->status->toBe(DiscountRequestStatus::Edited)
        ->final_amount->toEqual(1_500_000)
        ->reviewed_by->toBe($manager->id);
});

it('lets a manager reject a pending discount request', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    $request = DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $employee->id,
        'status' => DiscountRequestStatus::Pending,
    ]);

    $this->actingAs($manager);

    Livewire::test(ListDiscountRequests::class)
        ->callTableAction('reject', $request)
        ->assertHasNoTableActionErrors();

    expect($request->fresh())
        ->status->toBe(DiscountRequestStatus::Rejected)
        ->reviewed_by->toBe($manager->id);

    expect(CustomerCreditLedger::query()->where('customer_id', $customer->id)->exists())->toBeFalse();
});
