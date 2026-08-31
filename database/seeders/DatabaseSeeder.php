<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(ShieldSeeder::class);

        $user = User::query()->updateOrCreate(
            ['email' => 'ferdo30.ir@yahoo.com'],
            [
                'name' => 'فردوک',
                'password' => '12345678',
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles(['manager']);
    }
}
