<?php

use App\Actions\CreateDiscountRequest;
use App\Actions\ReviewDiscountRequest;
use App\Enums\CreditTransactionType;
use App\Enums\DiscountRequestStatus;
use App\Exceptions\DiscountRequestAlreadyReviewedException;
use App\Models\Customer;
use App\Models\CustomerCreditLedger;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Services\CustomerCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
});

it('credits the customer when a manager approves a discount request', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    $request = DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $employee->id,
        'proposed_amount' => 2_000_000,
        'status' => DiscountRequestStatus::Pending,
    ]);

    $reviewed = app(ReviewDiscountRequest::class)->approve($request, $manager);

    expect($reviewed)
        ->status->toBe(DiscountRequestStatus::Approved)
        ->final_amount->toEqual(2_000_000)
        ->reviewed_by->toBe($manager->id)
        ->reviewed_at->not->toBeNull();

    $ledger = CustomerCreditLedger::query()->where('discount_request_id', $request->id)->first();
    $invoice = $request->invoice()->first();

    expect($ledger)
        ->not->toBeNull()
        ->transaction_type->toBe(CreditTransactionType::Credit)
        ->invoice_id->toBe($invoice->id)
        ->and((float) $ledger->amount)->toBe(2_000_000.0)
        ->and((float) $ledger->balance_after)->toBe(2_000_000.0)
        ->and(app(CustomerCreditService::class)->getBalance($customer))->toBe(2_000_000.0)
        ->and((float) $invoice->fresh()->total_amount)->toBe($invoice->itemsTotal())
        ->and((float) $invoice->fresh()->discount_amount)->toBe(0.0);
});

it('does not create a credit ledger when a discount request is rejected', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    $request = DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $employee->id,
        'proposed_amount' => 2_000_000,
        'status' => DiscountRequestStatus::Pending,
    ]);

    app(ReviewDiscountRequest::class)->reject($request, $manager);

    expect($request->fresh()->status)->toBe(DiscountRequestStatus::Rejected)
        ->and(CustomerCreditLedger::query()->where('customer_id', $customer->id)->exists())->toBeFalse()
        ->and(app(CustomerCreditService::class)->getBalance($customer))->toBe(0.0);
});

it('cannot review a discount request more than once', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    $request = DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $employee->id,
        'proposed_amount' => 1_000_000,
        'status' => DiscountRequestStatus::Pending,
    ]);

    $review = app(ReviewDiscountRequest::class);
    $review->approve($request, $manager);
    $review->approve($request->fresh(), $manager);
})->throws(DiscountRequestAlreadyReviewedException::class);

it('binds a new discount request to the invoice customer', function () {
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    $invoice = Invoice::factory()->priced(3_000_000)->create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
    ]);

    $request = app(CreateDiscountRequest::class)->handle($invoice, $employee, 1_200_000);

    expect($request)
        ->invoice_id->toBe($invoice->id)
        ->customer_id->toBe($customer->id)
        ->requested_by->toBe($employee->id)
        ->status->toBe(DiscountRequestStatus::Pending)
        ->and((float) $request->proposed_amount)->toBe(1_200_000.0)
        ->and((float) $invoice->fresh()->total_amount)->toBe(3_000_000.0)
        ->and((float) $invoice->fresh()->discount_amount)->toBe(0.0);
});

it('rejects a second pending discount request on the same invoice', function () {
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    $invoice = Invoice::factory()->priced(3_000_000)->create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
    ]);

    $action = app(CreateDiscountRequest::class);
    $action->handle($invoice, $employee, 500_000);
    $action->handle($invoice, $employee, 400_000);
})->throws(ValidationException::class);

it('rejects a proposed discount greater than the invoice items total', function () {
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);
    $invoice = Invoice::factory()->priced(500_000)->create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
    ]);

    app(CreateDiscountRequest::class)->handle($invoice, $employee, 500_001);
})->throws(ValidationException::class);
