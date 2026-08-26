<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerCompanyProfile;
use Database\Seeders\Support\PersianFaker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerCompanyProfile>
 */
class CustomerCompanyProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'company_name' => PersianFaker::companyName(),
            'national_id' => PersianFaker::nationalId(),
            'economic_code' => PersianFaker::economicCode(),
            'company_address' => PersianFaker::address(),
        ];
    }
}
