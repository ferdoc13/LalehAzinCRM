<?php

use App\Actions\ReviewDiscountRequest;
use App\Enums\DiscountRequestStatus;
use App\Enums\WithdrawalRequestStatus;
use App\Models\Customer;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\WithdrawalRequest;
use App\Notifications\CustomerRegisteredNotification;
use App\Notifications\DiscountAppliedNotification;
use App\Notifications\DiscountRequestCreatedNotification;
use App\Notifications\DiscountReviewedNotification;
use App\Notifications\InvoiceCreatedNotification;
use App\Notifications\MelipayamakNotification;
use App\Notifications\OtpCodeNotification;
use App\Notifications\WithdrawalCompletedNotification;
use App\Notifications\WithdrawalRequestCreatedNotification;
use App\Services\CustomerCreditService;
use App\Services\OtpService;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('queues customer registration sms when a customer is created', function () {
    Notification::fake();

    $customer = Customer::factory()->create();

    Notification::assertSentTo($customer, CustomerRegisteredNotification::class);
    expect(new CustomerRegisteredNotification)->toBeInstanceOf(ShouldQueue::class);
});

it('sends otp via notification instead of the application log', function () {
    Notification::fake();

    $customer = Customer::factory()->create(['mobile' => '09125550000']);

    $otp = app(OtpService::class)->send($customer->mobile);

    Notification::assertSentTo(
        $customer,
        OtpCodeNotification::class,
        fn (OtpCodeNotification $notification): bool => $notification->otpCode->is($otp),
    );
});

it('notifies the customer when an invoice is created', function () {
    $customer = Customer::factory()->create();

    Notification::fake();

    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'employee_id' => $customer->employee_id,
    ]);

    Notification::assertSentTo(
        $customer,
        InvoiceCreatedNotification::class,
        fn (InvoiceCreatedNotification $notification): bool => $notification->invoice->is($invoice),
    );
});

it('optionally notifies managers when a discount request is created', function () {
    $customer = Customer::factory()->create();

    config(['sms.manager_mobiles' => ['09120000001']]);

    Notification::fake();

    $request = DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $customer->employee_id,
        'status' => DiscountRequestStatus::Pending,
    ]);

    Notification::assertSentOnDemand(
        DiscountRequestCreatedNotification::class,
        fn (DiscountRequestCreatedNotification $notification, array $channels, object $notifiable): bool => $notification->discountRequest->is($request)
            && $notifiable->routes['melipayamak'] === '09120000001',
    );
});

it('skips manager discount alerts when no manager mobiles are configured', function () {
    $customer = Customer::factory()->create();

    config(['sms.manager_mobiles' => []]);

    Notification::fake();

    DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $customer->employee_id,
    ]);

    Notification::assertNothingSent();
});

it('notifies the customer when a discount request is reviewed', function () {
    $manager = staffUser('manager');
    $customer = Customer::factory()->create();
    $request = DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $customer->employee_id,
        'proposed_amount' => 1_500_000,
        'status' => DiscountRequestStatus::Pending,
    ]);

    Notification::fake();

    app(ReviewDiscountRequest::class)->approve($request, $manager);

    Notification::assertSentTo($customer, DiscountReviewedNotification::class);
});

it('notifies the customer when credit is applied to an invoice', function () {
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'employee_id' => $customer->employee_id,
        'discount_amount' => 0,
        'total_amount' => 0,
    ]);
    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
        'quantity' => 1,
        'unit_price' => 500_000,
        'total_amount' => 500_000,
    ]);
    app(CustomerCreditService::class)->addCredit($customer, 100_000, 'اعتبار تست');

    Notification::fake();

    app(CustomerCreditService::class)->applyAvailableCreditToInvoice($invoice);

    Notification::assertSentTo($customer, DiscountAppliedNotification::class);
});

it('notifies the customer when a withdrawal is requested and when it is completed', function () {
    $customer = Customer::factory()->create();

    Notification::fake();

    $request = WithdrawalRequest::factory()->create([
        'customer_id' => $customer->id,
        'status' => WithdrawalRequestStatus::Pending,
        'amount' => 250_000,
    ]);

    Notification::assertSentTo($customer, WithdrawalRequestCreatedNotification::class);

    $request->update(['status' => WithdrawalRequestStatus::Done]);

    Notification::assertSentTo($customer, WithdrawalCompletedNotification::class);
});

it('marks all melipayamak notifications as queued', function (string $class) {
    expect(is_subclass_of($class, MelipayamakNotification::class))->toBeTrue()
        ->and(is_a($class, ShouldQueue::class, true))->toBeTrue();
})->with([
    CustomerRegisteredNotification::class,
    OtpCodeNotification::class,
    InvoiceCreatedNotification::class,
    DiscountRequestCreatedNotification::class,
    DiscountReviewedNotification::class,
    DiscountAppliedNotification::class,
    WithdrawalRequestCreatedNotification::class,
    WithdrawalCompletedNotification::class,
]);
