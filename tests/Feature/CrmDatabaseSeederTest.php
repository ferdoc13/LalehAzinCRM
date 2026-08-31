<?php

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\CrmDatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds demo staff and customers without relying on the fake helper namespace', function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CrmDatabaseSeeder::class);

    expect(User::query()->count())->toBe(7)
        ->and(Customer::query()->count())->toBe(20);
});
