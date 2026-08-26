<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'ferdo30.ir@yahoo.com'],
            [
                'name' => 'فردوک',
                'password' => '12345678',
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles(['admin']);
    }
}
