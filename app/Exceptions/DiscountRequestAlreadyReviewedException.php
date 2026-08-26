<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class DiscountRequestAlreadyReviewedException extends Exception implements ShouldntReport
{
    public function __construct()
    {
        parent::__construct('این درخواست تخفیف قبلاً بررسی شده است.');
    }
}
