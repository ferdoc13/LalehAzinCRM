<?php

namespace Database\Seeders;

use App\Enums\DiscountRequestStatus;
use App\Enums\WithdrawalRequestStatus;
use App\Models\Customer;
use App\Models\CustomerBankAccount;
use App\Models\CustomerCreditLedger;
use App\Models\DiscountRequest;
use App\Models\Invoice;
use App\Models\OtpCode;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Database\Seeders\Support\PersianFaker;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CrmDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $managers = User::factory()
            ->count(2)
            ->manager()
            ->create();

        $employees = User::factory()
            ->count(5)
            ->employee()
            ->create();

        $allEmployees = $employees->merge($managers);
        $manager = $managers->first();

        $individualCustomers = Customer::factory()
            ->count(12)
            ->individual()
            ->recycle($allEmployees)
            ->create();

        $companyCustomers = Customer::factory()
            ->count(8)
            ->company()
            ->recycle($allEmployees)
            ->create();

        $customers = $individualCustomers->merge($companyCustomers);

        foreach ($customers as $customer) {
            CustomerBankAccount::factory()
                ->count(fake()->numberBetween(1, 2))
                ->create(['customer_id' => $customer->id]);
        }

        foreach ($customers->random(15) as $customer) {
            Invoice::factory()
                ->withItems(fake()->numberBetween(2, 5))
                ->create([
                    'customer_id' => $customer->id,
                    'employee_id' => $allEmployees->random()->id,
                ]);
        }

        $pendingRequests = DiscountRequest::factory()
            ->count(4)
            ->recycle($customers)
            ->recycle($employees)
            ->create(['status' => DiscountRequestStatus::Pending]);

        $approvedRequests = DiscountRequest::factory()
            ->count(3)
            ->recycle($customers)
            ->recycle($employees)
            ->approved($manager)
            ->create();

        $editedRequests = DiscountRequest::factory()
            ->count(3)
            ->recycle($customers)
            ->recycle($employees)
            ->edited($manager)
            ->create();

        $rejectedRequests = DiscountRequest::factory()
            ->count(2)
            ->recycle($customers)
            ->recycle($employees)
            ->rejected($manager)
            ->create();

        $balanceByCustomer = [];

        foreach ($approvedRequests->merge($editedRequests) as $discountRequest) {
            $amount = (float) $discountRequest->final_amount;
            $previousBalance = $balanceByCustomer[$discountRequest->customer_id] ?? 0;
            $newBalance = $previousBalance + $amount;
            $balanceByCustomer[$discountRequest->customer_id] = $newBalance;

            CustomerCreditLedger::factory()
                ->forDiscountRequest($discountRequest)
                ->create([
                    'balance_after' => $newBalance,
                ]);
        }

        foreach ($customers->random(5) as $customer) {
            $ledger = CustomerCreditLedger::query()
                ->where('customer_id', $customer->id)
                ->latest()
                ->first();

            $bankAccount = $customer->bankAccounts()->first();

            if (! $bankAccount) {
                continue;
            }

            WithdrawalRequest::factory()->create([
                'customer_id' => $customer->id,
                'credit_ledger_id' => $ledger?->id,
                'discount_request_id' => $ledger?->discount_request_id,
                'amount' => fake()->randomFloat(2, 100_000, 1_000_000),
                'bank_account_id' => $bankAccount->id,
                'status' => fake()->randomElement(WithdrawalRequestStatus::cases()),
            ]);
        }

        foreach ($customers->random(10) as $customer) {
            OtpCode::factory()->create([
                'customer_id' => $customer->id,
                'phone' => $customer->mobile,
            ]);
        }

        OtpCode::factory()
            ->count(5)
            ->forPhone(PersianFaker::mobile())
            ->create();

        SmsLog::factory()->count(20)->create();
    }
}
