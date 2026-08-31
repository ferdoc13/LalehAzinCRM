<?php

use App\Actions\ReviewDiscountRequest;
use App\Enums\CreditTransactionType;
use App\Enums\DiscountRequestStatus;
use App\Exceptions\DiscountRequestAlreadyReviewedException;
use App\Models\Customer;
use App\Models\CustomerCreditLedger;
use App\Models\DiscountRequest;
use App\Services\CustomerCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

    expect($ledger)
        ->not->toBeNull()
        ->transaction_type->toBe(CreditTransactionType::Credit)
        ->and((float) $ledger->amount)->toBe(2_000_000.0)
        ->and((float) $ledger->balance_after)->toBe(2_000_000.0)
        ->and(app(CustomerCreditService::class)->getBalance($customer))->toBe(2_000_000.0);
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
