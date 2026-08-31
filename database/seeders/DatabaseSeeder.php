<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (['employee', 'manager', 'admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $admin = User::query()->updateOrCreate(
            ['email' => 'ferdo30.ir@yahoo.com'],
            [
                'name' => 'فردوک',
                'password' => '12345678',
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles(['manager']);
    }
}
