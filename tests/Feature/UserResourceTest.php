<?php

use App\Enums\StaffRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('lets a manager list users', function () {
    $manager = staffUser('manager');
    $employee = staffUser();

    $this->actingAs($manager);

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$manager, $employee]);
});

it('forbids employees and admins from accessing the user resource', function (string $role) {
    $this->actingAs(staffUser($role));

    Livewire::test(ListUsers::class)->assertForbidden();

    $this->get(UserResource::getUrl())->assertForbidden();
})->with(['employee', 'admin']);

it('lets a manager create a staff user', function () {
    $this->actingAs(staffUser('manager'));

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'سارا احمدی',
            'email' => 'sara@example.com',
            'role' => StaffRole::Employee->value,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::query()->where('email', 'sara@example.com')->first();

    expect($created)->not->toBeNull()
        ->name->toBe('سارا احمدی')
        ->and($created->hasRole(StaffRole::Employee->value))->toBeTrue()
        ->and(Hash::check('password', $created->password))->toBeTrue();
});

it('lets a manager block a user', function () {
    $manager = staffUser('manager');
    $employee = staffUser();

    $this->actingAs($manager);

    Livewire::test(ListUsers::class)
        ->callTableAction('block', $employee)
        ->assertHasNoTableActionErrors();

    expect($employee->fresh()->isBlocked())->toBeTrue();
});

it('hides the block action for the signed-in manager', function () {
    $manager = staffUser('manager');

    $this->actingAs($manager);

    Livewire::test(ListUsers::class)
        ->assertOk()
        ->assertTableActionHidden('block', $manager);
});

it('lets a manager unblock a user', function () {
    $manager = staffUser('manager');
    $employee = User::factory()->employee()->blocked()->create();

    $this->actingAs($manager);

    Livewire::test(ListUsers::class)
        ->callTableAction('unblock', $employee)
        ->assertHasNoTableActionErrors();

    expect($employee->fresh()->isBlocked())->toBeFalse();
});

it('prevents a blocked user from accessing the admin panel', function () {
    $employee = User::factory()->employee()->blocked()->create();

    $this->actingAs($employee)
        ->get('/admin')
        ->assertForbidden();
});
