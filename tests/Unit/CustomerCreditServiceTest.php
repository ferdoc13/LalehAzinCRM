<?php

use App\Enums\CreditTransactionType;
use App\Exceptions\InsufficientCreditException;
use App\Models\Customer;
use App\Services\CustomerCreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('starts customers with a zero balance', function () {
    $customer = Customer::factory()->create();

    expect(app(CustomerCreditService::class)->getBalance($customer))->toBe(0.0);
});

it('adds credit and tracks the running balance', function () {
    $customer = Customer::factory()->create();
    $credits = app(CustomerCreditService::class);

    $first = $credits->addCredit($customer, 1_000_000, 'افزایش اول');
    $second = $credits->addCredit($customer, 500_000, 'افزایش دوم');

    expect((float) $first->balance_after)->toBe(1_000_000.0)
        ->and($first->transaction_type)->toBe(CreditTransactionType::Credit)
        ->and((float) $second->balance_after)->toBe(1_500_000.0)
        ->and($credits->getBalance($customer))->toBe(1_500_000.0);
});

it('deducts credit from the current balance', function () {
    $customer = Customer::factory()->create();
    $credits = app(CustomerCreditService::class);

    $credits->addCredit($customer, 1_000_000, 'افزایش');
    $debit = $credits->deductCredit($customer, 250_000, 'کسر');

    expect($debit->transaction_type)->toBe(CreditTransactionType::Debit)
        ->and((float) $debit->balance_after)->toBe(750_000.0)
        ->and($credits->getBalance($customer))->toBe(750_000.0);
});

it('rejects a debit that exceeds the available balance', function () {
    $customer = Customer::factory()->create();
    $credits = app(CustomerCreditService::class);

    $credits->addCredit($customer, 100_000, 'افزایش');

    $credits->deductCredit($customer, 200_000, 'کسر بیش از موجودی');
})->throws(InsufficientCreditException::class);
