<?php

use App\Enums\DiscountRequestStatus;
use App\Enums\InvoicePaymentStatus;
use App\Enums\WithdrawalRequestStatus;
use App\Filament\Pages\Reports;
use App\Filament\Widgets\InvoiceSalesChartWidget;
use App\Filament\Widgets\ManagerStatsOverviewWidget;
use App\Filament\Widgets\PendingDiscountRequestsWidget;
use App\Filament\Widgets\Reports\EmployeePerformanceTableWidget;
use App\Filament\Widgets\Reports\PaidDiscountsTableWidget;
use App\Filament\Widgets\Reports\ReportInvoicesTableWidget;
use App\Models\Customer;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Models\WithdrawalRequest;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Number;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
    Notification::fake();
});

it('lets a manager view dashboard overview widgets', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $individual = Customer::factory()->individual()->create(['employee_id' => $employee->id]);
    Customer::factory()->company()->create(['employee_id' => $employee->id]);

    Invoice::factory()->create([
        'customer_id' => $individual->id,
        'employee_id' => $employee->id,
        'invoice_date' => now()->toDateString(),
        'total_amount' => 1_500_000,
        'payment_status' => InvoicePaymentStatus::Paid,
    ]);

    DiscountRequest::factory()->approved($manager)->create([
        'customer_id' => $individual->id,
        'requested_by' => $employee->id,
        'proposed_amount' => 200_000,
        'final_amount' => 200_000,
    ]);

    WithdrawalRequest::factory()->create([
        'customer_id' => $individual->id,
        'status' => WithdrawalRequestStatus::Pending,
        'amount' => 200_000,
    ]);

    $this->actingAs($manager);

    Livewire::test(Dashboard::class)->assertOk();

    Livewire::test(ManagerStatsOverviewWidget::class)
        ->assertOk()
        ->assertSee('حقیقی')
        ->assertSee('حقوقی')
        ->assertSee('فاکتورهای این ماه')
        ->assertSee(Number::format(1_500_000, precision: 0));

    Livewire::test(InvoiceSalesChartWidget::class)->assertOk();
});

it('hides manager widgets and reports from employees and admins', function (string $role) {
    $this->actingAs(staffUser($role));

    Livewire::test(Dashboard::class)->assertOk();

    expect(ManagerStatsOverviewWidget::canView())->toBeFalse()
        ->and(InvoiceSalesChartWidget::canView())->toBeFalse()
        ->and(PendingDiscountRequestsWidget::canView())->toBeFalse()
        ->and(Reports::canAccess())->toBeFalse();

    Livewire::test(ManagerStatsOverviewWidget::class)->assertForbidden();

    $this->get(Reports::getUrl())->assertForbidden();
})->with(['employee', 'admin']);

it('lists pending discount requests for a manager to review', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);

    $pending = DiscountRequest::factory()->create([
        'customer_id' => $customer->id,
        'requested_by' => $employee->id,
        'status' => DiscountRequestStatus::Pending,
        'proposed_amount' => 750_000,
    ]);
    $approved = DiscountRequest::factory()->approved($manager)->create([
        'customer_id' => $customer->id,
        'requested_by' => $employee->id,
    ]);

    $this->actingAs($manager);

    Livewire::test(PendingDiscountRequestsWidget::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$approved]);
});

it('lets a manager open the reports page', function () {
    $this->actingAs(staffUser('manager'))
        ->get(Reports::getUrl())
        ->assertOk();
});

it('forbids employees and admins from opening the reports page', function (string $role) {
    $this->actingAs(staffUser($role))
        ->get(Reports::getUrl())
        ->assertForbidden();
})->with(['employee', 'admin']);

it('filters report invoices by the selected date range', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);

    $inRange = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
        'invoice_date' => now()->toDateString(),
        'total_amount' => 800_000,
        'payment_status' => InvoicePaymentStatus::Paid,
    ]);
    $outOfRange = Invoice::factory()->create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
        'invoice_date' => now()->subMonths(3)->toDateString(),
        'total_amount' => 400_000,
        'payment_status' => InvoicePaymentStatus::Paid,
    ]);

    $this->actingAs($manager);

    Livewire::test(ReportInvoicesTableWidget::class, [
        'pageFilters' => [
            'from' => now()->startOfMonth()->toDateString(),
            'until' => now()->toDateString(),
        ],
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$inRange])
        ->assertCanNotSeeTableRecords([$outOfRange])
        ->assertTableActionExists('export');
});

it('summarizes employee performance in the selected range', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $customer = Customer::factory()->create([
        'employee_id' => $employee->id,
        'created_at' => now()->subDay(),
    ]);

    Invoice::factory()->create([
        'customer_id' => $customer->id,
        'employee_id' => $employee->id,
        'invoice_date' => now()->toDateString(),
        'total_amount' => 900_000,
        'payment_status' => InvoicePaymentStatus::Paid,
    ]);

    $this->actingAs($manager);

    $widget = Livewire::test(EmployeePerformanceTableWidget::class, [
        'pageFilters' => [
            'from' => now()->startOfMonth()->toDateString(),
            'until' => now()->toDateString(),
        ],
    ])
        ->assertOk()
        ->assertSee($employee->name)
        ->assertCanSeeTableRecords([$employee]);

    $employeeRow = $widget->instance()->getTableRecords()->firstWhere('id', $employee->id);

    expect($employeeRow->invoices_count)->toBe(1)
        ->and((float) $employeeRow->invoices_total)->toBe(900_000.0)
        ->and($employeeRow->customers_count)->toBe(1);
});

it('lists completed withdrawals in the selected range', function () {
    $manager = staffUser('manager');
    $employee = staffUser();
    $customer = Customer::factory()->create(['employee_id' => $employee->id]);

    $paid = WithdrawalRequest::factory()->create([
        'customer_id' => $customer->id,
        'status' => WithdrawalRequestStatus::Done,
        'amount' => 350_000,
        'updated_at' => now(),
    ]);
    $old = WithdrawalRequest::factory()->create([
        'customer_id' => $customer->id,
        'status' => WithdrawalRequestStatus::Done,
        'amount' => 120_000,
    ]);
    $old->forceFill(['updated_at' => now()->subMonths(4)])->saveQuietly();
    $pending = WithdrawalRequest::factory()->create([
        'customer_id' => $customer->id,
        'status' => WithdrawalRequestStatus::Pending,
        'amount' => 50_000,
    ]);

    $this->actingAs($manager);

    Livewire::test(PaidDiscountsTableWidget::class, [
        'pageFilters' => [
            'from' => now()->startOfMonth()->toDateString(),
            'until' => now()->toDateString(),
        ],
    ])
        ->assertOk()
        ->assertCanSeeTableRecords([$paid])
        ->assertCanNotSeeTableRecords([$old, $pending]);
});
