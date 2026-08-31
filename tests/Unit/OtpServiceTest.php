<?php

use App\Models\Customer;
use App\Models\OtpCode;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a six digit otp that expires in two minutes', function () {
    $customer = Customer::factory()->create(['mobile' => '09125556677']);

    $otp = app(OtpService::class)->send($customer->mobile);

    expect($otp)
        ->customer_id->toBe($customer->id)
        ->phone->toBe($customer->mobile)
        ->is_used->toBeFalse()
        ->and(strlen($otp->code))->toBe(6)
        ->and($otp->expires_at->getTimestamp())->toBeGreaterThan(now()->addMinute()->getTimestamp())
        ->and($otp->expires_at->getTimestamp())->toBeLessThanOrEqual(now()->addMinutes(2)->getTimestamp());
});

it('consumes a valid otp once', function () {
    $customer = Customer::factory()->create(['mobile' => '09125556678']);
    $service = app(OtpService::class);
    $otp = $service->send($customer->mobile);

    expect($service->consume($customer, $otp->code))->toBeTrue()
        ->and($otp->fresh()->is_used)->toBeTrue()
        ->and($service->consume($customer, $otp->code))->toBeFalse();
});

it('rejects an expired otp', function () {
    $customer = Customer::factory()->create(['mobile' => '09125556679']);
    $otp = OtpCode::factory()->expired()->create([
        'customer_id' => $customer->id,
        'phone' => $customer->mobile,
        'code' => '123456',
    ]);

    expect(app(OtpService::class)->consume($customer, $otp->code))->toBeFalse();
});

it('throws when the mobile number is not registered', function () {
    app(OtpService::class)->send('09120000000');
})->throws(ValidationException::class);
