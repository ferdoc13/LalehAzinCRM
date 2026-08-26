<?php

namespace App\Models;

use App\Enums\DiscountRequestStatus;
use Database\Factories\DiscountRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'customer_id',
    'requested_by',
    'proposed_amount',
    'final_amount',
    'status',
    'reviewed_by',
    'reviewed_at',
])]
class DiscountRequest extends Model
{
    /** @use HasFactory<DiscountRequestFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'proposed_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'status' => DiscountRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function creditLedger(): HasOne
    {
        return $this->hasOne(CustomerCreditLedger::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    #[Scope]
    protected function visibleTo(Builder $query, User $user): Builder
    {
        if ($user->canAccessAllRecords()) {
            return $query;
        }

        return $query->where('requested_by', $user->id);
    }
}
