<?php

namespace App\Models;

use App\Enums\CustomerType;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'type',
    'first_name',
    'last_name',
    'national_code',
    'mobile',
    'address',
    'employee_id',
])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
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

    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if ($user->canAccessAllRecords()) {
            return $query;
        }

        return $query->where('employee_id', $user->id);
    }
}
