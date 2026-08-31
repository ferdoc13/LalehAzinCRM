<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function registeredCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'employee_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'employee_id');
    }

    public function discountRequests(): HasMany
    {
        return $this->hasMany(DiscountRequest::class, 'requested_by');
    }

    public function reviewedDiscountRequests(): HasMany
    {
        return $this->hasMany(DiscountRequest::class, 'reviewed_by');
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['employee', 'manager', 'admin']);
    }

    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    public function canAccessAllRecords(): bool
    {
        return $this->hasAnyRole(['manager', 'admin']);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->isStaff();
    }
}
