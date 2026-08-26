<?php

namespace App\Models;

use Database\Factories\CustomerCompanyProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'customer_id',
    'company_name',
    'national_id',
    'economic_code',
    'company_address',
])]
class CustomerCompanyProfile extends Model
{
    /** @use HasFactory<CustomerCompanyProfileFactory> */
    use HasFactory;

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
