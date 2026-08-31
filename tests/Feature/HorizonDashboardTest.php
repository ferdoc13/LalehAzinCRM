<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    seedRoles();
});

it('lets a manager open the horizon dashboard', function () {
    $this->actingAs(staffUser('manager'))
        ->get('/horizon')
        ->assertOk();
});

it('lets an admin open the horizon dashboard', function () {
    $this->actingAs(staffUser('admin'))
        ->get('/horizon')
        ->assertOk();
});

it('forbids employees and guests from opening the horizon dashboard', function (?string $role) {
    if ($role) {
        $this->actingAs(staffUser($role));
    }

    $this->get('/horizon')->assertForbidden();
})->with([
    'employee' => 'employee',
    'guest' => null,
]);
