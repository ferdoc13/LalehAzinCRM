<?php

namespace App\Models;

use App\Enums\CustomerType;
use App\Observers\CustomerObserver;
use Database\Factories\CustomerFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'type',
    'first_name',
    'last_name',
    'national_code',
    'mobile',
    'address',
    'employee_id',
    'apply_credit_to_next_invoice',
])]
#[ObservedBy([CustomerObserver::class])]
class Customer extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, Notifiable;

    protected $attributes = [
        'apply_credit_to_next_invoice' => false,
    ];

    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
            'apply_credit_to_next_invoice' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CustomerCompanyProfile::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(CustomerBankAccount::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function discountRequests(): HasMany
    {
        return $this->hasMany(DiscountRequest::class);
    }

    public function creditLedgers(): HasMany
    {
        return $this->hasMany(CustomerCreditLedger::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'customer';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function routeNotificationForMelipayamak(?object $notification = null): ?string
    {
        return $this->mobile;
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if ($user->canAccessAllRecords()) {
            return $query;
        }

        return $query->where('employee_id', $user->id);
    }
}
