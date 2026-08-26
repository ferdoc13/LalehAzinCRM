<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class InsufficientCreditException extends Exception implements ShouldntReport
{
    public function __construct(
        public readonly float $requested,
        public readonly float $available,
    ) {
        parent::__construct('موجودی اعتبار مشتری کافی نیست.');
    }

    public function context(): array
    {
        return [
            'requested' => $this->requested,
            'available' => $this->available,
        ];
    }
}
